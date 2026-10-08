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

    'default_plan' => 'entrepreneur',

    'durations' => [
        1  => ['discount' => 0.0, 'label' => '1 bulan'],
        3  => ['discount' => 0.05, 'label' => '3 bulan'],
        6  => ['discount' => 0.10, 'label' => '6 bulan'],
        12 => ['discount' => 2 / 12, 'label' => '12 bulan (bayar 10)'],
    ],

    'tiers' => [
        'standar' => [
            'label'    => 'Standar',
            'tagline'  => 'Untuk peternakan kecil yang baru mulai mencatat',
            'price'    => (int) env('HEFAM_PRICE_STANDAR', 99000),
            'limits'   => ['coops' => 3, 'workers' => 3, 'devices' => 2, 'wa_monthly' => 0],
            'features' => [],
        ],
        'pro' => [
            'label'    => 'Pro',
            'tagline'  => 'Untuk peternakan dengan beberapa pekerja',
            'price'    => (int) env('HEFAM_PRICE_PRO', 199000),
            'limits'   => ['coops' => 10, 'workers' => 10, 'devices' => 5, 'wa_monthly' => 100],
            'features' => ['payroll', 'medicines', 'other_income', 'strain', 'export'],
        ],
        'entrepreneur' => [
            'label'    => 'Entrepreneur',
            'tagline'  => 'Untuk usaha peternakan yang terus berkembang',
            'price'    => (int) env('HEFAM_PRICE_ENTREPRENEUR', 349000),
            'limits'   => ['coops' => null, 'workers' => null, 'devices' => null, 'wa_monthly' => 300],
            'features' => ['payroll', 'medicines', 'other_income', 'strain', 'export', 'priority_support'],
        ],
    ],

    'features' => [
        'payroll'          => 'Gaji & absensi pekerja',
        'medicines'        => 'Stok obat & vitamin',
        'other_income'     => 'Pendapatan lain (afkir, kotoran)',
        'strain'           => 'Standar produksi strain',
        'export'           => 'Ekspor Excel',
        'priority_support' => 'Prioritas bantuan admin',
    ],
];
