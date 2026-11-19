<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_listings_from_active_farmers_and_their_photos_are_public(): void
    {
        Storage::fake('local');
        $farmer = User::factory()->create([
            'role' => Role::Petani, 'name' => 'Petani Tabanan', 'district' => 'Tabanan',
            'email' => 'account@example.test', 'transaction_contact' => 'private@example.test',
        ]);
        $inactive = User::factory()->create(['role' => Role::Petani, 'is_active' => false]);
        $listing = $this->listing($farmer, 'Bawang Panen Pagi', ['stock_kg' => '0.00']);
        $hidden = [
            $this->listing($farmer, 'Produk Draf', status: ProductStatus::Draft),
            $this->listing($farmer, 'Produk Arsip', status: ProductStatus::Archived),
            $this->listing($inactive, 'Produk Akun Nonaktif'),
        ];
        $path = UploadedFile::fake()->image('panen.jpg')->storeAs(dirname($listing->photo_path), basename($listing->photo_path), 'local');
        $this->assertSame($listing->photo_path, $path);
        foreach ($hidden as $product) {
            UploadedFile::fake()->image('panen.jpg')->storeAs(dirname($product->photo_path), basename($product->photo_path), 'local');
        }

        foreach ([route('home'), route('catalog.index')] as $page) {
            $response = $this->get($page)->assertOk()->assertSee($listing->name)->assertSee('Habis')
                ->assertSee(route('catalog.index'))->assertDontSee('Cabai Merah Segar');
            foreach ($hidden as $product) {
                $response->assertDontSee($product->name);
            }
        }

        $details = route('catalog.show', $listing);
        $photo = route('catalog.photo', $listing);
        $this->get($details)->assertOk()->assertSee('Stok habis')->assertSee('Rp32.000')->assertSee('Petani Tabanan')
            ->assertSee('Tanggal panen')->assertSee('Pasar Tabanan')->assertSee($photo)
            ->assertDontSee('account@example.test')->assertDontSee('private@example.test');
        $this->get($photo)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Content-Type-Options', 'nosniff');
        foreach ($hidden as $product) {
            $this->get(route('catalog.show', $product))->assertNotFound();
            $this->get(route('catalog.photo', $product))->assertNotFound();
        }

        $listing->status = ProductStatus::Archived;
        $listing->save();
        $this->get($details)->assertNotFound();
        $this->get($photo)->assertNotFound();
    }

    public function test_catalog_combines_search_commodity_and_district_filters(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        $this->listing($farmer, 'Cabai Merah Besar 100% Pilihan', ['commodity' => 'cabai-merah-besar']);
        $this->listing($farmer, 'Cabai Merah Besar 100 Pedas', ['commodity' => 'cabai-merah-besar']);
        $this->listing($farmer, 'Cabai Merah Besar 100% Bangli', ['commodity' => 'cabai-merah-besar', 'district' => 'Bangli']);
        $this->listing($farmer, 'Cabai Rawit 100% Pilihan', ['commodity' => 'cabai-rawit-merah']);

        $this->get(route('catalog.index', ['q' => '  cabai  ', 'komoditas' => 'cabai-merah-besar', 'lokasi' => 'Tabanan']))
            ->assertOk()->assertSee('Cabai Merah Besar 100% Pilihan')->assertSee('Cabai Merah Besar 100 Pedas')
            ->assertDontSee('Cabai Merah Besar 100% Bangli')->assertDontSee('Cabai Rawit 100% Pilihan');
        $this->get(route('catalog.index', ['q' => '100%', 'komoditas' => 'cabai-merah-besar', 'lokasi' => 'Tabanan']))
            ->assertOk()->assertSee('Cabai Merah Besar 100% Pilihan')->assertDontSee('Cabai Merah Besar 100 Pedas');
        $this->get(route('catalog.index', ['q' => 'sirih']))->assertOk()->assertSee('Produk tidak ditemukan');
        $this->get('/hasil-panen?q[]=cabai&komoditas[]=cabai&lokasi=Jakarta')->assertOk()->assertSee('4 produk');
    }

    public function test_catalog_pagination_keeps_filters_and_buyer_dashboard_links_to_it(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        foreach (range(1, 13) as $number) {
            $this->listing($farmer, sprintf('Bawang Tabanan %02d', $number));
        }

        $filters = ['q' => 'Bawang', 'komoditas' => 'bawang-merah', 'lokasi' => 'Tabanan'];
        $pageTwo = route('catalog.index', $filters + ['page' => 2]);
        $this->get(route('catalog.index', $filters))->assertOk()->assertSee('13 produk')->assertSee($pageTwo)
            ->assertSee('Bawang Tabanan 13')->assertDontSee('Bawang Tabanan 01');
        $this->get($pageTwo)->assertOk()->assertSee('Bawang Tabanan 01')->assertDontSee('Bawang Tabanan 13');
        $this->get(route('catalog.index', $filters + ['page' => 99]))->assertOk()->assertSee('Halaman ini kosong')
            ->assertSee(route('catalog.index', $filters + ['page' => 1]));

        $this->actingAs(User::factory()->create(['role' => Role::Pembeli]))
            ->get(route('pembeli.dashboard'))->assertOk()->assertSee('Jelajahi Hasil Panen')->assertSee(route('catalog.index'));
    }

    /** @param array<string, string> $fields */
    private function listing(User $farmer, string $name, array $fields = [], ProductStatus $status = ProductStatus::Active): Product
    {
        $listing = new Product($fields + [
            'name' => $name,
            'commodity' => 'bawang-merah',
            'description' => 'Hasil panen dijual per kilogram.',
            'price_per_kg' => 32000,
            'stock_kg' => '12.50',
            'harvested_on' => today()->toDateString(),
            'district' => 'Tabanan',
            'pickup_location' => 'Pasar Tabanan',
        ]);
        $listing->farmer_id = $farmer->id;
        $listing->photo_path = Product::photoDirectory($farmer->id).'/'.str()->slug($name).'.jpg';
        $listing->status = $status;
        $listing->saveOrFail();

        return $listing;
    }
}
