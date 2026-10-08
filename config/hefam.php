<?php

// Pengaturan platform HEFAM (SaaS)
return [
    // Nomor WhatsApp admin HEFAM untuk pertanyaan langganan (format 62xxxxxxxxxx)
    'admin_whatsapp' => env('HEFAM_ADMIN_WA', ''),

    'backup' => [
        'path'        => env('HEFAM_BACKUP_PATH', storage_path('app/backups')),
        'keep_days'   => (int) env('HEFAM_BACKUP_KEEP_DAYS', 30),
        'keep_min'    => 7,
        'mysqldump'   => env('HEFAM_MYSQLDUMP', 'mysqldump'),
        'stale_hours' => 36,
        'connection'  => env('HEFAM_BACKUP_CONNECTION'),
    ],

    'whatsapp' => [
        'driver'        => env('HEFAM_WA_DRIVER', 'log'),
        'fonnte_token'  => env('FONNTE_TOKEN'),
        'fonnte_url'    => env('FONNTE_URL', 'https://api.fonnte.com/send'),
        'morning_at'    => ['06:00', '07:00'],
        'evening_at'    => ['17:00', '18:00'],
    ],

    'plans' => [
        1  => (int) env('HEFAM_PRICE_1', 150000),
        3  => (int) env('HEFAM_PRICE_3', 420000),
        6  => (int) env('HEFAM_PRICE_6', 800000),
        12 => (int) env('HEFAM_PRICE_12', 1500000),
    ],
];
