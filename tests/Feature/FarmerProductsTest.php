<?php

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FarmerProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_farmer_can_manage_listing_and_replace_photo(): void
    {
        Storage::fake('local');
        $farmer = User::factory()->create(['role' => Role::Petani, 'district' => 'Tabanan']);
        $this->actingAs($farmer)->get(route('petani.products.index'))->assertOk()->assertSee('Belum ada produk');
        $this->get(route('petani.products.create'))->assertOk()->assertSee('Simpan draf');

        $response = $this->post(route('petani.products.store'), $this->details() + [
            'photo' => UploadedFile::fake()->image('panen.jpg'),
            'status' => ProductStatus::Active->value,
            'farmer_id' => User::factory()->create()->id,
        ]);
        $product = Product::sole();
        $response->assertRedirect(route('petani.products.edit', $product));
        $this->assertSame($farmer->id, $product->farmer_id);
        $this->assertSame(ProductStatus::Draft, $product->status);
        $this->assertSame(25000, $product->price_per_kg);
        $this->assertSame('12.50', $product->stock_kg);
        $originalPhoto = $product->photo_path;
        Storage::disk('local')->assertExists($originalPhoto);
        $this->get(route('petani.products.edit', $product))->assertOk()->assertSee('Terbitkan produk');
        $this->get(route('petani.products.photo', $product))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $status = route('petani.products.status', $product);
        $this->patch($status, ['action' => 'sell_out'])->assertSessionHasErrors('action');
        $this->patch($status, ['action' => 'publish'])->assertSessionHasNoErrors();
        $this->assertSame(ProductStatus::Active, $product->refresh()->status);
        $this->patch($status, ['action' => 'sell_out'])->assertSessionHasNoErrors();
        $this->assertTrue($product->refresh()->isSoldOut());
        $this->get(route('petani.products.index'))->assertOk()->assertSee('Habis');

        $this->post(route('petani.products.update', $product), $this->details() + [
            '_method' => 'PATCH',
            'stock_before' => '0.00',
            'photo' => UploadedFile::fake()->image('panen-baru.png'),
        ])->assertSessionHasNoErrors();
        $this->assertFalse($product->refresh()->isSoldOut());
        Storage::disk('local')->assertMissing($originalPhoto);
        Storage::disk('local')->assertExists($product->photo_path);

        $this->patch($status, ['action' => 'archive'])->assertSessionHasNoErrors();
        $this->assertSame(ProductStatus::Archived, $product->refresh()->status);
        $this->patch($status, ['action' => 'publish'])->assertSessionHasNoErrors();
        $this->assertSame(ProductStatus::Active, $product->refresh()->status);

        Livewire::test('pages::settings.delete-user-modal')->set('password', 'password')->call('deleteUser')->assertHasNoErrors();
        $this->assertNull($product->fresh());
        Storage::disk('local')->assertMissing($product->photo_path);
    }

    public function test_other_accounts_cannot_access_a_farmers_listing_or_photo(): void
    {
        Storage::fake('local');
        $this->get(route('petani.products.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => Role::Pembeli]))->get(route('petani.products.index'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get(route('petani.products.index'))->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Role::Petani]))
            ->post(route('petani.products.store'), $this->details() + ['photo' => UploadedFile::fake()->image('panen.jpg')]);
        $product = Product::sole();

        $this->actingAs(User::factory()->create(['role' => Role::Petani]));
        $this->get(route('petani.products.index'))->assertOk()->assertDontSee($product->name);
        $this->get(route('petani.products.edit', $product))->assertForbidden();
        $this->get(route('petani.products.photo', $product))->assertForbidden();
        $this->patch(route('petani.products.update', $product), $this->details())->assertForbidden();
        $this->patch(route('petani.products.status', $product), ['action' => 'publish'])->assertForbidden();
    }

    public function test_invalid_product_fields_and_uploads_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => Role::Petani]));
        $store = route('petani.products.store');
        $this->from(route('petani.products.create'))->post($store, array_replace($this->details(), [
            'name' => ['invalid'],
            'price_per_kg' => '123.45',
            'stock_kg' => '-0,15',
            'harvested_on' => today()->addDay()->toDateString(),
            'district' => 'Jakarta',
        ]))->assertSessionHasErrors(['name', 'photo', 'price_per_kg', 'stock_kg', 'harvested_on', 'district']);
        $this->get(route('petani.products.create'))->assertOk();

        foreach ([
            UploadedFile::fake()->createWithContent('panen.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
            UploadedFile::fake()->image('panen.jpg')->size(2049),
        ] as $photo) {
            $this->post($store, $this->details() + ['photo' => $photo])->assertSessionHasErrors('photo');
        }

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_farmer_can_set_private_contact_and_default_district(): void
    {
        $farmer = User::factory()->create(['role' => Role::Petani]);
        $this->actingAs($farmer);
        Livewire::test('pages::settings.profile')
            ->set('district', 'Gianyar')
            ->set('transaction_contact', 'farmer@example.test')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();
        $this->assertSame('Gianyar', $farmer->refresh()->district);
        $this->assertSame('farmer@example.test', $farmer->transaction_contact);
        $this->assertArrayNotHasKey('transaction_contact', $farmer->toArray());
        $this->get(route('petani.products.create'))->assertOk()->assertSee('value="Gianyar" selected', false);
    }

    /** @return array<string, string> */
    private function details(): array
    {
        return [
            'name' => 'Bawang Merah Panen Tabanan',
            'commodity' => 'bawang-merah',
            'description' => 'Bawang merah hasil panen hari ini.',
            'price_per_kg' => '25000',
            'stock_kg' => '12,50',
            'harvested_on' => today()->toDateString(),
            'district' => 'Tabanan',
            'pickup_location' => 'Pasar Tabanan',
        ];
    }
}
