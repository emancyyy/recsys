<?php
/**
 * run_tests.php
 * Самодельный тестовый раннер для проверки:
 * - работоспособности API
 * - корректности логики рекомендаций
 * - лайков
 * - seed.php
 * - структуры данных
 */

error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

function test($name, $callback) {
    try {
        $callback();
        echo "[PASS] $name\n";
    } catch (Exception $e) {
        echo "[FAIL] $name\n";
        echo "    → " . $e->getMessage() . "\n";
    }
}

function api($url) {
    $res = @file_get_contents("http://localhost:8080$url");
    if ($res === false) throw new Exception("API not reachable: $url");
    return json_decode($res, true);
}

echo "=== Running Recsys Tests ===\n\n";

/* -----------------------------------------
   1) Seed работает
------------------------------------------*/
test("Seed loads successfully", function() {
    $res = @file_get_contents("http://localhost:8080/api/seed.php");
    if (strpos($res, "seed complete") === false)
        throw new Exception("seed.php did not return 'seed complete'");
});

/* -----------------------------------------
   2) Контент существует
------------------------------------------*/
test("Content count >= 30", function() {
    $data = api("/api/recommend.php?user_id=1&limit=30");
    if (!isset($data["recommendations"]))
        throw new Exception("recommendations missing");
    if (count($data["recommendations"]) < 30)
        throw new Exception("expected >=30 posts, got " . count($data["recommendations"]));
});

/* -----------------------------------------
   3) Лайк работает
------------------------------------------*/
test("Liking a post toggles correctly", function() {
    $res1 = api("/api/toggle_like.php?user_id=1&content_id=1");
    if (!isset($res1["liked"])) throw new Exception("toggle_like missing 'liked'");
    $first = $res1["liked"];

    $res2 = api("/api/toggle_like.php?user_id=1&content_id=1");
    if (!isset($res2["liked"])) throw new Exception("second toggle missing");

    if ($first === $res2["liked"])
        throw new Exception("like did not toggle (same state after two toggles)");
});

/* -----------------------------------------
   4) Рекомендации сортируются по тегам
------------------------------------------*/
test("Recommendations sorted by tag matches", function() {

    // лайкаем тех пост с известными тегами
    api("/api/toggle_like.php?user_id=1&content_id=2"); // post #2 → tech,programming,...

    $data = api("/api/recommend.php?user_id=1&limit=10");

    if (!isset($data["recommendations"][0]))
        throw new Exception("no recommendations");

    $top = $data["recommendations"][0];

    // Проверяем, что в топе не оказался пост без тегов "tech"
    if (stripos(json_encode($top), "tech") === false)
        throw new Exception("top recommendation does not match liked tags");
});

/* -----------------------------------------
   5) API доступны
------------------------------------------*/
test("API endpoints reachable", function() {
    $reqs = [
        "/api/recommend.php",
        "/api/toggle_like.php?user_id=1&content_id=1",
        "/api/seed.php"
    ];
    foreach ($reqs as $r) {
        $res = @file_get_contents("http://localhost:8080$r");
        if ($res === false) throw new Exception("$r unreachable");
    }
});

/* -----------------------------------------
   Completed
------------------------------------------*/
echo "\n=== Tests Done ===\n";
