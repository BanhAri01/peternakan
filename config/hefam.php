<?php

// Pengaturan platform HEFAM (SaaS)
return [
    // Nomor WhatsApp admin HEFAM untuk pertanyaan langganan (format 62xxxxxxxxxx)
    'admin_whatsapp' => env('HEFAM_ADMIN_WA', ''),

    'business' => [
        'brand'   => 'HEFAM',
        'name'    => env('HEFAM_BUSINESS_NAME', 'Sinar Abadi Farm'),
        'owner'   => env('HEFAM_OWNER_NAME', ''),
        'address' => env('HEFAM_ADDRESS', 'Jl. Yudistira, Peninjoan, Kec. Tembuku, Kabupaten Bangli, Bali 80671'),
        'email'   => env('HEFAM_CONTACT_EMAIL', 'idewamadearisantika@gmail.com'),
        'hours'   => env('HEFAM_SUPPORT_HOURS', 'Senin - Sabtu, 08.00 - 20.00 WITA'),
        'updated' => '9 Oktober 2026',
    ],

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

    'assistant' => [
        'api_key'        => env('ANTHROPIC_API_KEY'),
        'timeout'        => (float) env('HEFAM_AI_TIMEOUT', 60),
        'webhook_secret' => env('HEFAM_AI_WEBHOOK_SECRET'),
        'wa_device'      => env('HEFAM_AI_WA_DEVICE'),
        'usd_rate'       => (float) env('HEFAM_AI_USD_RATE', 16500),
        'budget'         => (int) env('HEFAM_AI_BUDGET', 25000),
        'trial_budget'   => (int) env('HEFAM_AI_TRIAL_BUDGET', 5000),
        'daily_share'    => 0.2,
        'topup_amount'   => (int) env('HEFAM_AI_TOPUP', 20000),
        'max_rounds'     => 6,
        'history'        => 6,
        'history_hours'  => 3,
        'question_max'   => 500,
        'question_estimate' => 40,
        'modes' => [
            'quick' => [
                'model'      => env('HEFAM_AI_QUICK_MODEL', 'claude-haiku-5-5'),
                'effort'     => 'low',
                'max_tokens' => 4000,
                'reserve'    => 200,
            ],
            'deep' => [
                'model'      => env('HEFAM_AI_DEEP_MODEL', 'claude-haiku-5-5'),
                'effort'     => env('HEFAM_AI_DEEP_EFFORT', 'high'),
                'max_tokens' => 12000,
                'reserve'    => 2500,
            ],
        ],
        'prices' => [
            'claude-haiku-5-5'  => ['input' => 0.10, 'output' => 0.50, 'cache_read' => 0.01, 'cache_write' => 0.125],
            'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_read' => 0.20, 'cache_write' => 2.50],
            'claude-opus-5-5'   => ['input' => 4.00, 'output' => 20.00, 'cache_read' => 0.40, 'cache_write' => 5.00],
        ],
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
            'features' => ['payroll', 'medicines', 'other_income', 'strain', 'export', 'priority_support', 'assistant'],
        ],
    ],

    'features' => [
        'payroll'          => 'Gaji & absensi pekerja',
        'medicines'        => 'Stok obat & vitamin',
        'other_income'     => 'Pendapatan lain (afkir, kotoran)',
        'strain'           => 'Standar produksi strain',
        'export'           => 'Ekspor Excel',
        'priority_support' => 'Prioritas bantuan admin',
        'assistant'        => 'Asisten Kandang (tanya lewat aplikasi & WhatsApp)',
    ],
];
