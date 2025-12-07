<?php
class Utils {
    public static function getPDO($config) {
        $dsn = "mysql:host={$config->db['host']};port={$config->db['port']};dbname={$config->db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config->db['user'], $config->db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    }
}