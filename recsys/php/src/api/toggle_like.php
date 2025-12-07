<?php
require __DIR__ . '/config.php';
require __DIR__ . '/../lib/Utils.php';

$config = require __DIR__ . '/config.php';
$pdo = Utils::getPDO($config);

$data = json_decode(file_get_contents("php://input"), true);

$uid = (int)$data["user_id"];
$cid = (int)$data["content_id"];
$check = isset($data["_check"]);

if (!$uid || !$cid) {
    echo json_encode(["error"=>"bad request"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM interactions WHERE user_id=? AND content_id=? AND type='like'");
$stmt->execute([$uid,$cid]);
$existing = $stmt->fetchColumn();

if ($check) {
    echo json_encode(["liked" => (bool)$existing]);
    exit;
}

if ($existing) {
    $pdo->prepare("DELETE FROM interactions WHERE id=?")->execute([$existing]);
    echo json_encode(["liked"=>false]);
} else {
    $pdo->prepare("INSERT INTO interactions (user_id,content_id,type,weight) VALUES (?,?, 'like',3)")
        ->execute([$uid,$cid]);

    echo json_encode(["liked"=>true]);
}
