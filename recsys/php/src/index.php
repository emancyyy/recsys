<?php
// Correct routing: do NOT intercept /api, /frontend, /tests, /lib

$uri = $_SERVER['REQUEST_URI'];

// Если путь ведёт в /api — отдаём напрямую
if (strpos($uri, "/api/") === 0) {
    return false; // пусть Apache отдаёт /api/* как файлы
}

// Если путь ведёт в фронтенд
if (strpos($uri, "/frontend/") === 0) {
    return false;
}

// Если путь ведёт в tests
if (strpos($uri, "/tests/") === 0) {
    return false;
}

// Если это директория lib — отдаём напрямую
if (strpos($uri, "/lib/") === 0) {
    return false;
}

// Всё остальное — отправляем на фронтенд
include __DIR__ . "/frontend/index.html";
