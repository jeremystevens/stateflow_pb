<?php
require_once __DIR__ . '/db.php';

/**
 * Load achievements from a CSV file into the achievements table.
 */
function loadAchievementsFromCSV($csvPath)
{
    global $pdo;
    if (!file_exists($csvPath)) {
        return;
    }
    if (($handle = fopen($csvPath, 'r')) === false) {
        return;
    }
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        return;
    }
    $insert = $pdo->prepare('INSERT OR IGNORE INTO achievements (name, category, description, target_progress, points, icon) VALUES (?, ?, ?, ?, ?, ?)');
    while (($row = fgetcsv($handle)) !== false) {
        $data = array_combine($header, $row);
        $insert->execute([
            $data['name'],
            $data['category'] ?? null,
            $data['description'] ?? null,
            (int)($data['target_progress'] ?? 1),
            (int)($data['points'] ?? 0),
            $data['icon'] ?? 'fa-trophy'
        ]);
    }
    fclose($handle);
}

/**
 * Increment progress for a user's achievement and unlock if completed.
 */
function updateAchievementProgress($userId, $achievementName, $increment = 1)
{
    global $pdo;
    $achStmt = $pdo->prepare('SELECT id, target_progress, points FROM achievements WHERE name = ?');
    $achStmt->execute([$achievementName]);
    $achievement = $achStmt->fetch();
    if (!$achievement) {
        return;
    }
    // already unlocked?
    $check = $pdo->prepare('SELECT 1 FROM user_achievements WHERE user_id = ? AND achievement_id = ?');
    $check->execute([$userId, $achievement['id']]);
    if ($check->fetchColumn()) {
        return;
    }
    $progStmt = $pdo->prepare('SELECT id, current_progress FROM user_achievement_progress WHERE user_id = ? AND achievement_id = ?');
    $progStmt->execute([$userId, $achievement['id']]);
    $progress = $progStmt->fetch();
    if ($progress) {
        $newProgress = $progress['current_progress'] + $increment;
        $pdo->prepare('UPDATE user_achievement_progress SET current_progress = ?, updated_at = strftime(\'%s\',\'now\') WHERE id = ?')
            ->execute([$newProgress, $progress['id']]);
    } else {
        $pdo->prepare('INSERT INTO user_achievement_progress (user_id, achievement_id, current_progress, target_progress) VALUES (?, ?, ?, ?)')
            ->execute([$userId, $achievement['id'], $increment, $achievement['target_progress']]);
        $newProgress = $increment;
    }
    if ($newProgress >= $achievement['target_progress']) {
        $pdo->prepare('DELETE FROM user_achievement_progress WHERE user_id = ? AND achievement_id = ?')
            ->execute([$userId, $achievement['id']]);
        $pdo->prepare('INSERT INTO user_achievements (user_id, achievement_id, points) VALUES (?, ?, ?)')
            ->execute([$userId, $achievement['id'], $achievement['points']]);
    }
}

/**
 * Get achievement summary statistics for a user.
 */
function getUserAchievementStats($userId)
{
    global $pdo;
    $totalUnlocked = (int)$pdo->query("SELECT COUNT(*) FROM user_achievements WHERE user_id = '" . $userId . "'")->fetchColumn();
    $totalPoints = (int)$pdo->query(
        "SELECT COALESCE(SUM(a.points), 0)
         FROM user_achievements ua
         JOIN achievements a ON ua.achievement_id = a.id
         WHERE ua.user_id = '" . $userId . "'"
    )->fetchColumn();
    $totalAchievements = (int)$pdo->query('SELECT COUNT(*) FROM achievements')->fetchColumn();
    $completionRate = $totalAchievements > 0 ? round(($totalUnlocked / $totalAchievements) * 100) : 0;
    return [
        'unlocked' => $totalUnlocked,
        'points' => $totalPoints,
        'completion' => $completionRate
    ];
}

/**
 * Get list of unlocked achievements for a user.
 */
function getUnlockedAchievements($userId)
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT a.name, a.description, a.icon, ua.unlocked_at, a.points FROM user_achievements ua JOIN achievements a ON ua.achievement_id = a.id WHERE ua.user_id = ? ORDER BY ua.unlocked_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get list of achievements still in progress for a user.
 */
function getInProgressAchievements($userId)
{
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT a.name, a.description, a.icon, uap.current_progress, uap.target_progress
         FROM user_achievement_progress uap
         JOIN achievements a ON uap.achievement_id = a.id
         WHERE uap.user_id = :user_id
         ORDER BY uap.updated_at DESC"
    );
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}
