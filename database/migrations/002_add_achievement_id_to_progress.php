<?php
return function (PDO $pdo) {
    // Check existing columns in user_achievement_progress
    $cols = $pdo->query("PRAGMA table_info(user_achievement_progress)")->fetchAll(PDO::FETCH_COLUMN, 1);

    if (!in_array('achievement_id', $cols)) {
        // Add the missing achievement_id column
        $pdo->exec("ALTER TABLE user_achievement_progress ADD COLUMN achievement_id INTEGER");

        // Populate achievement_id by matching on achievement_name
        $pdo->exec(
            "UPDATE user_achievement_progress
             SET achievement_id = (
                 SELECT id FROM achievements WHERE name = user_achievement_progress.achievement_name
             )
             WHERE achievement_id IS NULL"
        );
    }
};

