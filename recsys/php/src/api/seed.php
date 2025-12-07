<?php
// seed.php — создаёт 5 пользователей, 30 постов (каждый с 4-6 тегами) и несколько interactions
require __DIR__ . '/config.php';
require __DIR__ . '/../lib/Utils.php';

$config = require __DIR__ . '/config.php';
$pdo = Utils::getPDO($config);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Очищаем таблицы и сбрасываем автоинкремент (dev helper)
$pdo->exec("DELETE FROM interactions");
$pdo->exec("ALTER TABLE interactions AUTO_INCREMENT = 1");

$pdo->exec("DELETE FROM content");
$pdo->exec("ALTER TABLE content AUTO_INCREMENT = 1");

$pdo->exec("DELETE FROM users");
$pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");

// 1) Users
$users = ['alice','bob','carol','dave','eva'];
$stmtU = $pdo->prepare("INSERT INTO users (username) VALUES (:u)");
foreach ($users as $u) {
    $stmtU->execute([':u' => $u]);
}
$userIds = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);

// 2) Content (30 posts) — каждый пост имеет 4-6 тегов
$posts = [
    ["Путешествие в горы", "Полезные советы по походам, снаряжению, еде и безопасности.", "travel,adventure,mountains,hiking,nature,gear"],
    ["Топ языков программирования 2025", "Сравнение Python, Go, Rust, PHP и JavaScript — для каких задач подходят.", "tech,programming,python,go,rust,php"],
    ["Как готовить идеальную пасту", "Лайфхаки итальянских шефов и лучшие рецепты для дома.", "food,recipes,italian,cooking,kitchen,tips"],
    ["Фильмы, которые стоит посмотреть", "Подборка лучших фильмов и короткие обзоры.", "movies,cinema,review,art,drama,thriller"],
    ["Игровые новинки месяца", "Анонсы, трейлеры и ожидания.", "games,gaming,review,pc,console,esports"],
    ["Как обрести мотивацию", "Практические советы и упражнения.", "life,motivation,psychology,habits,productivity"],
    ["Музыка, которая вдохновляет", "Лучшие альбомы и плейлисты.", "music,review,chill,lofi,jazz,electronic"],
    ["Как начать заниматься спортом", "План для новичков и базовые тренировки.", "sport,fitness,health,training,nutrition"],
    ["Учимся делать сайты", "HTML, CSS, JS — кратко и понятно.", "coding,web,frontend,html,css,javascript"],
    ["Лучшие гаджеты года", "Смартфоны, наушники и аксессуары.", "tech,gadgets,review,phones,wearables"],
    ["Рецепты быстрых завтраков", "5 рецептов для занятых утр.", "food,breakfast,recipes,quick,healthy"],
    ["Как делать красивые фото", "Свет, композиция и обработка.", "photo,photography,composition,editing,gear"],
    ["Самые интересные книги", "Рекомендации по чтению — фантастика и нон-фикшн.", "books,reading,fiction,nonfiction,review"],
    ["Гид по минимализму", "Как убрать лишнее и упорядочить дом.", "life,minimalism,declutter,home,habits"],
    ["Что нового в мире AI", "Модели, новости и практические применения.", "tech,ai,ml,deep-learning,models,research"],
    ["Как собрать игровой ПК", "Выбор комплектующих и советы по апгрейду.", "tech,pc,gaming,hardware,builds"],
    ["Путеводитель по Европе", "Маршруты, бюджет и лайфхаки.", "travel,europe,guide,budget,transport,hotels"],
    ["Продвинутый JavaScript", "Closure, async/await, оптимизация производительности.", "coding,javascript,web,performance,frontend,advanced"],
    ["Секреты домашней выпечки", "Булочки, хлеб и десерты.", "food,baking,recipes,dessert,home"],
    ["Введение в Rust", "Почему Rust набирает популярность — примеры.", "tech,programming,rust,safety,systems"],
    ["Технические интервью: подготовка", "Вопросы, задачи и стратегия.", "career,tech,interview,programming,practice"],
    ["Фитнес дома: 30 минут в день", "Тренировка без оборудования.", "fitness,home,workout,health,bodyweight"],
    ["Кибербезопасность для всех", "Пароли, 2FA и защита личных данных.", "tech,security,privacy,cyber,online"],
    ["Искусство сторителлинга", "Как рассказывать истории — для блогов и видео.", "creative,writing,storytelling,media,content"],
    ["Как выбрать ноутбук для учебы", "Какой процессор, RAM и диск нужны.", "tech,laptops,education,students,hardware"],
    ["Домашний сад на подоконнике", "Выращивание зелени круглый год.", "home,gardening,plants,sustainability"],
    ["Обзор indie-игр", "Тёплые инди-проекты и почему их стоит попробовать.", "games,indie,review,design,art"],
    ["Секреты тайм-менеджмента", "Методы Pomodoro и планирование.", "productivity,time-management,habits,work"],
    ["Основы Docker и контейнеров", "Практическое руководство для начинающих.", "devops,docker,containers,dev,infra"],
    ["Психология цвета в дизайне", "Как выбирать палитру и сочетания.", "design,color,ux,branding,visual"]
];

$stmtP = $pdo->prepare("INSERT INTO content (title, body, tags, created_at) VALUES (:t,:b,:g, NOW())");
foreach ($posts as $p) {
    $stmtP->execute([':t' => $p[0], ':b' => $p[1], ':g' => $p[2]]);
}

// 3) Interactions — создаём несколько реальных likes и просмотров (работаем с реальными id)
$contentIds = $pdo->query("SELECT id FROM content")->fetchAll(PDO::FETCH_COLUMN);

// Очистим interactions перед записями
$pdo->exec("DELETE FROM interactions");
$pdo->exec("ALTER TABLE interactions AUTO_INCREMENT = 1");

$stmtI = $pdo->prepare("INSERT INTO interactions (user_id, content_id, type, weight) VALUES (:u,:c,:t,:w)");

// Для первых 5 пользователей дадим по нескольку лайков/просмотров
foreach ($userIds as $uid) {
    // каждый пользователь лайкает 4 случайных поста и просматривает 2 других
    $liked = [];
    for ($i = 0; $i < 4; $i++) {
        $cid = $contentIds[array_rand($contentIds)];
        // избегаем дублирования like для одного user/content
        if (in_array($cid, $liked)) continue;
        $liked[] = $cid;
        $stmtI->execute([':u' => $uid, ':c' => $cid, ':t' => 'like', ':w' => 3]);
    }
    for ($i = 0; $i < 2; $i++) {
        $cid = $contentIds[array_rand($contentIds)];
        $stmtI->execute([':u' => $uid, ':c' => $cid, ':t' => 'view', ':w' => 1]);
    }
}

echo "seed complete";
