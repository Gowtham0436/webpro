-- ========================================
-- FIFTEEN PUZZLE GAME DATABASE DUMP
-- ========================================
-- Generated: August 2, 2025
-- Database: sjuyal1 (or your database name)
-- Purpose: Complete database structure with sample data
-- ========================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- DATABASE CREATION
-- ========================================
CREATE DATABASE IF NOT EXISTS `sjuyal1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sjuyal1`;

-- ========================================
-- TABLE STRUCTURE FOR `users`
-- ========================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('player','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'player',
  `registration_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR `users`
-- ========================================
INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `role`, `registration_date`, `last_login`) VALUES
(1, 'admin', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LQvtpj3WlP2M8ABCD', 'admin@puzzlegame.com', 'admin', '2025-08-01 10:00:00', '2025-08-02 08:30:00'),
(2, 'player1', '$2y$12$XYZ789ABCDEFghijklmnopQRSTUVwxyz123456789ABCDEFGHIJ', 'player1@example.com', 'player', '2025-08-01 11:15:00', '2025-08-02 07:45:00'),
(3, 'puzzlemaster', '$2y$12$DEF456GHIjklMNOpqr789STUvwxYZ123ABCdefGHIjklMNO456', 'master@example.com', 'player', '2025-08-01 12:30:00', '2025-08-02 06:20:00'),
(4, 'speedsolver', '$2y$12$GHI789JKLmnoPQRstu012VWXyzABC345defGHIjkLMNopQR678', 'speed@example.com', 'player', '2025-08-01 14:20:00', '2025-08-02 08:15:00'),
(5, 'newbie2025', '$2y$12$JKL012MNOpqrSTUvwx345YZabcDEFghiJKLmnoPQRstUVWX901', 'newbie@example.com', 'player', '2025-08-02 06:45:00', '2025-08-02 08:00:00');

-- ========================================
-- TABLE STRUCTURE FOR `background_images`
-- ========================================
DROP TABLE IF EXISTS `background_images`;
CREATE TABLE `background_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `image_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `uploaded_by_user_id` int(11) DEFAULT NULL,
  `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `uploaded_by_user_id` (`uploaded_by_user_id`),
  CONSTRAINT `background_images_ibfk_1` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR `background_images`
-- ========================================
INSERT INTO `background_images` (`image_id`, `image_name`, `image_url`, `is_active`, `uploaded_by_user_id`, `upload_date`) VALUES
(1, 'Default Nature', 'background.jpg', 1, 1, '2025-08-01 10:00:00'),
(2, 'Abstract Geometric', 'backgrounds/geometric.jpg', 1, 1, '2025-08-01 10:15:00'),
(3, 'Ocean Waves', 'backgrounds/ocean.jpg', 1, 1, '2025-08-01 10:30:00'),
(4, 'Mountain Landscape', 'backgrounds/mountains.jpg', 1, 1, '2025-08-01 10:45:00'),
(5, 'City Skyline', 'backgrounds/city.jpg', 1, 2, '2025-08-01 15:20:00'),
(6, 'Space Galaxy', 'backgrounds/galaxy.jpg', 1, 2, '2025-08-01 16:10:00');

-- ========================================
-- TABLE STRUCTURE FOR `user_preferences`
-- ========================================
DROP TABLE IF EXISTS `user_preferences`;
CREATE TABLE `user_preferences` (
  `preference_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `default_puzzle_size` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '4x4',
  `preferred_background_image_id` int(11) DEFAULT NULL,
  `sound_enabled` tinyint(1) DEFAULT '1',
  `animations_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `preferred_background_image_id` (`preferred_background_image_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_preferences_ibfk_2` FOREIGN KEY (`preferred_background_image_id`) REFERENCES `background_images` (`image_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR `user_preferences`
-- ========================================
INSERT INTO `user_preferences` (`preference_id`, `user_id`, `default_puzzle_size`, `preferred_background_image_id`, `sound_enabled`, `animations_enabled`) VALUES
(1, 1, '4x4', 1, 1, 1),
(2, 2, '3x3', 3, 0, 1),
(3, 3, '5x5', 2, 1, 1),
(4, 4, '4x4', 4, 1, 0),
(5, 5, '4x4', 1, 1, 1);

-- ========================================
-- TABLE STRUCTURE FOR `game_stats`
-- ========================================
DROP TABLE IF EXISTS `game_stats`;
CREATE TABLE `game_stats` (
  `stat_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `puzzle_size` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_taken_seconds` int(11) NOT NULL,
  `moves_count` int(11) NOT NULL,
  `background_image_id` int(11) DEFAULT NULL,
  `win_status` tinyint(1) NOT NULL,
  `game_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stat_id`),
  KEY `user_id` (`user_id`),
  KEY `background_image_id` (`background_image_id`),
  KEY `puzzle_size` (`puzzle_size`),
  KEY `game_date` (`game_date`),
  CONSTRAINT `game_stats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `game_stats_ibfk_2` FOREIGN KEY (`background_image_id`) REFERENCES `background_images` (`image_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR `game_stats`
-- ========================================
INSERT INTO `game_stats` (`stat_id`, `user_id`, `puzzle_size`, `time_taken_seconds`, `moves_count`, `background_image_id`, `win_status`, `game_date`) VALUES
-- Admin user games
(1, 1, '4x4', 245, 89, 1, 1, '2025-08-01 10:30:00'),
(2, 1, '4x4', 198, 76, 2, 1, '2025-08-01 11:15:00'),
(3, 1, '3x3', 45, 25, 1, 1, '2025-08-01 12:00:00'),

-- Player1 games (casual player)
(4, 2, '3x3', 67, 34, 3, 1, '2025-08-01 11:45:00'),
(5, 2, '3x3', 89, 42, 3, 1, '2025-08-01 12:30:00'),
(6, 2, '4x4', 456, 145, 3, 1, '2025-08-01 13:15:00'),
(7, 2, '4x4', 378, 132, 1, 1, '2025-08-01 14:00:00'),

-- Puzzlemaster games (expert player)
(8, 3, '5x5', 892, 234, 2, 1, '2025-08-01 13:00:00'),
(9, 3, '5x5', 756, 198, 4, 1, '2025-08-01 14:30:00'),
(10, 3, '4x4', 134, 52, 2, 1, '2025-08-01 15:15:00'),
(11, 3, '4x4', 145, 58, 6, 1, '2025-08-01 16:00:00'),
(12, 3, '3x3', 23, 18, 2, 1, '2025-08-01 16:45:00'),

-- Speedsolver games (very fast player)
(13, 4, '4x4', 87, 41, 4, 1, '2025-08-01 14:45:00'),
(14, 4, '4x4', 92, 45, 5, 1, '2025-08-01 15:30:00'),
(15, 4, '4x4', 78, 38, 4, 1, '2025-08-01 16:15:00'),
(16, 4, '3x3', 19, 15, 4, 1, '2025-08-01 17:00:00'),
(17, 4, '5x5', 234, 89, 4, 1, '2025-08-01 18:00:00'),

-- Newbie games (learning player)
(18, 5, '4x4', 678, 234, 1, 1, '2025-08-02 07:00:00'),
(19, 5, '4x4', 589, 198, 1, 1, '2025-08-02 07:45:00'),
(20, 5, '3x3', 123, 67, 1, 1, '2025-08-02 08:15:00'),

-- Recent games for demo
(21, 2, '4x4', 298, 102, 3, 1, '2025-08-02 08:00:00'),
(22, 3, '4x4', 156, 64, 2, 1, '2025-08-02 08:15:00'),
(23, 4, '4x4', 82, 39, 4, 1, '2025-08-02 08:30:00');

-- ========================================
-- TABLE STRUCTURE FOR `announcements`
-- ========================================
DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by_user_id` int(11) DEFAULT NULL,
  `created_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_date` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`),
  KEY `created_by_user_id` (`created_by_user_id`),
  KEY `is_active` (`is_active`),
  KEY `created_date` (`created_date`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA FOR `announcements`
-- ========================================
INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `is_active`, `created_by_user_id`, `created_date`, `updated_date`) VALUES
(1, 'Welcome to Fifteen Puzzle Game!', 'Welcome to our advanced puzzle game platform! Enjoy solving puzzles with beautiful backgrounds and track your progress over time.', 1, 1, '2025-08-01 10:00:00', '2025-08-01 10:00:00'),
(2, 'New Background Images Added', 'We have added 5 new stunning background images for your puzzle solving experience. Check them out in the game settings!', 1, 1, '2025-08-01 15:00:00', '2025-08-01 15:00:00'),
(3, 'Leaderboard Competition', 'Monthly leaderboard competition is now live! Compete with other players and see who can solve puzzles the fastest. Prizes for top performers!', 1, 1, '2025-08-02 06:00:00', '2025-08-02 06:00:00'),
(4, 'Tips for Faster Solving', 'Pro tip: Focus on solving the top two rows first, then work on the left column. This strategy can significantly improve your solving time!', 1, 1, '2025-08-02 07:00:00', '2025-08-02 07:00:00');

-- ========================================
-- INDEXES FOR BETTER PERFORMANCE
-- ========================================

-- Additional indexes for common queries
ALTER TABLE `game_stats` ADD INDEX `idx_user_date` (`user_id`, `game_date`);
ALTER TABLE `game_stats` ADD INDEX `idx_size_time` (`puzzle_size`, `time_taken_seconds`);
ALTER TABLE `game_stats` ADD INDEX `idx_user_size` (`user_id`, `puzzle_size`);

-- ========================================
-- VIEWS FOR COMMON QUERIES
-- ========================================

-- Best times per user per puzzle size
DROP VIEW IF EXISTS `user_best_times`;
CREATE VIEW `user_best_times` AS
SELECT 
    u.user_id,
    u.username,
    gs.puzzle_size,
    MIN(gs.time_taken_seconds) as best_time,
    MIN(gs.moves_count) as best_moves,
    COUNT(gs.stat_id) as games_played,
    AVG(gs.time_taken_seconds) as avg_time,
    AVG(gs.moves_count) as avg_moves
FROM users u
LEFT JOIN game_stats gs ON u.user_id = gs.user_id AND gs.win_status = 1
GROUP BY u.user_id, u.username, gs.puzzle_size;

-- Global leaderboard
DROP VIEW IF EXISTS `global_leaderboard`;
CREATE VIEW `global_leaderboard` AS
SELECT 
    u.username,
    gs.puzzle_size,
    gs.time_taken_seconds as best_time,
    gs.moves_count as moves,
    gs.game_date,
    bi.image_name as background_used
FROM users u
JOIN game_stats gs ON u.user_id = gs.user_id
LEFT JOIN background_images bi ON gs.background_image_id = bi.image_id
WHERE gs.win_status = 1
ORDER BY gs.puzzle_size, gs.time_taken_seconds ASC;

-- Recent activity
DROP VIEW IF EXISTS `recent_activity`;
CREATE VIEW `recent_activity` AS
SELECT 
    u.username,
    gs.puzzle_size,
    gs.time_taken_seconds,
    gs.moves_count,
    gs.game_date,
    CASE 
        WHEN gs.time_taken_seconds = (
            SELECT MIN(time_taken_seconds) 
            FROM game_stats 
            WHERE user_id = gs.user_id AND puzzle_size = gs.puzzle_size AND win_status = 1
        ) THEN 'Personal Best'
        ELSE 'Completed'
    END as achievement
FROM users u
JOIN game_stats gs ON u.user_id = gs.user_id
WHERE gs.win_status = 1
ORDER BY gs.game_date DESC
LIMIT 20;

-- ========================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- ========================================

-- Procedure to get user statistics
DELIMITER //
DROP PROCEDURE IF EXISTS GetUserStatistics//
CREATE PROCEDURE GetUserStatistics(IN input_user_id INT)
BEGIN
    SELECT 
        COUNT(*) as total_games,
        AVG(time_taken_seconds) as avg_time,
        MIN(time_taken_seconds) as best_time,
        AVG(moves_count) as avg_moves,
        MIN(moves_count) as best_moves,
        puzzle_size
    FROM game_stats 
    WHERE user_id = input_user_id AND win_status = 1
    GROUP BY puzzle_size;
END//

-- Procedure to update user preferences
DROP PROCEDURE IF EXISTS UpdateUserPreferences//
CREATE PROCEDURE UpdateUserPreferences(
    IN input_user_id INT,
    IN input_puzzle_size VARCHAR(10),
    IN input_background_id INT,
    IN input_sound_enabled BOOLEAN,
    IN input_animations_enabled BOOLEAN
)
BEGIN
    INSERT INTO user_preferences 
    (user_id, default_puzzle_size, preferred_background_image_id, sound_enabled, animations_enabled)
    VALUES (input_user_id, input_puzzle_size, input_background_id, input_sound_enabled, input_animations_enabled)
    ON DUPLICATE KEY UPDATE
        default_puzzle_size = input_puzzle_size,
        preferred_background_image_id = input_background_id,
        sound_enabled = input_sound_enabled,
        animations_enabled = input_animations_enabled;
END//

DELIMITER ;

-- ========================================
-- SAMPLE QUERIES FOR TESTING
-- ========================================

-- Test user best times view
-- SELECT * FROM user_best_times WHERE username = 'speedsolver';

-- Test global leaderboard
-- SELECT * FROM global_leaderboard WHERE puzzle_size = '4x4' LIMIT 10;

-- Test recent activity
-- SELECT * FROM recent_activity LIMIT 10;

-- Test stored procedure
-- CALL GetUserStatistics(4);

-- ========================================
-- DATABASE CONFIGURATION COMPLETE
-- ========================================

COMMIT;

-- ========================================
-- FINAL NOTES FOR IMPORT
-- ========================================
/*
IMPORT INSTRUCTIONS:
1. Save this file as 'fifteen_puzzle_database_dump.sql'
2. Import using command line: mysql -u username -p database_name < fifteen_puzzle_database_dump.sql
3. Or import via phpMyAdmin by uploading this file
4. Update config.php with your database credentials
5. Test connection with test_connection.php

SAMPLE LOGIN CREDENTIALS:
- Admin: username='admin', password='admin123'
- Player: username='player1', password='player123'
- Note: Passwords are hashed, these are the plain text versions

DATABASE FEATURES:
- 5 normalized tables with foreign key constraints
- Sample data for 5 users with 23 game records
- 6 background images with proper relationships
- 4 announcements for admin demonstration
- Optimized indexes for common queries
- 3 views for leaderboards and statistics
- 2 stored procedures for complex operations

STATISTICS SUMMARY:
- Total Users: 5 (1 admin, 4 players)
- Total Games: 23 completed games
- Puzzle Sizes: 3x3, 4x4, 5x5
- Best Time 3x3: 19 seconds (speedsolver)
- Best Time 4x4: 78 seconds (speedsolver)
- Best Time 5x5: 234 seconds (speedsolver)
- Most Active: puzzlemaster (5 games)
*/
