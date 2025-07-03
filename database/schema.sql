BEGIN TRANSACTION;
CREATE TABLE IF NOT EXISTS "pastes" (
        "id"    TEXT,
        "title" TEXT,
        "content"       TEXT,
        "language"      TEXT,
        "password"      TEXT,
        "expire_time"   INTEGER,
        "is_public"     BOOLEAN DEFAULT 1,
        "created_at"    INTEGER,
        "tags"  TEXT DEFAULT '',
        "views" INTEGER DEFAULT 0,
        "user_id"       TEXT,
        "burn_after_read"       BOOLEAN DEFAULT 0,
        "flags" INTEGER DEFAULT 0,
        "flag_type"     TEXT,
        "flag_source"   TEXT,
        "ai_score"      REAL DEFAULT 0,
        "current_version"       INTEGER DEFAULT 1,
        "last_modified" INTEGER,
        "collection_id" INTEGER DEFAULT NULL,
        "original_paste_id"     INTEGER DEFAULT NULL,
        "fork_count"    INTEGER DEFAULT 0,
        "project_id"    INTEGER DEFAULT NULL,
        "branch_id"     INTEGER DEFAULT NULL,
        "source_url"    TEXT DEFAULT NULL,
        "imported_from" TEXT DEFAULT NULL,
        "parent_paste_id"       INTEGER DEFAULT NULL,
        "ai_summary_id" INTEGER DEFAULT NULL,
        PRIMARY KEY("id")
);

CREATE TABLE IF NOT EXISTS "users" (
        "id"    TEXT,
        "username"      TEXT UNIQUE,
        "password"      TEXT NOT NULL,
        "email" TEXT,
        "profile_image" TEXT DEFAULT NULL,
        "created_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        "website"       TEXT DEFAULT NULL,
        "tagline"       TEXT DEFAULT 'Just a dev sharing random code',
        "role"  TEXT DEFAULT 'free',
        "status"        TEXT DEFAULT 'active',
        "followers_count"       INTEGER DEFAULT 0,
        "following_count"       INTEGER DEFAULT 0,
        "profile_visibility"    TEXT DEFAULT 'public',
        "show_paste_count"      INTEGER DEFAULT 1,
        "allow_messages"        INTEGER DEFAULT 1,
        PRIMARY KEY("id")
);

CREATE TABLE IF NOT EXISTS "paste_views" (
        "paste_id"      INTEGER,
        "ip_address"    TEXT,
        "created_at"    INTEGER,
        FOREIGN KEY("paste_id") REFERENCES "pastes"("id"),
        PRIMARY KEY("paste_id","ip_address")
);

CREATE TABLE IF NOT EXISTS "paste_templates" (
        "id"    INTEGER,
        "name"  TEXT NOT NULL,
        "description"   TEXT,
        "content"       TEXT NOT NULL,
        "language"      TEXT DEFAULT 'plaintext',
        "category"      TEXT DEFAULT 'general',
        "is_public"     BOOLEAN DEFAULT 1,
        "created_by"    TEXT,
        "created_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        "usage_count"   INTEGER DEFAULT 0,
        FOREIGN KEY("created_by") REFERENCES "users"("id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "collections" (
        "id"    INTEGER,
        "name"  TEXT NOT NULL,
        "description"   TEXT,
        "user_id"       TEXT NOT NULL,
        "is_public"     BOOLEAN DEFAULT 1,
        "created_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        "updated_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        FOREIGN KEY("user_id") REFERENCES "users"("id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "collection_pastes" (
        "id"    INTEGER,
        "collection_id" INTEGER NOT NULL,
        "paste_id"      INTEGER NOT NULL,
        "added_at"      INTEGER DEFAULT (strftime('%s', 'now')),
        FOREIGN KEY("collection_id") REFERENCES "collections"("id") ON DELETE CASCADE,
        FOREIGN KEY("paste_id") REFERENCES "pastes"("id") ON DELETE CASCADE,
        UNIQUE("collection_id","paste_id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "paste_versions" (
        "id"    INTEGER,
        "paste_id"      INTEGER NOT NULL,
        "version_number"        INTEGER NOT NULL,
        "title" TEXT,
        "content"       TEXT,
        "language"      TEXT,
        "created_at"    INTEGER,
        "created_by"    TEXT,
        "change_message"        TEXT,
        FOREIGN KEY("paste_id") REFERENCES "pastes"("id"),
        FOREIGN KEY("created_by") REFERENCES "users"("id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "paste_forks" (
        "id"    INTEGER,
        "original_paste_id"     INTEGER NOT NULL,
        "forked_paste_id"       INTEGER NOT NULL,
        "forked_by_user_id"     TEXT NOT NULL,
        "created_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        FOREIGN KEY("forked_paste_id") REFERENCES "pastes"("id") ON DELETE CASCADE,
        FOREIGN KEY("forked_by_user_id") REFERENCES "users"("id"),
        FOREIGN KEY("original_paste_id") REFERENCES "pastes"("id") ON DELETE CASCADE,
        UNIQUE("original_paste_id","forked_by_user_id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "comments" (
        "id"    INTEGER,
        "paste_id"      INTEGER,
        "user_id"       TEXT,
        "content"       TEXT,
        "created_at"    INTEGER,
        "is_deleted"    BOOLEAN DEFAULT 0,
        "is_flagged"    BOOLEAN DEFAULT 0,
        "reply_count"   INTEGER DEFAULT 0,
        FOREIGN KEY("user_id") REFERENCES "users"("id"),
        FOREIGN KEY("paste_id") REFERENCES "pastes"("id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "comment_replies" (
        "id"    INTEGER,
        "parent_comment_id"     INTEGER NOT NULL,
        "paste_id"      INTEGER,
        "user_id"       TEXT,
        "content"       TEXT,
        "created_at"    INTEGER,
        "is_deleted"    BOOLEAN DEFAULT 0,
        "is_flagged"    BOOLEAN DEFAULT 0,
        FOREIGN KEY("user_id") REFERENCES "users"("id"),
        FOREIGN KEY("parent_comment_id") REFERENCES "comments"("id") ON DELETE CASCADE,
        FOREIGN KEY("paste_id") REFERENCES "pastes"("id"),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "audit_logs" (
        "id"    INTEGER,
        "user_id"       TEXT,
        "action"        TEXT NOT NULL,
        "resource_type" TEXT,
        "resource_id"   TEXT,
        "ip_address"    TEXT,
        "user_agent"    TEXT,
        "details"       TEXT,
        "severity"      TEXT DEFAULT 'info',
        "created_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "security_events" (
        "id"    INTEGER,
        "event_type"    TEXT NOT NULL,
        "ip_address"    TEXT,
        "user_agent"    TEXT,
        "details"       TEXT,
        "risk_level"    TEXT DEFAULT 'low',
        "created_at"    INTEGER DEFAULT (strftime('%s', 'now')),
        PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE IF NOT EXISTS "rate_limits" (
        "id"    TEXT,
        "identifier"    TEXT NOT NULL,
        "action"        TEXT NOT NULL,
        "count" INTEGER DEFAULT 1,
        "window_start"  INTEGER,
        "expires_at"    INTEGER,
        PRIMARY KEY("id")
);

-- Reports submitted by users
CREATE TABLE IF NOT EXISTS paste_flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paste_id INTEGER NOT NULL,
    user_id TEXT,
    ip_address TEXT,
    flag_type TEXT NOT NULL,
    reason TEXT,
    description TEXT,
    created_at INTEGER DEFAULT (strftime('%s', 'now')),
    status TEXT DEFAULT 'pending',
    reviewed_by TEXT,
    reviewed_at INTEGER,
    FOREIGN KEY(paste_id) REFERENCES pastes(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
);

-- Discussion system tables
CREATE TABLE IF NOT EXISTS paste_discussion_threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paste_id TEXT NOT NULL,
    user_id INTEGER DEFAULT NULL,           -- NULL for anonymous users
    username TEXT DEFAULT 'Anonymous',      -- Store username directly for anonymous users
    title TEXT NOT NULL,
    category TEXT NOT NULL CHECK (category IN ('Q&A', 'Tip', 'Idea', 'Bug', 'General')),
    created_at INTEGER DEFAULT (strftime('%s', 'now')),
    FOREIGN KEY (paste_id) REFERENCES pastes(id) ON DELETE CASCADE
);

-- Individual posts within discussion threads
CREATE TABLE IF NOT EXISTS paste_discussion_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER NOT NULL,
    user_id INTEGER DEFAULT NULL,           -- NULL for anonymous users
    username TEXT DEFAULT 'Anonymous',      -- Store username directly for anonymous users
    content TEXT NOT NULL,
    created_at INTEGER DEFAULT (strftime('%s', 'now')),
    is_deleted INTEGER DEFAULT 0,
    FOREIGN KEY (thread_id) REFERENCES paste_discussion_threads(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_discussion_threads_paste_id ON paste_discussion_threads(paste_id);
CREATE INDEX IF NOT EXISTS idx_discussion_posts_thread_id ON paste_discussion_posts(thread_id);
CREATE INDEX IF NOT EXISTS idx_discussion_threads_created_at ON paste_discussion_threads(created_at);
CREATE INDEX IF NOT EXISTS idx_discussion_posts_created_at ON paste_discussion_posts(created_at);

-- Insert default paste templates
INSERT OR IGNORE INTO "paste_templates" ("id", "name", "description", "content", "language", "category") VALUES
(1, 'HTML Template', 'Basic HTML5 document structure', '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Hello World</h1>
</body>
</html>', 'html', 'web'),

(2, 'CSS Reset', 'Basic CSS reset and styling', '/* CSS Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.6;
    color: #333;
}', 'css', 'web'),

(3, 'JavaScript Function', 'Basic JavaScript function template', '// JavaScript Function Template
function myFunction(param1, param2) {
    // Your code here
    console.log("Function called with:", param1, param2);
    
    return param1 + param2;
}

// Usage example
const result = myFunction(5, 10);
console.log("Result:", result);', 'javascript', 'web'),

(4, 'PHP Class', 'Basic PHP class structure', '<?php

class MyClass {
    private $property;
    
    public function __construct($value) {
        $this->property = $value;
    }
    
    public function getProperty() {
        return $this->property;
    }
    
    public function setProperty($value) {
        $this->property = $value;
    }
}

// Usage
$obj = new MyClass("Hello World");
echo $obj->getProperty();

?>', 'php', 'backend'),

(5, 'SQL Query', 'Common SQL query patterns', '-- Select with conditions
SELECT 
    id, 
    name, 
    email, 
    created_at
FROM users 
WHERE status = "active" 
    AND created_at > DATE("now", "-30 days")
ORDER BY created_at DESC
LIMIT 10;

-- Insert new record
INSERT INTO users (name, email, status) 
VALUES ("John Doe", "john@example.com", "active");

-- Update existing record
UPDATE users 
SET status = "inactive" 
WHERE last_login < DATE("now", "-90 days");', 'sql', 'data');

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS "idx_pastes_created_at" ON "pastes" ("created_at");
CREATE INDEX IF NOT EXISTS "idx_pastes_expire_time" ON "pastes" ("expire_time");
CREATE INDEX IF NOT EXISTS "idx_pastes_is_public" ON "pastes" ("is_public");
CREATE INDEX IF NOT EXISTS "idx_pastes_language" ON "pastes" ("language");
CREATE INDEX IF NOT EXISTS "idx_paste_views_paste_id" ON "paste_views" ("paste_id");
CREATE INDEX IF NOT EXISTS "idx_paste_views_ip_address" ON "paste_views" ("ip_address");

COMMIT;