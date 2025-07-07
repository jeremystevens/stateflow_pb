-- Rename the column to match what the backend expects
ALTER TABLE user_achievement_progress RENAME COLUMN last_updated TO updated_at;
