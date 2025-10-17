-- Database Schema Updates for Admin Panel
-- Run this SQL to add missing columns

USE kos_management;

-- Add priority and reply columns to complaints table (if not exists)
ALTER TABLE complaints 
ADD COLUMN IF NOT EXISTS priority ENUM('low','medium','high') DEFAULT 'medium' AFTER status,
ADD COLUMN IF NOT EXISTS reply TEXT AFTER description;

-- Add category and is_published columns to announcements table (if not exists)
ALTER TABLE announcements 
ADD COLUMN IF NOT EXISTS category ENUM('info','important','maintenance','event') DEFAULT 'info' AFTER content,
ADD COLUMN IF NOT EXISTS is_published TINYINT(1) DEFAULT 1 AFTER category;

-- Update existing data
UPDATE complaints SET priority = 'medium' WHERE priority IS NULL;
UPDATE announcements SET category = 'info' WHERE category IS NULL;
UPDATE announcements SET is_published = 1 WHERE is_published IS NULL;

-- Verify changes
SHOW COLUMNS FROM complaints;
SHOW COLUMNS FROM announcements;

SELECT 'Database schema updated successfully!' as Status;
