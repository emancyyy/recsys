<?php
require __DIR__ . '/config.php';
require __DIR__ . '/../lib/Utils.php';
$config = require __DIR__ . '/config.php';
$pdo = Utils::getPDO($config);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['title'])) { http_response_code(400); echo json_encode(['error'=>'title required']); exit; }
    $stmt = $pdo->prepare("INSERT INTO content (title, body, tags) VALUES (:title, :body, :tags)");
    $stmt->execute([':title'=>$data['title'], ':body'=>$data['body'] ?? '', ':tags'=>$data['tags'] ?? '']);
    echo json_encode(['id'=>$pdo->lastInsertId()]);
    exit;
}

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT id, title, tags FROM content ORDER BY id DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
    exit;
}

http_response_code(405);