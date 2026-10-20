<?php

namespace App\Services;

use App\Models\Product;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductQrCodeService
{
    public function publicUrl(Product $product): string
    {
        return route('public.products.show', [
            'business' => $product->business_id,
            'product' => $product->id,
        ]);
    }

    public function generate(Product $product): string
    {
        $product->loadMissing('business');

        $qrCode = new QrCode(
            data: $this->publicUrl($product),
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 500,
            margin: 15
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        $path = "businesses/{$product->business_id}/products/{$product->id}/qr-code.png";

        $saved = Storage::disk('public')->put(
            $path,
            $result->getString()
        );

        if (!$saved) {
            throw new RuntimeException(
                'Could not save product QR code.'
            );
        }

        // Save path without triggering unrelated Product updates.
        $product->forceFill([
            'qr_code' => $path,
        ])->save();

        return $path;
    }

    public function ensure(Product $product): string
    {
        if (
            $product->qr_code
            && Storage::disk('public')->exists($product->qr_code)
        ) {
            return $product->qr_code;
        }

        return $this->generate($product);
    }
}
