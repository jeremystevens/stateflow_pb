<?php
/**
 * Database Configuration and Connection
 * SQLite PDO Connection Handler
 */

$dbPath = __DIR__ . '/../database/pastebin.db';
$dbDir = dirname($dbPath);

// Create database directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

/**
 * Get database connection
 */
function getDatabase() {
    $dbPath = __DIR__ . '/../database/pastebin.db';
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Initialize database if needed
        if (!file_exists($dbPath) || filesize($dbPath) == 0) {
            require_once __DIR__ . '/../database/init.php';
            initializeDatabase($pdo);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Create global PDO connection
try {
    $pdo = getDatabase();
} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage());
}

/**
 * Generate a unique paste ID
 */
function generatePasteId($length = 8) {
    global $pdo;
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    do {
        $id = '';
        for ($i = 0; $i < $length; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // Check if this ID already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pastes WHERE id = ?");
        $stmt->execute([$id]);
        $exists = $stmt->fetchColumn() > 0;
    } while ($exists);

    return $id;
}

/**
 * Get paste by ID
 */
function getPasteById($id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare(
            "SELECT p.*, u.username, u.profile_image
             FROM pastes p
             LEFT JOIN users u ON p.user_id = u.id
             WHERE p.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create a new paste
 */
function createPaste($title, $content, $language, $expiration = null) {
    global $pdo;
    
    $id = generatePasteId();
    
    // Ensure unique ID
    while (getPasteById($id)) {
        $id = generatePasteId();
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pastes (id, title, content, language, expiration, created_at) 
            VALUES (?, ?, ?, ?, ?, datetime('now'))
        ");
        
        $stmt->execute([$id, $title, $content, $language, $expiration]);
        return $id;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Create a new paste with advanced features
 */
function createPasteAdvanced($title, $content, $language, $expiration = null, $visibility = 'public', $password = null, $burnAfterRead = false, $zeroKnowledge = false, $parentPasteId = null, $userId = null) {
    global $pdo;
    
    $id = generatePasteId();
    
    // Convert expiration to timestamp if provided
    $expireTime = null;
    if ($expiration && $expiration !== 'never') {
        if (is_numeric($expiration)) {
            $expireTime = $expiration;
        } else {
            $expireTime = strtotime($expiration);
        }
    }
    
    // Map visibility to is_public boolean
    // Automatically set burn after read pastes to unlisted for privacy
    if ($burnAfterRead) {
        $visibility = 'unlisted';
    }
    
    $isPublic = 1;
    if ($visibility === 'private') {
        $isPublic = 0;
    } elseif ($visibility === 'unlisted') {
        $isPublic = 1; // Unlisted is still public but not listed
    }
    
    // Generate secure creator token for burn after read
    $creatorToken = null;
    if ($burnAfterRead) {
        $creatorToken = bin2hex(random_bytes(32)); // 64-character secure token
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pastes (
                id, title, content, language, expire_time, created_at,
                is_public, password, burn_after_read, zero_knowledge, creator_token, visibility,
                parent_paste_id, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $title,
            $content,
            $language,
            $expireTime,
            time(), // Unix timestamp
            $isPublic,
            $password,
            $burnAfterRead ? 1 : 0,
            $zeroKnowledge ? 1 : 0,
            $creatorToken,
            $visibility,
            $parentPasteId,
            $userId
        ]);
        
        // Return appropriate format based on burn after read
        if ($burnAfterRead) {
            return ['id' => $id, 'creator_token' => $creatorToken];
        } else {
            return $id; // Backward compatibility for non-burn pastes
        }
    } catch (PDOException $e) {
        error_log("Create paste failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get recent pastes
 */
function getRecentPastes($limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, language, created_at, views
            FROM pastes 
            WHERE is_public = 1 
            AND visibility = 'public'
            AND burn_after_read = 0
            AND (expire_time IS NULL OR expire_time > ?)
            AND (burned IS NULL OR burned = 0)
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([time(), $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get recent pastes failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Increment paste view count
 */
function incrementViewCount($pasteId, $ipAddress) {
    global $pdo;
    
    try {
        // First check if this IP has already viewed this paste
        $stmt = $pdo->prepare("SELECT 1 FROM paste_views WHERE paste_id = ? AND ip_address = ?");
        $stmt->execute([$pasteId, $ipAddress]);
        
        if (!$stmt->fetch()) {
            // Record the view
            $stmt = $pdo->prepare("INSERT INTO paste_views (paste_id, ip_address, created_at) VALUES (?, ?, ?)");
            $stmt->execute([$pasteId, $ipAddress, time()]);
            
            // Increment the counter
            $stmt = $pdo->prepare("UPDATE pastes SET views = views + 1 WHERE id = ?");
            $stmt->execute([$pasteId]);
            
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Increment view count failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get paste templates by category
 */
function getPasteTemplates($category = null) {
    global $pdo;
    
    try {
        if ($category) {
            $stmt = $pdo->prepare("
                SELECT id, name, description, content, language, category 
                FROM paste_templates 
                WHERE category = ? AND is_public = 1
                ORDER BY usage_count DESC, name ASC
            ");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, description, content, language, category 
                FROM paste_templates 
                WHERE is_public = 1
                ORDER BY category ASC, usage_count DESC, name ASC
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get paste templates failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark paste as viewed by creator (first redirect view)
 */
function markPasteAsCreatorViewed($pasteId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE pastes SET creator_viewed = 1 WHERE id = ?");
        $stmt->execute([$pasteId]);
        return true;
    } catch (PDOException $e) {
        error_log("Mark paste as creator viewed failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if paste should be burned (has been viewed by creator and now has a real view)
 */
function shouldBurnPaste($pasteId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT creator_viewed, views FROM pastes WHERE id = ?");
        $stmt->execute([$pasteId]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Burn if creator has viewed it and this would be the first real view
            return $result['creator_viewed'] == 1;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Should burn paste check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Burn (delete) a paste after it has been read
 */
function burnPaste($pasteId) {
    global $pdo;
    
    try {
        // Mark as burned and set expire_time to current time to hide from recent pastes
        $currentTime = time();
        $stmt = $pdo->prepare("UPDATE pastes SET burned = 1, burned_at = ?, expire_time = ? WHERE id = ?");
        $stmt->execute([$currentTime, $currentTime, $pasteId]);
        
        // Optionally delete the actual content for security
        $stmt = $pdo->prepare("UPDATE pastes SET content = '[BURNED]' WHERE id = ?");
        $stmt->execute([$pasteId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Burn paste failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get template categories
 */
function getTemplateCategories() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT category, COUNT(*) as count
            FROM paste_templates 
            WHERE is_public = 1
            GROUP BY category
            ORDER BY category ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get template categories failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Get comments for a paste
 */
function getComments($pasteId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.profile_image
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.paste_id = ? AND c.is_deleted = 0
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$pasteId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get comments failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Get replies for a comment
 */
function getCommentReplies($commentId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, u.username, u.profile_image
            FROM comment_replies r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.parent_comment_id = ? AND r.is_deleted = 0
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$commentId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get comment replies failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a comment
 */
function addComment($pasteId, $content, $userId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO comments (paste_id, user_id, content, created_at)
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$pasteId, $userId, $content, time()]);
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Add comment failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a reply to a comment
 */
function addCommentReply($commentId, $pasteId, $content, $userId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO comment_replies (parent_comment_id, paste_id, user_id, content, created_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $result = $stmt->execute([$commentId, $pasteId, $userId, $content, time()]);
        
        if ($result) {
            return $pdo->lastInsertId();
        }
        return false;
    } catch (PDOException $e) {
        error_log("Add comment reply failed: " . $e->getMessage());
        return false;
    }
}

// Discussion Thread Functions
function getDiscussionThreads($pasteId) {
    $db = getDatabase();
    $stmt = $db->prepare("
        SELECT dt.*, 
               (SELECT COUNT(*) FROM paste_discussion_posts 
                WHERE thread_id = dt.id AND is_deleted = 0) as reply_count
        FROM paste_discussion_threads dt
        WHERE dt.paste_id = ?
        ORDER BY dt.created_at DESC
    ");
    $stmt->execute([$pasteId]);
    return $stmt->fetchAll();
}

function getDiscussionPosts($threadId) {
    $db = getDatabase();
    $stmt = $db->prepare("
        SELECT dp.*
        FROM paste_discussion_posts dp
        WHERE dp.thread_id = ? AND dp.is_deleted = 0
        ORDER BY dp.created_at ASC
    ");
    $stmt->execute([$threadId]);
    return $stmt->fetchAll();
}

function createDiscussionThread($pasteId, $title, $category, $content, $username = 'Anonymous') {
    $db = getDatabase();
    $db->beginTransaction();
    
    try {
        // Create thread
        $stmt = $db->prepare("
            INSERT INTO paste_discussion_threads (paste_id, username, title, category)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$pasteId, $username, $title, $category]);
        $threadId = $db->lastInsertId();
        
        // Create initial post
        $stmt = $db->prepare("
            INSERT INTO paste_discussion_posts (thread_id, username, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$threadId, $username, $content]);
        
        $db->commit();
        return $threadId;
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function addDiscussionPost($threadId, $content, $username = 'Anonymous') {
    $db = getDatabase();
    $stmt = $db->prepare("
        INSERT INTO paste_discussion_posts (thread_id, username, content)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$threadId, $username, $content]);
    return $db->lastInsertId();
}

function getDiscussionThread($threadId) {
    $db = getDatabase();
    $stmt = $db->prepare("
        SELECT * FROM paste_discussion_threads WHERE id = ?
    ");
    $stmt->execute([$threadId]);
    return $stmt->fetch();
}

/**
 * Log password access attempts for a paste
 */
function logPasteAccessAttempt($pasteId, $ipAddress, $success) {
    $logDir = __DIR__ . '/../database';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/access.log';
    $entry = date('c') . " | {$pasteId} | {$ipAddress} | " . ($success ? 'SUCCESS' : 'FAIL') . PHP_EOL;
    file_put_contents($logFile, $entry, FILE_APPEND);
}


?>
