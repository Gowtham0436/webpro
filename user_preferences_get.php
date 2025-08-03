<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Handle GET requests for testing
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'get_user_profile') {
            // Simulate user login if not logged in
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = 'testuser';
                $_SESSION['role'] = 'user';
            }
            
            $userId = $_SESSION['user_id'];
            
            // Get user basic info
            $userStmt = $pdo->prepare("
                SELECT user_id, username, email, role, registration_date, last_login
                FROM users 
                WHERE user_id = ?
            ");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
            // Get user preferences (create default if not exists)
            $prefStmt = $pdo->prepare("
                SELECT 
                    up.*,
                    bi.image_name,
                    bi.image_url as background_image_path
                FROM user_preferences up
                LEFT JOIN background_images bi ON up.preferred_background_image_id = bi.image_id
                WHERE up.user_id = ?
            ");
            $prefStmt->execute([$userId]);
            $preferences = $prefStmt->fetch();
            
            if (!$preferences) {
                // Create default preferences with correct structure
                $defaultStmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, default_puzzle_size, preferred_background_image_id, sound_enabled, animations_enabled)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $defaultStmt->execute([$userId, '4x4', 1, 1, 1]);
                
                // Fetch the newly created preferences
                $prefStmt->execute([$userId]);
                $preferences = $prefStmt->fetch();
                
                if (!$preferences) {
                    // If still no preferences, create a default array
                    $preferences = [
                        'preference_id' => null,
                        'user_id' => $userId,
                        'default_puzzle_size' => '4x4',
                        'preferred_background_image_id' => 1,
                        'sound_enabled' => 1,
                        'animations_enabled' => 1,
                        'image_name' => 'Default',
                        'background_image_path' => 'background.jpg'
                    ];
                }
            }
            
            // Get game statistics
            $statsStmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_games,
                    SUM(CASE WHEN win_status = 1 THEN 1 ELSE 0 END) as total_wins,
                    AVG(CASE WHEN win_status = 1 THEN time_taken_seconds ELSE NULL END) as avg_win_time,
                    MIN(CASE WHEN win_status = 1 THEN time_taken_seconds ELSE NULL END) as best_time,
                    AVG(CASE WHEN win_status = 1 THEN moves_count ELSE NULL END) as avg_moves,
                    MIN(CASE WHEN win_status = 1 THEN moves_count ELSE NULL END) as best_moves,
                    MAX(game_date) as last_game_date
                FROM game_stats 
                WHERE user_id = ?
            ");
            $statsStmt->execute([$userId]);
            $stats = $statsStmt->fetch();
            
            // Get recent games
            $recentStmt = $pdo->prepare("
                SELECT 
                    stat_id, puzzle_size, time_taken_seconds, moves_count, win_status, game_date,
                    COALESCE(bi.image_name, 'Default') as background_name
                FROM game_stats gs
                LEFT JOIN background_images bi ON gs.background_image_id = bi.image_id
                WHERE gs.user_id = ? 
                ORDER BY gs.game_date DESC 
                LIMIT 10
            ");
            $recentStmt->execute([$userId]);
            $recentGames = $recentStmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'profile' => [
                    'user' => $user,
                    'preferences' => $preferences,
                    'statistics' => $stats,
                    'recent_games' => $recentGames
                ]
            ]);
            
        } elseif ($action === 'get_available_backgrounds') {
            // Get available backgrounds
            $stmt = $pdo->query("
                SELECT image_id, image_name, image_url
                FROM background_images 
                WHERE is_active = 1 
                ORDER BY image_name
            ");
            $backgrounds = $stmt->fetchAll();
            
            // If no backgrounds exist, create a default one
            if (empty($backgrounds)) {
                $insertStmt = $pdo->prepare("
                    INSERT INTO background_images (image_id, image_name, image_url, is_active) 
                    VALUES (1, 'Default Background', 'background.jpg', 1)
                    ON DUPLICATE KEY UPDATE image_name = VALUES(image_name)
                ");
                $insertStmt->execute();
                
                $backgrounds = [[
                    'image_id' => 1,
                    'image_name' => 'Default Background', 
                    'image_url' => 'background.jpg'
                ]];
            }
            
            echo json_encode([
                'success' => true,
                'backgrounds' => $backgrounds
            ]);
            
        } elseif ($action === 'save_preferences') {
            // Save preferences via GET (for testing)
            $userId = $_SESSION['user_id'] ?? 1;
            $puzzleSize = $_GET['puzzle_size'] ?? '4x4';
            $backgroundId = $_GET['background_id'] ?? 1;
            $soundEnabled = $_GET['sound_enabled'] ?? '1';
            $animationsEnabled = $_GET['animations_enabled'] ?? '1';
            
            // Check if preferences exist for this user
            $checkStmt = $pdo->prepare("SELECT preference_id FROM user_preferences WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                // Update existing preferences
                $stmt = $pdo->prepare("
                    UPDATE user_preferences 
                    SET default_puzzle_size = ?, 
                        preferred_background_image_id = ?, 
                        sound_enabled = ?, 
                        animations_enabled = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$puzzleSize, $backgroundId, $soundEnabled, $animationsEnabled, $userId]);
            } else {
                // Insert new preferences
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, default_puzzle_size, preferred_background_image_id, sound_enabled, animations_enabled)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $puzzleSize, $backgroundId, $soundEnabled, $animationsEnabled]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences saved successfully'
            ]);
            
        } elseif ($action === 'update_user_preferences') {
            // Same as save_preferences but different action name (for profile page)
            $userId = $_SESSION['user_id'] ?? 1;
            $puzzleSize = $_GET['default_puzzle_size'] ?? '4x4';
            $backgroundId = $_GET['preferred_background_image_id'] ?? 1;
            $soundEnabled = $_GET['sound_enabled'] ?? '0';
            $animationsEnabled = $_GET['animations_enabled'] ?? '0';
            
            // Check if preferences exist for this user
            $checkStmt = $pdo->prepare("SELECT preference_id FROM user_preferences WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            $exists = $checkStmt->fetch();
            
            if ($exists) {
                // Update existing preferences
                $stmt = $pdo->prepare("
                    UPDATE user_preferences 
                    SET default_puzzle_size = ?, 
                        preferred_background_image_id = ?, 
                        sound_enabled = ?, 
                        animations_enabled = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$puzzleSize, $backgroundId, $soundEnabled, $animationsEnabled, $userId]);
            } else {
                // Insert new preferences
                $stmt = $pdo->prepare("
                    INSERT INTO user_preferences (user_id, default_puzzle_size, preferred_background_image_id, sound_enabled, animations_enabled)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $puzzleSize, $backgroundId, $soundEnabled, $animationsEnabled]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences updated successfully'
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Only GET requests supported in test mode']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
