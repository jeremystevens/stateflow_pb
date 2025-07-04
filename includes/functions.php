<?php
require_once __DIR__ . '/db.php';

/**
 * Create a new paste with versioning and optional forking support.
 *
 * @param PDO    $db            Database connection
 * @param string $title         Paste title
 * @param string $content       Paste content
 * @param string $language      Language code
 * @param int|null $userId      ID of the creating user
 * @param string|null $parentPasteId ID of the parent paste (for chaining)
 * @param string|null $forkedFromId  ID of the paste being forked
 *
 * @return string The generated paste ID
 */
if (!function_exists('createPasteAdvanced')) {
function createPasteAdvanced($db, $title, $content, $language, $userId = null, $parentPasteId = null, $forkedFromId = null) {
    $slug = bin2hex(random_bytes(8));
    $createdAt = date('Y-m-d H:i:s');

    $stmt = $db->prepare("INSERT INTO pastes (paste_id, title, content, language, user_id, parent_paste_id, chain_parent_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$slug, $title, $content, $language, $userId, $parentPasteId, $parentPasteId, $createdAt]);

    $pasteId = $slug;

    // Insert first version for versioning support
    $versionStmt = $db->prepare("INSERT INTO paste_versions (paste_id, version_number, content, created_at) VALUES (?, 1, ?, ?)");
    $versionStmt->execute([$pasteId, $content, $createdAt]);

    // Record fork relationship if applicable
    if ($forkedFromId !== null && $userId !== null) {
        $forkInsert = $db->prepare("INSERT INTO paste_forks (original_paste_id, forked_paste_id, forked_by_user_id) VALUES (?, ?, ?)");
        $forkInsert->execute([$forkedFromId, $pasteId, $userId]);
    }

    return $pasteId;
}
}
?>
