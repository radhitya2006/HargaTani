<?php

namespace App\Services;

use App\Support\PriceCommodities;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Throwable;
use UnexpectedValueException;

final class PihpsBaliPrices
{
    public const PROVINCE = 'Bali';

    public const MARKET_TYPE = 'tradisional';

    public const SOURCE_URL = 'https://www.bi.go.id/hargapangan/TabelHarga/PasarTradisionalDaerah';

    public const ENDPOINT = 'https://www.bi.go.id/hargapangan/WebSite/TabelHarga/GetGridDataDaerah';

    /**
     * @return array{prices: array<string, array<string, int>>, warnings: list<string>}
     */
    public function fetch(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $prices = array_fill_keys(array_keys(PriceCommodities::ALL), []);
        $warnings = [];

        for ($start = $from; $start <= $to; $start = $end->addDay()) {
            $end = $start->addDays(6)->min($to);
            if ($start > $from) {
                Sleep::for(1)->second();
            }

            $data = Http::acceptJson()->connectTimeout(5)->timeout(15)
                ->retry(2, 1000, static fn (Throwable $e): bool => $e instanceof ConnectionException
                    || ($e instanceof RequestException && $e->response->serverError()))
                ->get(self::ENDPOINT, [
                    'price_type_id' => 1, 'comcat_id' => '', 'province_id' => 17,
                    'regency_id' => '', 'market_id' => '', 'tipe_laporan' => 1,
                    'start_date' => $start->toDateString(), 'end_date' => $end->toDateString(),
                ])->throw()->json('data');

            if (! is_array($data) || ! array_is_list($data)) {
                throw new UnexpectedValueException('Format respons PIHPS berubah atau data tidak tersedia.');
            }

            $names = array_column(PriceCommodities::ALL, 'name');
            $slugs = array_combine($names, array_keys(PriceCommodities::ALL));
            $seen = [];
            foreach ($data as $row) {
                if (! is_array($row) || ! in_array($row['level'] ?? null, [2, '2'], true)
                    || ! is_string($row['name'] ?? null) || ! isset($slugs[$row['name']])) {
                    continue;
                }
                $slug = $slugs[$row['name']];
                if (isset($seen[$slug])) {
                    throw new UnexpectedValueException("Baris komoditas ganda: $slug.");
                }
                $observations = $this->parseRow($row, $start, $end);
                $seen[$slug] = count($observations);
                $prices[$slug] += $observations;
            }
            foreach (PriceCommodities::ALL as $slug => $item) {
                if (empty($seen[$slug])) {
                    $warnings[] = "{$item['name']}: tanpa pengamatan {$start->toDateString()} hingga {$end->toDateString()}.";
                }
            }
        }

        return ['prices' => $prices, 'warnings' => $warnings];
    }

    /** @param array<array-key, mixed> $row
     * @return array<string, int>
     */
    private function parseRow(array $row, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $observations = [];
        foreach ($row as $key => $value) {
            if (in_array($key, ['name', 'level', 'no', 'id'], true)) {
                continue;
            }
            if (! is_string($key) || ! preg_match('/\A(\d{2})\/(\d{2})\/(\d{4})\z/', $key, $date)
                || ! checkdate((int) $date[2], (int) $date[1], (int) $date[3])) {
                throw new UnexpectedValueException("Tanggal pengamatan PIHPS tidak valid ($key).");
            }
            $isoDate = "$date[3]-$date[2]-$date[1]";
            if ($isoDate < $start->toDateString() || $isoDate > $end->toDateString()) {
                throw new UnexpectedValueException("Tanggal PIHPS di luar rentang permintaan ($key).");
            }
            $value = is_string($value) ? trim($value) : $value;
            if (in_array($value, [null, '', '-', '–', '—'], true)) {
                continue;
            }
            if ((! is_string($value) && ! is_int($value))
                || ! preg_match('/\A(?:\d{1,9}|\d{1,3}(?:,\d{3}){1,2})\z/', (string) $value)) {
                throw new UnexpectedValueException("Format harga PIHPS tidak valid ($key).");
            }
            $price = (int) str_replace(',', '', (string) $value);
            if ($price > 0) {
                $observations[$isoDate] = $price;
            }
        }
        ksort($observations);

        return $observations;
    }
}
