<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Services\PihpsBaliPrices;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_visit_the_summary_dashboard(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk()->assertSeeText('Ringkasan Admin')
            ->assertSeeText('Belum ada sinkronisasi yang berhasil.')
            ->assertSeeText('Belum ada riwayat sinkronisasi.');
    }

    public function test_petani_go_straight_to_their_dashboard_from_the_public_site(): void
    {
        $petani = User::factory()->create(['role' => Role::Petani]);
        $this->actingAs($petani);

        $this->get(route('home'))->assertOk()->assertSee('href="'.route('dashboard').'"', false);
        $this->get(route('dashboard'))->assertRedirect(route('petani.dashboard'));
        $this->get(route('petani.dashboard'))->assertOk()
            ->assertSee('href="'.route('home').'"', false)
            ->assertSeeText('Kembali ke Beranda')
            ->assertSeeText('Belum ada produk aktif atau draf.')
            ->assertSee(route('petani.products.create'))
            ->assertSeeText('Kalkulator Harga Jual')
            ->assertDontSeeText('Ringkasan');
    }

    public function test_pembeli_go_straight_to_their_dashboard_with_catalog_navigation(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Pembeli]));

        $this->get(route('home'))->assertOk()->assertSee('href="'.route('dashboard').'"', false);
        $this->get(route('dashboard'))->assertRedirect(route('pembeli.dashboard'));
        $this->get(route('pembeli.dashboard'))->assertOk()
            ->assertSeeText('Dashboard Pembeli')
            ->assertSeeText('Kembali ke Beranda')
            ->assertSeeText('Belum ada hasil panen dengan stok tersedia.')
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('href="'.route('profile.edit').'"', false)
            ->assertDontSeeText('Ringkasan')
            ->assertDontSeeText('Kalkulator Harga Jual')
            ->assertDontSee('href="'.route('calculator.show').'"', false);
    }

    public function test_farmer_metrics_and_recent_products_are_limited_to_their_own_active_products_and_drafts(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        $otherFarmer = User::factory()->create(['role' => Role::Petani]);
        $latest = $this->product($farmer, 'Produk yang baru diedit');
        $this->product($farmer, 'Produk habis', stock: '0.00');
        $this->product($farmer, 'Produk draf', ProductStatus::Draft, '0.00');
        $this->product($farmer, 'Produk tersedia A');
        $this->product($farmer, 'Tidak muat di daftar', ProductStatus::Draft)->forceFill(['updated_at' => now()->subDay()])->save();
        $this->product($farmer, 'Produk tersedia B');
        $this->product($farmer, 'Produk arsip', ProductStatus::Archived);
        $this->product($otherFarmer, 'Produk milik petani lain');
        $latest->forceFill(['updated_at' => now()->addMinute()])->save();

        $this->actingAs($farmer)->get(route('petani.dashboard'))->assertOk()
            ->assertViewHas('metrics', ['Tayang dengan stok' => 3, 'Draf' => 2, 'Tayang, stok habis' => 1])
            ->assertSeeTextInOrder(['Produk yang baru diedit', 'Produk tersedia B', 'Produk tersedia A', 'Produk draf', 'Produk habis'])
            ->assertSee(route('petani.products.edit', $latest))
            ->assertDontSeeText('Tidak muat di daftar')
            ->assertDontSeeText('Produk arsip')
            ->assertDontSeeText('Produk milik petani lain')
            ->assertDontSeeText('Sinkronisasi PIHPS');
    }

    public function test_buyer_dashboard_shows_only_the_three_newest_available_public_listings(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        $inactiveFarmer = User::factory()->create(['role' => Role::Petani, 'is_active' => false]);
        foreach (range(1, 4) as $number) {
            $this->product($farmer, 'Penawaran nomor '.$number);
        }
        $this->product($farmer, 'Penawaran tanpa stok', stock: '0.00');
        $this->product($farmer, 'Penawaran draf', ProductStatus::Draft);
        $this->product($farmer, 'Penawaran arsip', ProductStatus::Archived);
        $this->product($inactiveFarmer, 'Penawaran petani nonaktif');

        $this->actingAs(User::factory()->create(['role' => Role::Pembeli]))->get(route('pembeli.dashboard'))->assertOk()
            ->assertSeeTextInOrder(['Penawaran nomor 4', 'Penawaran nomor 3', 'Penawaran nomor 2'])
            ->assertDontSeeText('Penawaran nomor 1')
            ->assertDontSeeText('Penawaran tanpa stok')
            ->assertDontSeeText('Penawaran draf')
            ->assertDontSeeText('Penawaran arsip')
            ->assertDontSeeText('Penawaran petani nonaktif')
            ->assertDontSeeText('Sinkronisasi PIHPS');
    }

    public function test_market_cards_show_previous_observations_and_mark_missing_or_old_prices_for_both_roles(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-19 16:00', 'Asia/Makassar'));
        foreach ([['bawang-merah', '2026-11-19', 32000], ['bawang-merah', '2026-11-01', 30000], ['cabai-merah-besar', '2026-11-10', 39000]] as [$commodity, $date, $price]) {
            DB::table('market_prices')->insert([
                'commodity' => $commodity, 'province' => 'Bali', 'market_type' => 'tradisional',
                'observed_on' => $date, 'price_per_kg' => $price, 'imported_at' => now(),
                'source_url' => PihpsBaliPrices::SOURCE_URL,
            ]);
        }

        foreach ([Role::Petani, Role::Pembeli] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route($role->value.'.dashboard'))->assertOk()
                ->assertSeeText('Harga Pasar Bali')->assertSeeText('Rp32.000')->assertSeeText('Naik Rp2.000')
                ->assertSeeText('19 November 2026')->assertSeeText('Belum ada pengamatan pembanding.')
                ->assertSeeText('Data terakhir berusia lebih dari tujuh hari.')
                ->assertSeeText('Belum ada harga dari PIHPS.')
                ->assertSee(route('prices.index', ['komoditas' => 'cabai-rawit-merah']));
        }
    }

    public function test_both_admin_routes_show_recent_failures_and_last_success_even_outside_the_history_limit(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        $inactiveFarmer = User::factory()->create(['role' => Role::Petani, 'is_active' => false]);
        User::factory()->create(['role' => Role::Pembeli]);
        User::factory()->create(['role' => Role::Pembeli, 'is_active' => false]);
        $this->product($farmer, 'Penawaran katalog', stock: '0.00');
        $this->product($farmer, 'Draf admin', ProductStatus::Draft);
        $this->product($inactiveFarmer, 'Penawaran tersembunyi');

        $range = ['range_start' => '2026-11-10', 'range_end' => '2026-11-18'];
        DB::table('price_sync_runs')->insert($range + [
            'status' => 'success', 'started_at' => '2026-11-18 15:00:00', 'finished_at' => '2026-11-18 15:05:00',
            'inserted' => 6, 'updated' => 2,
        ]);
        foreach (range(0, 4) as $minute) {
            DB::table('price_sync_runs')->insert($range + [
                'status' => 'failed', 'started_at' => "2026-11-19 15:0$minute:00", 'finished_at' => "2026-11-19 15:0$minute:30",
            ]);
        }

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        foreach (['dashboard', 'admin.dashboard'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertViewHas('metrics', ['Petani aktif' => 1, 'Pembeli aktif' => 1, 'Produk tayang' => 1])
                ->assertViewHas('syncRuns', fn ($runs) => $runs->count() === 5 && $runs->first()->status === 'failed')
                ->assertSeeText('Gagal')->assertSeeText('Harga baru')->assertSeeText('Direvisi')
                ->assertSeeText('18 November 2026, 15.05 WITA')
                ->assertDontSeeText('18 November 2026, 15.00 WITA');
        }
    }

    private function product(User $farmer, string $name, ProductStatus $status = ProductStatus::Active, string $stock = '12.50'): Product
    {
        $product = new Product([
            'name' => $name,
            'commodity' => 'bawang-merah',
            'description' => 'Hasil panen Tabanan.',
            'price_per_kg' => 32000,
            'stock_kg' => $stock,
            'harvested_on' => today()->toDateString(),
            'district' => 'Tabanan',
            'pickup_location' => 'Pasar Tabanan',
        ]);
        $product->farmer_id = $farmer->id;
        $product->photo_path = Product::photoDirectory($farmer->id).'/panen.jpg';
        $product->status = $status;
        $product->saveOrFail();

        return $product;
    }
}
