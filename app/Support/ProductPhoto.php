<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductPhoto
{
    public static function response(Product $product): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($product->photo_path), 404);

        return $disk->response($product->photo_path, headers: [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
