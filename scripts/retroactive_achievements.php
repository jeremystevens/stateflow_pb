<?php
require_once __DIR__ . '/../includes/db.php';

function retroactivelyAwardAchievements(PDO $pdo) {
    $query = "
        SELECT uap.id AS progress_id, uap.user_id, uap.achievement_id, uap.current_progress, uap.target_progress
        FROM user_achievement_progress uap
        INNER JOIN achievements a ON uap.achievement_id = a.id
        WHERE uap.current_progress >= uap.target_progress
        AND NOT EXISTS (
            SELECT 1 FROM user_achievements ua
            WHERE ua.user_id = uap.user_id AND ua.achievement_id = uap.achievement_id
        )
    ";

    $stmt = $pdo->query($query);
    $toAward = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insertStmt = $pdo->prepare("
        INSERT INTO user_achievements (user_id, achievement_id, unlocked_at)
        VALUES (:user_id, :achievement_id, strftime('%s','now'))
    ");
    $deleteProgress = $pdo->prepare("DELETE FROM user_achievement_progress WHERE id = :id");

    $count = 0;
    foreach ($toAward as $row) {
        $insertStmt->execute([
            ':user_id' => $row['user_id'],
            ':achievement_id' => $row['achievement_id']
        ]);
        $deleteProgress->execute([':id' => $row['progress_id']]);
        $count++;
    }

    echo "Awarded $count achievements retroactively.\n";
}

retroactivelyAwardAchievements($pdo);
