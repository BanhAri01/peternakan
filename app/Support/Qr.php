<?php

namespace App\Support;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

// Membuat gambar QR code (SVG) yang bisa langsung dipakai di <img src="...">, termasuk di PDF
class Qr
{
    public static function dataUri(string $text, int $size = 160): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));

        return 'data:image/svg+xml;base64,' . base64_encode($writer->writeString($text));
    }
}
