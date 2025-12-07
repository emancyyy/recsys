<?php
// migrate.php - выполняет SQL-схему
$config = require __DIR__ . '/config.php';
$dsn = "mysql:host={$config->db['host']};port={$config->db['port']};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $config->db['user'], $config->db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Создать базу, если не существует
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config->db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$config->db['name']}`");

    $ddl = file_get_contents(__DIR__ . '/schema.sql');
    if ($ddl === false) throw new Exception('schema.sql not found');
    $pdo->exec($ddl);

    echo "Migrations applied successfully.";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Migration error: " . $e->getMessage();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}