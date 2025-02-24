<?php
$driver = getenv('DB_DRIVER') ?: 'sqlite';

if ($driver === 'sqlite') {
    return [
        'driver' => 'pdo_sqlite',
        'path' => __DIR__ . '/../database/baas.sqlite'
    ];
} else {
    return [
        'driver' => 'pdo_mysql',
        'host' => getenv('DB_HOST') ?: 'localhost',
        'dbname' => getenv('DB_DATABASE') ?: 'baas',
        'user' => getenv('DB_USERNAME') ?: 'baas_user',
        'password' => getenv('DB_PASSWORD') ?: 'baas_password',
        'charset' => 'utf8mb4'
    ];
}
