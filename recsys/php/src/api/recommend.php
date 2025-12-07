<?php
// recommend.php — рекомендатор: первая сортировка — количество совпавших тегов
require __DIR__ . '/config.php';
require __DIR__ . '/../lib/Utils.php';
$config = require __DIR__ . '/config.php';
$pdo = Utils::getPDO($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$uid = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$limit = isset($_GET['limit']) ? min(100, (int)$_GET['limit']) : 15;

// 1) Получаем теги, которые пользователь ЛАЙКАЛ (type='like')
$likedTags = [];
if ($uid > 0) {
    $sql = "
      SELECT c.tags
      FROM interactions i
      JOIN content c ON c.id = i.content_id
      WHERE i.user_id = :uid AND i.type = 'like'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($rows as $tagsCsv) {
        if (!$tagsCsv) continue;
        $tags = array_filter(array_map('trim', explode(',', $tagsCsv)));
        foreach ($tags as $t) {
            if ($t === '') continue;
            $likedTags[$t] = true;
        }
    }
}

// 2) Популярность (кол-во interactions на пост)
$pop = [];
$popStmt = $pdo->query("SELECT content_id, COUNT(*) as cnt FROM interactions GROUP BY content_id");
foreach ($popStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $pop[(int)$r['content_id']] = (int)$r['cnt'];
}

// 3) Получаем все посты
$contentStmt = $pdo->query("SELECT id, title, body, tags, created_at FROM content");
$all = $contentStmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Для каждого поста считаем matches = количество совпадающих тегов с likedTags
$candidates = [];
foreach ($all as $c) {
    $tags = array_filter(array_map('trim', explode(',', $c['tags'])));
    $matches = 0;
    foreach ($tags as $t) {
        if (isset($likedTags[$t])) $matches++;
    }
    // popularity
    $popularity = isset($pop[(int)$c['id']]) ? (int)$pop[(int)$c['id']] : 0;
    // recency bonus
    $recency = 0.0;
    if (!empty($c['created_at'])) {
        $ageDays = (time() - strtotime($c['created_at'])) / (60*60*24);
        $recency = max(0, 7 - $ageDays) / 7.0;
    }

    $candidates[] = [
        'id' => (int)$c['id'],
        'title' => $c['title'],
        'body' => $c['body'],
        'tags' => $tags,
        'matches' => $matches,
        'popularity' => $popularity,
        'recency' => $recency
    ];
}

// 5) Сортируем: сначала matches (desc), затем popularity (desc), затем recency (desc), затем id desc
usort($candidates, function($a,$b){
    if ($a['matches'] !== $b['matches']) return $b['matches'] - $a['matches'];
    if ($a['popularity'] !== $b['popularity']) return $b['popularity'] - $a['popularity'];
    if ($a['recency'] !== $b['recency']) return ($b['recency'] < $a['recency']) ? -1 : 1;
    return $b['id'] - $a['id'];
});

// 6) Отдаём top $limit (убирая теги из ответа — теги скрыты)
$response = ['recommendations' => []];
$top = array_slice($candidates, 0, $limit);
foreach ($top as $t) {
    $response['recommendations'][] = [
        'id' => $t['id'],
        'title' => $t['title'],
        'body' => $t['body'],
        'matches' => $t['matches'],
        'popularity' => $t['popularity']
        // 'tags' => $t['tags'] // отладка: можно вернуть временно
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
