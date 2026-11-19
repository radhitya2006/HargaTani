<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Support\BaliDistricts;
use App\Support\PriceCommodities;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? Gate::allows('update', $product)
            : Gate::allows('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        $stock = $this->input('stock_kg');
        if (is_string($stock)) {
            $this->merge(['stock_kg' => str_replace(',', '.', $stock)]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'commodity' => ['required', Rule::in(array_keys(PriceCommodities::ALL))],
            'description' => ['required', 'string', 'max:2000'],
            'photo' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
            'price_per_kg' => ['required', 'integer', 'min:1', 'max:9999999999'],
            'stock_kg' => ['required', 'regex:/\A[0-9]{1,8}(?:\.[0-9]{1,2})?\z/'],
            'harvested_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'district' => ['required', Rule::in(BaliDistricts::ALL)],
            'pickup_location' => ['required', 'string', 'max:250'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Masukkan nama produk.',
            'name.max' => 'Nama produk maksimal 120 karakter.',
            'commodity.*' => 'Pilih komoditas yang tersedia.',
            'description.required' => 'Masukkan deskripsi produk.',
            'description.max' => 'Deskripsi maksimal 2.000 karakter.',
            'photo.required' => 'Unggah satu foto produk.',
            'photo.image' => 'Foto harus berupa JPEG, PNG, atau WebP.',
            'photo.mimes' => 'Foto harus berupa JPEG, PNG, atau WebP.',
            'photo.extensions' => 'Foto harus berekstensi JPG, JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'price_per_kg.*' => 'Masukkan harga per kg dalam rupiah bulat dari 1 hingga 9.999.999.999.',
            'stock_kg.*' => 'Masukkan stok dari 0 hingga 99.999.999,99 kg dengan paling banyak dua angka desimal.',
            'harvested_on.*' => 'Pilih tanggal panen hari ini atau sebelumnya.',
            'district.*' => 'Pilih kabupaten atau kota di Bali.',
            'pickup_location.required' => 'Masukkan lokasi pengambilan.',
            'pickup_location.max' => 'Lokasi pengambilan maksimal 250 karakter.',
        ];
    }
}
