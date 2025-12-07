<?php
class Recommender {
    private $pdo;
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getRecommendationsForUser(int $userId, int $limit = 10): array {
        // 1. Получаем seed items
        $stmt = $this->pdo->prepare("SELECT content_id, SUM(weight) as score FROM interactions WHERE user_id = :uid GROUP BY content_id ORDER BY score DESC LIMIT 20");
        $stmt->execute([':uid' => $userId]);
        $userItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $seedIds = array_map(function($r){ return (int)$r['content_id']; }, $userItems);

        if (count($seedIds) === 0) {
            return $this->getPopular($limit);
        }

        $in = implode(',', array_map('intval', $seedIds));

        // 2. Co-occurrence
        $sql = "
            SELECT i2.content_id as candidate_id, COUNT(DISTINCT i2.user_id) as co_count
            FROM interactions i1
            JOIN interactions i2 ON i1.user_id = i2.user_id AND i2.content_id NOT IN ($in)
            WHERE i1.content_id IN ($in)
            GROUP BY i2.content_id
            ORDER BY co_count DESC
            LIMIT 200
        ";
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // load tags for seed items
        $seedTags = [];
        if (count($seedIds) > 0) {
            $tagStmt = $this->pdo->query("SELECT id, tags FROM content WHERE id IN ($in)");
            while($r = $tagStmt->fetch(PDO::FETCH_ASSOC)) {
                $seedTags[$r['id']] = array_filter(array_map('trim', explode(',', $r['tags'])));
            }
        }

        $candidates = [];
        $tagsStmt = $this->pdo->prepare("SELECT tags FROM content WHERE id = :cid");
        foreach ($rows as $r) {
            $cand = (int)$r['candidate_id'];
            $score = (float)$r['co_count'];
            $tagsStmt->execute([':cid' => $cand]);
            $ct = $tagsStmt->fetch(PDO::FETCH_ASSOC);
            $candTags = $ct ? array_filter(array_map('trim', explode(',', $ct['tags']))) : [];
            $overlap = 0;
            foreach ($seedTags as $st) {
                $overlap += count(array_intersect($st, $candTags));
            }
            $score += 0.5 * $overlap;
            $candidates[$cand] = $score;
        }

        if (count($candidates) < $limit) {
            $pop = $this->getPopular($limit*2);
            foreach ($pop as $p) {
                if (!isset($candidates[$p])) $candidates[$p] = 0.1;
            }
        }

        arsort($candidates);
        return array_slice(array_keys($candidates), 0, $limit);
    }

    private function getPopular(int $limit = 10): array {
        $stmt = $this->pdo->prepare("SELECT content_id, COUNT(*) AS cnt FROM interactions GROUP BY content_id ORDER BY cnt DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'content_id'));
    }
}