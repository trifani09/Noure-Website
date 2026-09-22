<?php

return [
    'currency' => 'IDR',
    'free_shipping_min_order' => env('FREE_SHIPPING_MIN_ORDER', 500000),
    'weight_step_grams' => 1000,
    'weight_surcharge_per_step' => 0,
    'methods' => [
        'standard' => [
            'name' => 'Standard Delivery',
            'description' => 'Reliable delivery for everyday orders.',
            'base_price' => 20000,
            'estimate' => '2-5 business days',
            'active' => true,
        ],
        'express' => [
            'name' => 'Express Delivery',
            'description' => 'Priority delivery when you need it sooner.',
            'base_price' => 40000,
            'estimate' => '1-2 business days',
            'active' => true,
        ],
    ],
    'zones' => [
        'jabodetabek' => [
            'name' => 'Jabodetabek',
            'cities' => ['jakarta', 'bogor', 'depok', 'tangerang', 'bekasi'],
        ],
        'java' => [
            'name' => 'Java',
            'cities' => ['bandung', 'cirebon', 'semarang', 'surabaya', 'yogyakarta', 'solo', 'malang', 'tasikmalaya'],
            'provinces' => ['banten', 'jawa barat', 'jawa tengah', 'jawa timur', 'daerah istimewa yogyakarta', 'dki jakarta'],
        ],
        'sumatra' => ['name' => 'Sumatra', 'cities' => ['medan', 'padang', 'pekanbaru', 'palembang', 'bandar lampung', 'batam'], 'provinces' => ['aceh', 'sumatera utara', 'sumatera barat', 'riau', 'jambi', 'sumatera selatan', 'bengkulu', 'lampung', 'kepulauan bangka belitung', 'kepulauan riau']],
        'kalimantan' => ['name' => 'Kalimantan', 'cities' => ['pontianak', 'banjarmasin', 'samarinda', 'balikpapan'], 'provinces' => ['kalimantan barat', 'kalimantan tengah', 'kalimantan selatan', 'kalimantan timur', 'kalimantan utara']],
        'sulawesi' => ['name' => 'Sulawesi', 'cities' => ['manado', 'palu', 'makassar', 'kendari', 'gorontalo'], 'provinces' => ['sulawesi utara', 'sulawesi tengah', 'sulawesi selatan', 'sulawesi tenggara', 'gorontalo', 'sulawesi barat']],
        'bali_nusa_tenggara' => ['name' => 'Bali / Nusa Tenggara', 'cities' => ['denpasar', 'mataram', 'kupang'], 'provinces' => ['bali', 'nusa tenggara barat', 'nusa tenggara timur']],
        'other_indonesia' => ['name' => 'Other Indonesia', 'cities' => ['ambon', 'jayapura', 'sorong'], 'provinces' => ['maluku', 'maluku utara', 'papua', 'papua barat', 'papua barat daya', 'papua pegunungan', 'papua selatan', 'papua tengah']],
    ],
];