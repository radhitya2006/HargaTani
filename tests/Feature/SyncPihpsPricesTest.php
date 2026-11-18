<?php

namespace Tests\Feature;

use App\Services\MarketPriceImport;
use App\Services\PihpsBaliPrices;
use App\Support\PriceCommodities;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SyncPihpsPricesTest extends TestCase
{
    use RefreshDatabase;

    private const RANGE = ['--from' => '2026-11-13', '--to' => '2026-11-18'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00', 'Asia/Makassar'));
        config(['cache.stores.file.lock_path' => storage_path('framework/cache/testing-locks')]);
        Sleep::fake();
        Http::preventStrayRequests();
    }

    public function test_repeated_import_only_audits_source_changes(): void
    {
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::sequence()
            ->push($this->report())->push($this->report())->push($this->report('32,000'))]);
        foreach ([1, 2, 3] as $run) {
            $this->artisan('pihps:sync', self::RANGE)->assertSuccessful();
        }
        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET' && $query['province_id'] === '17'
                && $query['comcat_id'] === '' && $query['price_type_id'] === '1';
        });
        $this->assertDatabaseCount('market_prices', 4);
        $this->assertDatabaseCount('market_price_revisions', 1);
        $run = DB::table('price_sync_runs')->orderByDesc('id')->first();
        $price = DB::table('market_prices')->where('commodity', 'bawang-merah')->where('observed_on', '2026-11-18')->first();
        $this->assertSame(32000, (int) $price->price_per_kg);
        $this->assertDatabaseHas('market_price_revisions', ['market_price_id' => $price->id, 'price_sync_run_id' => $run->id, 'old_price' => 31750, 'new_price' => 32000]);
        $this->assertDatabaseHas('price_sync_runs', ['inserted' => 0, 'updated' => 0, 'unchanged' => 4, 'status' => 'success']);
        $this->assertDatabaseHas('price_sync_runs', ['id' => $run->id, 'updated' => 1, 'unchanged' => 3]);
        $this->get('/')->assertOk()->assertSee('Rp32.000')->assertSee('Rp64.750');
        $this->get('/harga')->assertOk()->assertSee('Rp32.000')->assertSee('Naik Rp2.200');
    }

    public function test_partial_coverage_and_dry_run(): void
    {
        $report = $this->report();
        array_pop($report['data']);
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::response($report)]);
        $this->artisan('pihps:sync', self::RANGE + ['--dry-run' => true])
            ->expectsOutputToContain('Rp31.750')->expectsOutputToContain('Cabai Rawit Merah: tanpa pengamatan')->assertSuccessful();
        $this->assertDatabaseCount('market_prices', 0);
        $this->assertDatabaseCount('price_sync_runs', 0);
        $this->artisan('pihps:sync', self::RANGE)->assertSuccessful();
        $this->assertDatabaseCount('market_prices', 3);
    }

    public function test_empty_report_is_recorded_as_failed(): void
    {
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::response(['data' => []])]);
        $this->artisan('pihps:sync', self::RANGE)->assertFailed();
        $this->assertDatabaseHas('price_sync_runs', ['status' => 'failed']);
    }

    public static function invalidValues(): array
    {
        return [
            ['18/11/2026', '31,75'], ['18/11/2026', -1], ['31/11/2026', '31,000'],
            ['12/11/2026', 10000], ['2026-11-18', 10000],
        ];
    }

    #[DataProvider('invalidValues')]
    public function test_bad_value_rejects_the_import(string $date, mixed $amount): void
    {
        $report = $this->report();
        $report['data'][3][$date] = $amount;
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::response($report)]);
        $this->artisan('pihps:sync', self::RANGE)->assertFailed();
        $this->assertDatabaseCount('market_prices', 0);
        $this->assertDatabaseHas('price_sync_runs', ['status' => 'failed']);
    }

    public function test_duplicate_source_row_is_rejected(): void
    {
        $report = $this->report();
        $report['data'][] = $report['data'][1];
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::response($report)]);
        $this->artisan('pihps:sync', self::RANGE)->assertFailed();
        $this->assertDatabaseCount('market_prices', 0);
    }

    public function test_default_window_and_failed_second_chunk_leave_prices_intact(): void
    {
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::sequence()
            ->push($this->report())->push(['data' => [['name' => 'Cabai Merah Besar', 'level' => 2, '10/11/2026' => 20000]]])
            ->pushStatus(503)->pushStatus(503)]);
        $this->artisan('pihps:sync', self::RANGE)->assertSuccessful();
        $this->artisan('pihps:sync', ['--to' => '2026-11-18'])->assertFailed();
        Http::assertSentCount(4);
        $this->assertDatabaseCount('market_prices', 4);
        $this->assertDatabaseHas('price_sync_runs', ['range_start' => '2026-11-10', 'range_end' => '2026-11-18', 'status' => 'failed']);
    }

    public function test_ninety_day_backfill_requests_contiguous_chunks(): void
    {
        $next = CarbonImmutable::parse('2026-08-21');
        Http::fake(function (Request $request) use (&$next) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $end = $next->addDays(6)->min(CarbonImmutable::parse('2026-11-18'));
            $this->assertSame($next->toDateString(), $query['start_date']);
            $this->assertSame($end->toDateString(), $query['end_date']);
            $next = $end->addDay();

            return Http::response(['data' => [['name' => PriceCommodities::ALL['cabai-merah-besar']['name'], 'level' => 2, $end->format('d/m/Y') => 10000]]]);
        });
        $this->artisan('pihps:sync', ['--from' => '2026-08-21'])->assertSuccessful();
        Http::assertSentCount(13);
        $this->assertDatabaseCount('market_prices', 13);
        $this->assertSame('2026-11-19', $next->toDateString());
    }

    public function test_invalid_ranges_and_overlap_make_no_requests(): void
    {
        Http::fake();
        foreach ([['--from' => '2026-08-20'], ['--to' => '2026-11-19'], ['--from' => '2026-11-31']] as $options) {
            $this->artisan('pihps:sync', $options)->assertExitCode(2);
        }
        $lock = Cache::store('file')->lock('pihps-bali-sync', 60);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('pihps:sync')->expectsOutputToContain('sedang berjalan')->assertFailed();
        } finally {
            $lock->release();
        }
        Http::assertNothingSent();
        $this->assertDatabaseCount('price_sync_runs', 0);
    }

    public function test_storage_failure_rolls_back_price_and_revision_writes(): void
    {
        Http::fake([PihpsBaliPrices::ENDPOINT.'*' => Http::response($this->report())]);
        $this->artisan('pihps:sync', self::RANGE)->assertSuccessful();
        try {
            // An invalid audit reference makes MySQL reject the revision after the new observation.
            app(MarketPriceImport::class)->store(0, [
                'bawang-merah' => ['2026-11-17' => 28000],
                'cabai-merah-besar' => ['2026-11-18' => 40000],
            ], '');
            $this->fail('MySQL seharusnya menolak referensi audit yang tidak valid.');
        } catch (QueryException $exception) {
            $this->assertEquals('23000', $exception->getCode());
        }
        $this->assertDatabaseCount('market_prices', 4);
        $this->assertDatabaseCount('market_price_revisions', 0);
        $this->assertDatabaseHas('market_prices', ['commodity' => 'cabai-merah-besar', 'price_per_kg' => 39550]);
    }

    private function report(string $latest = '31,750'): array
    {
        return ['data' => [
            ['name' => 'Bawang Merah', 'level' => 1, '18/11/2026' => '95,000'],
            ['no' => 1, 'name' => 'Bawang Merah Ukuran Sedang', 'level' => '2',
                '13/11/2026' => '29,800', '14/11/2026' => '—', '15/11/2026' => '–', '16/11/2026' => null,
                '17/11/2026' => 0, '18/11/2026' => $latest],
            ['name' => 'Cabai Merah Besar', 'level' => 2, '18/11/2026' => '39,550', '14/11/2026' => '-'],
            ['name' => 'Cabai Rawit Merah', 'level' => 2, '18/11/2026' => 64750, '14/11/2026' => ''],
        ]];
    }
}
