<?php

namespace App\Console\Commands;

use App\Services\MarketPriceImport;
use App\Services\PihpsBaliPrices;
use App\Support\PriceCommodities;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

final class SyncPihpsPrices extends Command
{
    protected $signature = 'pihps:sync
        {--from= : Tanggal awal (YYYY-MM-DD)}
        {--to= : Tanggal akhir (YYYY-MM-DD)}
        {--dry-run : Tampilkan hasil tanpa menyimpan ke database}';

    protected $description = 'Sinkronkan harga tiga komoditas pasar tradisional Bali dari PIHPS';

    public function handle(PihpsBaliPrices $pihps, MarketPriceImport $import): int
    {
        $today = CarbonImmutable::today('Asia/Makassar');
        $endDate = $this->option('to') ?? $today->toDateString();
        $startDate = $this->option('from');
        $validator = Validator::make(
            ['awal' => $startDate, 'akhir' => $endDate],
            ['awal' => ['nullable', 'date_format:Y-m-d'], 'akhir' => ['required', 'date_format:Y-m-d']],
        );
        if ($startDate === '' || $validator->fails()) {
            $this->error('Gunakan format tanggal YYYY-MM-DD pada --from dan --to.');

            return self::INVALID;
        }
        $to = CarbonImmutable::parse($endDate, 'Asia/Makassar');
        $from = $startDate === null ? $to->subDays(8) : CarbonImmutable::parse($startDate, 'Asia/Makassar');
        if ($from > $to || $from < $to->subDays(89) || $to > $today) {
            $this->error('Pilih rentang tanggal hingga hari ini dengan panjang maksimal 90 hari kalender.');

            return self::INVALID;
        }

        $dryRun = (bool) $this->option('dry-run');
        $store = Cache::store('file')->getStore();
        if (! $store instanceof LockProvider) {
            $this->error('Cache lokal harus mendukung penguncian sinkronisasi.');

            return self::FAILURE;
        }
        $lock = $store->lock('pihps-bali-sync', 900);
        if (! $lock->get()) {
            $this->warn('Sinkronisasi PIHPS lain sedang berjalan.');

            return self::FAILURE;
        }

        $runId = null;
        try {
            if (! $dryRun) {
                $runId = DB::table('price_sync_runs')->insertGetId([
                    'range_start' => $from->toDateString(), 'range_end' => $to->toDateString(),
                    'started_at' => now(), 'status' => 'running',
                ]);
            }
            $report = $pihps->fetch($from, $to);
            foreach ($report['warnings'] as $warning) {
                $this->warn($warning);
            }
            foreach ($report['prices'] as $slug => $prices) {
                $this->line(PriceCommodities::ALL[$slug]['name'].': '.count($prices).' pengamatan.');
                if ($dryRun && $prices !== []) {
                    $rows = [];
                    foreach ($prices as $date => $price) {
                        $rows[] = [$date, 'Rp'.number_format($price, 0, ',', '.')];
                    }
                    $this->table(['Tanggal', 'Harga per kg'], $rows);
                }
            }
            if (array_filter($report['prices']) === []) {
                throw new UnexpectedValueException('Tidak ada pengamatan PIHPS dalam rentang ini.');
            }
            if ($runId !== null) {
                $counts = $import->store($runId, $report['prices'], implode("\n", $report['warnings']));
                Log::info('Sinkronisasi PIHPS selesai.', ['run_id' => $runId] + $counts);
                $this->info("Disimpan: {$counts['inserted']} baru, {$counts['updated']} direvisi, {$counts['unchanged']} tetap.");
            }

            return self::SUCCESS;
        } catch (ConnectionException|RequestException|UnexpectedValueException|QueryException $e) {
            $message = match (true) {
                $e instanceof QueryException => 'Penyimpanan gagal. Periksa koneksi MySQL dan migrasi.',
                $e instanceof RequestException => 'PIHPS mengembalikan HTTP '.$e->response->status().'.',
                $e instanceof ConnectionException => 'Koneksi ke PIHPS gagal.',
                default => $e->getMessage(),
            };
            $this->error($message);
            if (! $dryRun) {
                Log::error($message, ['run_id' => $runId]);
                if ($runId !== null) {
                    try {
                        DB::table('price_sync_runs')->where('id', $runId)->update([
                            'status' => 'failed', 'finished_at' => now(), 'message' => $message,
                        ]);
                    } catch (QueryException) {
                        $this->warn('Status kegagalan hanya tersedia di log Laravel.');
                    }
                }
            }

            return self::FAILURE;
        } finally {
            $lock->release();
        }
    }
}
