<?php
return (object)[
    'db' => [
        'host' => getenv('DB_HOST') ?: 'db',
        'name' => getenv('DB_NAME') ?: 'recsys',
        'user' => getenv('DB_USER') ?: 'recuser',
        'pass' => getenv('DB_PASS') ?: 'recpass',
        'port' => getenv('DB_PORT') ?: '3306'
    ]
];