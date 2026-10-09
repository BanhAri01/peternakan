<?php

namespace App\Http\Controllers;

class PwaController extends Controller
{
    public const OFFLINE_PAGES = ['/panen/input', '/sortir', '/pakan/beri'];

    private const ICONS = ['icon-192.png', 'icon-512.png', 'icon-maskable-512.png', 'apple-touch-icon.png'];

    public static function version(): string
    {
        static $version;

        return $version ??= substr(md5(
            md5_file(resource_path('pwa/sw.js')) . md5_file(resource_path('pwa/offline.js')) . md5_file(resource_path('views/pwa/offline.blade.php'))
        ), 0, 10);
    }

    public function manifest()
    {
        return response()->json([
            'name'             => 'HEFAM — Peternakan Ayam Petelur',
            'short_name'       => 'HEFAM',
            'description'      => 'Catat panen, sortir, penjualan, dan keuangan peternakan ayam petelur.',
            'lang'             => 'id',
            'start_url'        => '/',
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#f6f3ea',
            'theme_color'      => '#1f2b20',
            'icons'            => [
                ['src' => route('pwa.icon', 'icon-192.png', false), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => route('pwa.icon', 'icon-512.png', false), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => route('pwa.icon', 'icon-maskable-512.png', false), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts'        => [
                ['name' => 'Beri Pakan', 'url' => '/pakan/beri'],
                ['name' => 'Catat Panen', 'url' => '/panen/input'],
                ['name' => 'Sortir Telur', 'url' => '/sortir'],
            ],
        ], 200, [
            'Content-Type'  => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function serviceWorker()
    {
        $script = strtr(file_get_contents(resource_path('pwa/sw.js')), [
            '__VERSION__'       => self::version(),
            '__OFFLINE_PAGES__' => json_encode(self::OFFLINE_PAGES),
            '__OFFLINE_URL__'   => route('pwa.offline', [], false),
            '__ICON_URL__'      => route('pwa.icon', 'icon-192.png', false),
        ]);

        return response($script, 200, [
            'Content-Type'           => 'application/javascript; charset=utf-8',
            'Cache-Control'          => 'no-cache, no-store, must-revalidate',
            'Service-Worker-Allowed' => '/',
        ]);
    }

    public function script()
    {
        return response(file_get_contents(resource_path('pwa/offline.js')), 200, [
            'Content-Type'  => 'application/javascript; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function icon(string $name)
    {
        abort_unless(in_array($name, self::ICONS, true), 404);

        return response()->file(resource_path('pwa/icons/' . $name), [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=2592000, immutable',
        ]);
    }

    public function offline()
    {
        return response()->view('pwa.offline')->header('Cache-Control', 'no-cache');
    }
}
