<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\PihpsBaliPrices;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellingPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_page_links_to_the_calculator_for_the_selected_commodity(): void
    {
        $this->get('/harga?komoditas=cabai-rawit-merah')->assertOk()
            ->assertSee(route('calculator.show', ['komoditas' => 'cabai-rawit-merah']));

        $this->get('/kalkulator?komoditas=cabai-rawit-merah')->assertOk()
            ->assertSee('value="cabai-rawit-merah" selected', false);
    }

    public function test_buyers_do_not_see_selling_price_tools_and_cannot_use_the_calculator(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Pembeli]));

        $this->get(route('home'))->assertOk()
            ->assertSeeText('Periksa detail produk')
            ->assertDontSeeText('Hitung harga yang sesuai')
            ->assertDontSeeText('Buka kalkulator')
            ->assertDontSee(route('calculator.show'));
        $this->get(route('prices.index', ['komoditas' => 'cabai-rawit-merah']))->assertOk()
            ->assertDontSeeText('Hitung harga jual')
            ->assertDontSee(route('calculator.show'));
        $this->get(route('catalog.index'))->assertOk()->assertDontSee(route('calculator.show'));
        $this->get(route('calculator.show'))->assertForbidden();
        $this->post(route('calculator.calculate'))->assertForbidden();
    }

    public function test_farmers_can_open_and_use_the_calculator(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Petani]));

        $this->get(route('home'))->assertOk()->assertSeeText('Buka kalkulator');
        $this->get(route('calculator.show'))->assertOk();
        $this->post(route('calculator.calculate'), [
            'komoditas' => 'bawang-merah',
            'biaya_panen' => '10000',
            'jumlah_kg' => '1',
            'tambahan_keuntungan' => '0',
        ])->assertOk()->assertSee('Rp10.000');
    }

    public function test_suggested_price_compares_with_the_latest_bali_retail_observation(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-18 12:00', 'Asia/Makassar'));
        $observation = [
            'commodity' => 'cabai-rawit-merah', 'province' => 'Bali', 'market_type' => 'tradisional',
            'observed_on' => '2026-11-10', 'price_per_kg' => 40000, 'imported_at' => now(),
            'source_url' => PihpsBaliPrices::SOURCE_URL,
        ];
        DB::table('market_prices')->insert([
            $observation,
            [...$observation, 'observed_on' => '2026-11-09', 'price_per_kg' => 35000],
            [...$observation, 'observed_on' => '2026-11-18', 'price_per_kg' => 75000, 'province' => 'Jawa Timur'],
            [...$observation, 'observed_on' => '2026-11-18', 'price_per_kg' => 85000, 'commodity' => 'bawang-merah'],
        ]);

        $response = $this->post('/kalkulator', [
            'komoditas' => 'cabai-rawit-merah',
            'biaya_panen' => '1510000',
            'jumlah_kg' => '50,50',
            'tambahan_keuntungan' => '12,50',
        ])->assertOk()->assertSee('Rp29.901')->assertSee('Rp33.639')->assertSee('Rp40.000')
            ->assertSee('Rp6.361/kg lebih rendah')->assertSee('10 November 2026')
            ->assertSee('Data terakhir berusia lebih dari tujuh hari kalender.')
            ->assertSee('value="33639"', false)->assertSessionMissing('_old_input');

        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public function test_calculation_works_without_a_market_reference_at_zero_cost_and_markup(): void
    {
        $this->post('/kalkulator', [
            'komoditas' => 'bawang-merah',
            'biaya_panen' => '0',
            'jumlah_kg' => '0.01',
            'tambahan_keuntungan' => '0',
        ])->assertOk()->assertViewHas('result', ['costPerKg' => 0, 'suggestedPrice' => 0])
            ->assertSee('Belum ada harga referensi untuk komoditas ini.')
            ->assertDontSee('dari harga referensi');
    }

    public function test_invalid_inputs_keep_form_values_and_do_not_produce_a_price(): void
    {
        $this->post('/kalkulator', [
            'komoditas' => ['bawang-merah'],
            'biaya_panen' => '-5000',
            'jumlah_kg' => '0,00',
            'tambahan_keuntungan' => '-20',
        ])->assertUnprocessable()->assertViewHas('result', null)
            ->assertSee('Pilih komoditas yang tersedia.')
            ->assertSee('Masukkan rupiah bulat dari 0')
            ->assertSee('Jumlah panen layak jual harus lebih dari 0 kg.')
            ->assertSee('Masukkan persentase dari 0')
            ->assertSee('value="0,00"', false)
            ->assertSessionMissing('_old_input');

        foreach (['-1', '1,234'] as $invalidWeight) {
            $this->post('/kalkulator', [
                'komoditas' => 'bawang-merah',
                'biaya_panen' => '5000',
                'jumlah_kg' => $invalidWeight,
                'tambahan_keuntungan' => '0',
            ])->assertUnprocessable()->assertSee('Masukkan jumlah yang positif');
        }
    }
}
