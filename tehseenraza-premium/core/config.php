<?php
// core/config.php
return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'my_blog_db',
        'user' => 'db_user',
        'pass' => 'db_pass',
        'charset' => 'utf8mb4'
    ],

    'site' => [
        'name' => "Tehseen's Premium",
        'url'  => "https://tehseenraza.net"
    ],

    'admin_email' => "admin@tehseenraza.net",

    'mail' => [
        'method' => 'smtp',
        'smtp' => [
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'smtp_user',
            'password' => 'smtp_pass',
            'secure' => 'tls',
            'from_email' => 'no-reply@tehseenraza.net',
            'from_name'  => "Tehseen's Premium"
        ]
    ],

    'rate_limit' => [
        'track_seconds' => 2,   // Tracking events
        'guest_submit_seconds' => 60,
        'subscribe_seconds' => 20
    ]
];
