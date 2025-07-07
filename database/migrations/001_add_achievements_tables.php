<?php
return function (PDO $pdo) {
    // Fetch existing table names
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('achievements', $tables)) {
        $pdo->exec(<<<SQL
CREATE TABLE achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    icon TEXT NOT NULL,
    category TEXT DEFAULT 'general',
    points INTEGER DEFAULT 10,
    is_active BOOLEAN DEFAULT 1,
    created_at INTEGER DEFAULT (strftime('%s', 'now'))
);
SQL
        );
    }

    if (!in_array('user_achievement_progress', $tables)) {
        $pdo->exec(<<<SQL
CREATE TABLE user_achievement_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    achievement_name TEXT NOT NULL,
    current_progress INTEGER DEFAULT 0,
    target_progress INTEGER NOT NULL,
    last_updated INTEGER DEFAULT (strftime('%s', 'now')),
    FOREIGN KEY(user_id) REFERENCES users(id),
    UNIQUE(user_id, achievement_name)
);
SQL
        );
    }

    if (!in_array('user_achievements', $tables)) {
        $pdo->exec(<<<SQL
CREATE TABLE user_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    achievement_id INTEGER NOT NULL,
    unlocked_at INTEGER DEFAULT (strftime('%s', 'now')),
    progress_data TEXT,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(achievement_id) REFERENCES achievements(id),
    UNIQUE(user_id, achievement_id)
);
SQL
        );
    }
};
