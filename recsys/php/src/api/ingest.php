<?php
require __DIR__ . '/config.php';
require __DIR__ . '/../lib/Utils.php';
$config = require __DIR__ . '/config.php';
$pdo = Utils::getPDO($config);

$body = json_decode(file_get_contents('php://input'), true);
$user_id = $body['user_id'] ?? null;
$content_id = $body['content_id'] ?? null;
$type = $body['type'] ?? 'view';
$rating = $body['rating'] ?? null;

if (!$content_id) {
    http_response_code(400);
    echo json_encode(['error' => 'content_id required']);
    exit;
}

$weight = 1.0;
if ($type === 'like') $weight = 3.0;
if ($type === 'rating' && $rating) $weight = 1 + ($rating - 3);

$stmt = $pdo->prepare("INSERT INTO interactions (user_id, content_id, type, rating, weight) VALUES (:uid, :cid, :type, :rating, :w)");
$stmt->execute([
    ':uid' => $user_id,
    ':cid' => $content_id,
    ':type' => $type,
    ':rating' => $rating,
    ':w' => $weight
]);

echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);