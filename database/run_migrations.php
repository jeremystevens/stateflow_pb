<?php
function runMigrations(PDO $pdo) {
    $migrationDir = __DIR__ . '/migrations';

    // Ensure migration log table exists
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS migration_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL UNIQUE,
    applied_at INTEGER DEFAULT (strftime('%s', 'now'))
);
SQL
    );

    if (!is_dir($migrationDir)) {
        return;
    }

    $files = array_merge(
        glob($migrationDir . '/*.php') ?: [],
        glob($migrationDir . '/*.sql') ?: []
    );
    sort($files);

    foreach ($files as $file) {
        $name = basename($file);
        $stmt = $pdo->prepare('SELECT 1 FROM migration_log WHERE filename = ?');
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) {
            continue;
        }

        if (substr($file, -4) === '.php') {
            $migration = include $file;
            if (is_callable($migration)) {
                $migration($pdo);
            }
        } elseif (substr($file, -4) === '.sql') {
            $sql = file_get_contents($file);
            $pdo->exec($sql);
        }

        $insert = $pdo->prepare('INSERT INTO migration_log (filename) VALUES (?)');
        $insert->execute([$name]);
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    require_once __DIR__ . '/../includes/db.php';
    runMigrations($pdo);
}
