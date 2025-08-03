<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

session_start();
require_once 'config.php';

// Set JSON response header first
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $pdo = getDBConnection();
    
    // Check if user is authenticated (except for some public actions)
    if (!isset($_SESSION['user_id'])) {
        // For now, let's allow unauthenticated access and return an error
        // This helps with debugging
        error_log('No user session found in user_preferences_api.php');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request');
    }
    
    $action = $input['action'];
    
    if ($action === 'get_user_preferences') {
        // Get user preferences
        $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('User ID required');
        }
        
        // Get user preferences with background image details
        $stmt = $pdo->prepare("
            SELECT 
                up.*,
                bi.image_name,
                bi.file_path as background_image_path
            FROM user_preferences up
            LEFT JOIN background_images bi ON up.preferred_background_image_id = bi.image_id
            WHERE up.user_id = ?
        ");
        $stmt->execute([$userId]);
        $preferences = $stmt->fetch();
        
        if (!$preferences) {
            // Create default preferences if they don't exist
            $defaultStmt = $pdo->prepare("
                INSERT INTO user_preferences (user_id, default_puzzle_size, preferred_background_image_id, sound_enabled, animations_enabled) 
                VALUES (?, '4x4', 1, TRUE, TRUE)
            ");
            $defaultStmt->execute([$userId]);
            
            // Fetch the newly created preferences
            $stmt->execute([$userId]);
            $preferences = $stmt->fetch();
        }
        
        echo json_encode([
            'success' => true,
            'preferences' => $preferences
        ]);
        
    } elseif ($action === 'update_user_preferences') {
        // Update user preferences
        $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('User ID required');
        }
        
        $defaultPuzzleSize = $input['default_puzzle_size'] ?? '4x4';
        $preferredBackgroundId = $input['preferred_background_image_id'] ?? 1;
        $soundEnabled = isset($input['sound_enabled']) ? (bool)$input['sound_enabled'] : true;
        $animationsEnabled = isset($input['animations_enabled']) ? (bool)$input['animations_enabled'] : true;
        
        // Validate puzzle size
        $validSizes = ['3x3', '4x4', '5x5','6x6'];
        if (!in_array($defaultPuzzleSize, $validSizes)) {
            throw new Exception('Invalid puzzle size');
        }
        
        // Verify background image exists
        $bgStmt = $pdo->prepare("SELECT image_id FROM background_images WHERE image_id = ? AND is_active = 1");
        $bgStmt->execute([$preferredBackgroundId]);
        if (!$bgStmt->fetch()) {
            throw new Exception('Invalid background image');
        }
        
        // Update preferences
        $stmt = $pdo->prepare("
            UPDATE user_preferences 
            SET default_puzzle_size = ?, preferred_background_image_id = ?, sound_enabled = ?, animations_enabled = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$defaultPuzzleSize, $preferredBackgroundId, $soundEnabled ? 1 : 0, $animationsEnabled ? 1 : 0, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Preferences updated successfully'
        ]);
        
    } elseif ($action === 'get_available_backgrounds') {
        // Get all available background images
        $stmt = $pdo->prepare("
            SELECT image_id, image_name, file_path, upload_date
            FROM background_images 
            WHERE is_active = 1 
            ORDER BY image_name
        ");
        $stmt->execute();
        $backgrounds = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'backgrounds' => $backgrounds
        ]);
        
    } elseif ($action === 'get_user_profile') {
        // Get comprehensive user profile with stats
        $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('User ID required');
        }
        
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
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    // Log the actual error for debugging
    error_log("User Preferences API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'session_id' => session_id(),
            'session_user_id' => $_SESSION['user_id'] ?? 'not set',
            'request_action' => $action ?? 'not set'
        ]
    ]);
}
?>
