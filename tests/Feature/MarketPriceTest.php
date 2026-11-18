<?php

namespace Tests\Feature;

use App\Services\PihpsBaliPrices;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarketPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_prices_and_invalid_filters(): void
    {
        $this->get('/')->assertOk()->assertSee('Belum ada harga dari PIHPS')->assertSee('Data contoh');
        $this->get('/harga?komoditas[]=tomat&periode=1000')->assertOk()
            ->assertViewHas('commodity', 'bawang-merah')->assertViewHas('days', 14)
            ->assertSee('Belum ada harga untuk Bawang Merah Ukuran Sedang')->assertDontSee('data-market-chart');
        $this->get('/harga?komoditas=cabai-merah&periode=30')
            ->assertRedirect(route('prices.index', ['komoditas' => 'cabai-merah-besar', 'periode' => 30]));
    }

    public function test_sparse_calendar_windows_are_anchored_to_latest_observation(): void
    {
        $last = CarbonImmutable::parse('2026-11-18');
        foreach ([0, 13, 14, 29, 30, 89, 90] as $offset) {
            $this->addPrice('cabai-rawit-merah', $last->subDays($offset)->toDateString(), 64750 - $offset);
        }
        $this->addPrice('bawang-merah', '2026-11-19', 33000);
        foreach ([14 => 2, 30 => 4, 90 => 6] as $days => $count) {
            $response = $this->get("/harga?komoditas=cabai-rawit-merah&periode=$days")->assertOk()
                ->assertSee('Rp64.750')->assertSee('Naik Rp13')->assertSee("$count pengamatan");
            preg_match("/data-market-chart='([^']+)'/", $response->getContent(), $match);
            $chart = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
            $this->assertCount($days, $chart);
            $this->assertNull($chart[$days - 2]['price']);
            $this->assertSame(64750, $chart[$days - 1]['price']);
        }
        $this->get('/')->assertSee('Rp64.750')->assertSee('Rp33.000');
    }

    public function test_one_observation_and_staleness_threshold_in_bali(): void
    {
        $this->addPrice('bawang-merah', '2026-11-10', 30000);
        $this->travelTo(CarbonImmutable::parse('2026-11-17 23:59', 'Asia/Makassar'));
        $this->get('/harga')->assertOk()->assertSee('Belum ada pengamatan pembanding')->assertViewHas('isStale', false);
        $this->travelTo(CarbonImmutable::parse('2026-11-18 00:01', 'Asia/Makassar'));
        $this->get('/harga')->assertSee('lebih dari tujuh hari kalender')->assertSee('1 pengamatan');
        $this->get('/')->assertSee('Data terakhir berusia lebih dari tujuh hari');
    }

    private function addPrice(string $slug, string $date, int $amount): void
    {
        DB::table('market_prices')->insert([
            'commodity' => $slug, 'province' => 'Bali', 'market_type' => 'tradisional',
            'observed_on' => $date, 'price_per_kg' => $amount, 'imported_at' => now(),
            'source_url' => PihpsBaliPrices::SOURCE_URL,
        ]);
    }
}
