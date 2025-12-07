<?php
// Авто-инициализация: миграция + сиды (выполняется один раз)
// Вызывается фронтом перед загрузкой ленты

require __DIR__ . '/config.php';
require __DIR__ . '/../lib/Utils.php';

$config = require __DIR__ . '/config.php';
$pdo = Utils::getPDO($config);

// Проверяем наличие таблиц
$hasUsers = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();

if (!$hasUsers) {
    // Выполняем schema.sql
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
}

// Проверяем количество постов
$count = $pdo->query("SELECT COUNT(*) FROM content")->fetchColumn();

if ($count < 30) {
    include __DIR__ . '/seed.php';
}

echo json_encode(["ok" => true]);
