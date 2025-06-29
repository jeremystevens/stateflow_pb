-- PasteForge Discussions Thread System Schema

-- Main discussion threads table
CREATE TABLE IF NOT EXISTS paste_discussion_threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    paste_id INTEGER NOT NULL,
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