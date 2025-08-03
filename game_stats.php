<?php
session_start();
require_once 'config.php';

// Set JSON response header
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
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request');
    }
    
    $action = $input['action'];
    
    if ($action === 'save_game_stats') {
        // Validate required fields
        $userId = $input['user_id'] ?? null;
        $puzzleSize = $input['puzzle_size'] ?? '4x4';
        $timeSeconds = $input['time_taken_seconds'] ?? null;
        $movesCount = $input['moves_count'] ?? null;
        $backgroundImageId = $input['background_image_id'] ?? 1;
        $winStatus = $input['win_status'] ?? false;
        
        if (!$userId || $timeSeconds === null || $movesCount === null) {
            throw new Exception('Missing required fields');
        }
        
        // Verify user exists
        $userStmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $userStmt->execute([$userId]);
        if (!$userStmt->fetch()) {
            throw new Exception('Invalid user');
        }
        
        // Insert game stats
        $stmt = $pdo->prepare("
            INSERT INTO game_stats (user_id, puzzle_size, time_taken_seconds, moves_count, background_image_id, win_status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $puzzleSize, $timeSeconds, $movesCount, $backgroundImageId, $winStatus ? 1 : 0]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Game stats saved successfully',
            'stat_id' => $pdo->lastInsertId()
        ]);
        
    } elseif ($action === 'get_user_stats') {
        // Get user statistics
        $userId = $input['user_id'] ?? null;
        $limit = $input['limit'] ?? 10;
        
        if (!$userId) {
            throw new Exception('User ID required');
        }
        
        // Get recent games
        $stmt = $pdo->prepare("
            SELECT stat_id, puzzle_size, time_taken_seconds, moves_count, win_status, game_date
            FROM game_stats 
            WHERE user_id = ? 
            ORDER BY game_date DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        $recentGames = $stmt->fetchAll();
        
        // Get user statistics summary
        $summaryStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_games,
                SUM(CASE WHEN win_status = 1 THEN 1 ELSE 0 END) as games_won,
                AVG(CASE WHEN win_status = 1 THEN time_taken_seconds ELSE NULL END) as avg_win_time,
                AVG(CASE WHEN win_status = 1 THEN moves_count ELSE NULL END) as avg_win_moves,
                MIN(CASE WHEN win_status = 1 THEN time_taken_seconds ELSE NULL END) as best_time,
                MIN(CASE WHEN win_status = 1 THEN moves_count ELSE NULL END) as best_moves
            FROM game_stats 
            WHERE user_id = ?
        ");
        $summaryStmt->execute([$userId]);
        $summary = $summaryStmt->fetch();
        
        echo json_encode([
            'success' => true,
            'recent_games' => $recentGames,
            'summary' => $summary
        ]);
        
    } elseif ($action === 'get_leaderboard') {
        // Get global leaderboard
        $type = $input['type'] ?? 'best_time'; // best_time, best_moves, most_wins
        $limit = $input['limit'] ?? 10;
        
        if ($type === 'best_time') {
            $stmt = $pdo->prepare("
                SELECT u.username, MIN(gs.time_taken_seconds) as best_time, MIN(gs.moves_count) as moves, MAX(gs.game_date) as date
                FROM game_stats gs
                JOIN users u ON gs.user_id = u.user_id
                WHERE gs.win_status = 1
                GROUP BY gs.user_id, u.username
                ORDER BY best_time ASC
                LIMIT ?
            ");
        } elseif ($type === 'best_moves') {
            $stmt = $pdo->prepare("
                SELECT u.username, MIN(gs.moves_count) as best_moves, MIN(gs.time_taken_seconds) as time, MAX(gs.game_date) as date
                FROM game_stats gs
                JOIN users u ON gs.user_id = u.user_id
                WHERE gs.win_status = 1
                GROUP BY gs.user_id, u.username
                ORDER BY best_moves ASC
                LIMIT ?
            ");
        } else { // most_wins
            $stmt = $pdo->prepare("
                SELECT u.username, COUNT(*) as total_wins, AVG(gs.time_taken_seconds) as avg_time, AVG(gs.moves_count) as avg_moves
                FROM game_stats gs
                JOIN users u ON gs.user_id = u.user_id
                WHERE gs.win_status = 1
                GROUP BY gs.user_id, u.username
                ORDER BY total_wins DESC
                LIMIT ?
            ");
        }
        
        $stmt->execute([$limit]);
        $leaderboard = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'leaderboard' => $leaderboard,
            'type' => $type
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
