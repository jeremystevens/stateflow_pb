<?php
require_once __DIR__ . '/db.php';

global $pdo;

// 1. Delete expired pastes
$pdo->exec("
    DELETE FROM pastes
    WHERE expire_time IS NOT NULL
      AND expire_time <= strftime('%s', 'now');
");

// 2. Clean up forks that reference deleted pastes
$pdo->exec("
    DELETE FROM paste_forks
    WHERE forked_paste_id NOT IN (SELECT id FROM pastes)
       OR original_paste_id NOT IN (SELECT id FROM pastes);
");

// 3. Null out invalid parent_paste_id (chains)
$pdo->exec("
    UPDATE pastes
    SET parent_paste_id = NULL
    WHERE parent_paste_id IS NOT NULL
      AND parent_paste_id NOT IN (SELECT id FROM pastes);
");
?>
