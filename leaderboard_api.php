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
    
    switch ($action) {
        case 'get_global_leaderboards':
            getGlobalLeaderboards($pdo, $input);
            break;
            
        case 'get_user_profile':
            getUserProfile($pdo, $input);
            break;
            
        case 'get_puzzle_size_stats':
            getPuzzleSizeStats($pdo, $input);
            break;
            
        case 'get_recent_achievements':
            getRecentAchievements($pdo, $input);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getGlobalLeaderboards($pdo, $input) {
    $puzzleSize = $input['puzzle_size'] ?? '4x4';
    $category = $input['category'] ?? 'all'; // all, best_time, best_moves, most_wins
    $limit = min($input['limit'] ?? 10, 50); // Max 50 results
    
    $leaderboards = [];
    
    // Best Times Leaderboard
    if ($category === 'best_time' || $category === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                u.user_id,
                MIN(gs.time_taken_seconds) as best_time,
                MIN(gs.moves_count) as best_moves,
                COUNT(*) as total_wins,
                MAX(gs.game_date) as last_achievement,
                ROW_NUMBER() OVER (ORDER BY MIN(gs.time_taken_seconds) ASC) as rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 AND gs.puzzle_size = ?
            GROUP BY gs.user_id, u.username, u.user_id
            ORDER BY best_time ASC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['best_time'] = $stmt->fetchAll();
    }
    
    // Best Moves Leaderboard
    if ($category === 'best_moves' || $category === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                u.user_id,
                MIN(gs.moves_count) as best_moves,
                MIN(gs.time_taken_seconds) as best_time,
                COUNT(*) as total_wins,
                MAX(gs.game_date) as last_achievement,
                ROW_NUMBER() OVER (ORDER BY MIN(gs.moves_count) ASC) as rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 AND gs.puzzle_size = ?
            GROUP BY gs.user_id, u.username, u.user_id
            ORDER BY best_moves ASC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['best_moves'] = $stmt->fetchAll();
    }
    
    // Most Wins Leaderboard
    if ($category === 'most_wins' || $category === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                u.user_id,
                COUNT(*) as total_wins,
                MIN(gs.time_taken_seconds) as best_time,
                MIN(gs.moves_count) as best_moves,
                AVG(gs.time_taken_seconds) as avg_time,
                AVG(gs.moves_count) as avg_moves,
                MAX(gs.game_date) as last_win,
                ROW_NUMBER() OVER (ORDER BY COUNT(*) DESC) as rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 AND gs.puzzle_size = ?
            GROUP BY gs.user_id, u.username, u.user_id
            ORDER BY total_wins DESC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['most_wins'] = $stmt->fetchAll();
    }
    
    // Weekly Champions (Best this week)
    if ($category === 'weekly' || $category === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                u.user_id,
                MIN(gs.time_taken_seconds) as best_time_week,
                MIN(gs.moves_count) as best_moves_week,
                COUNT(*) as wins_this_week,
                MAX(gs.game_date) as latest_game,
                ROW_NUMBER() OVER (ORDER BY MIN(gs.time_taken_seconds) ASC) as time_rank,
                ROW_NUMBER() OVER (ORDER BY MIN(gs.moves_count) ASC) as moves_rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 
                AND gs.puzzle_size = ?
                AND gs.game_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
            GROUP BY gs.user_id, u.username, u.user_id
            ORDER BY best_time_week ASC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['weekly'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'leaderboards' => $leaderboards,
        'puzzle_size' => $puzzleSize,
        'category' => $category,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
}

function getUserProfile($pdo, $input) {
    $userId = $input['user_id'] ?? $_SESSION['user_id'] ?? null;
    $targetUsername = $input['username'] ?? null;
    
    if (!$userId && !$targetUsername) {
        throw new Exception('User ID or username required');
    }
    
    // Get user basic info
    if ($targetUsername) {
        $stmt = $pdo->prepare("SELECT user_id, username, registration_date FROM users WHERE username = ?");
        $stmt->execute([$targetUsername]);
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username, registration_date FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    $user = $stmt->fetch();
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $userId = $user['user_id'];
    
    // Get comprehensive stats for all puzzle sizes
    $stmt = $pdo->prepare("
        SELECT 
            puzzle_size,
            COUNT(*) as total_games,
            SUM(win_status) as total_wins,
            ROUND(AVG(time_taken_seconds), 2) as avg_time,
            ROUND(AVG(moves_count), 2) as avg_moves,
            MIN(CASE WHEN win_status = 1 THEN time_taken_seconds END) as best_time,
            MIN(CASE WHEN win_status = 1 THEN moves_count END) as best_moves,
            MAX(game_date) as last_played,
            ROUND((SUM(win_status) / COUNT(*)) * 100, 2) as win_rate
        FROM game_stats 
        WHERE user_id = ?
        GROUP BY puzzle_size
        ORDER BY puzzle_size
    ");
    $stmt->execute([$userId]);
    $puzzleStats = $stmt->fetchAll();
    
    // Get recent games (last 10)
    $stmt = $pdo->prepare("
        SELECT 
            puzzle_size,
            time_taken_seconds,
            moves_count,
            win_status,
            game_date
        FROM game_stats 
        WHERE user_id = ?
        ORDER BY game_date DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentGames = $stmt->fetchAll();
    
    // Get user rankings for 4x4 (most common)
    $rankings = [];
    foreach (['4x4', '3x3', '5x5', '6x6'] as $size) {
        $rankings[$size] = getUserRankings($pdo, $userId, $size);
    }
    
    // Get achievements/milestones
    $achievements = [];
    
    // Check for various achievements
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_wins FROM game_stats WHERE user_id = ? AND win_status = 1");
    $stmt->execute([$userId]);
    $totalWins = $stmt->fetch()['total_wins'];
    
    if ($totalWins >= 100) $achievements[] = "Century Club (100+ wins)";
    if ($totalWins >= 50) $achievements[] = "Half Century (50+ wins)";
    if ($totalWins >= 10) $achievements[] = "Getting Good (10+ wins)";
    
    // Check for speed achievements
    $stmt = $pdo->prepare("SELECT MIN(time_taken_seconds) as fastest FROM game_stats WHERE user_id = ? AND win_status = 1 AND puzzle_size = '4x4'");
    $stmt->execute([$userId]);
    $fastest = $stmt->fetch()['fastest'];
    
    if ($fastest && $fastest < 60) $achievements[] = "Speed Demon (Sub-60 seconds)";
    if ($fastest && $fastest < 30) $achievements[] = "Lightning Fast (Sub-30 seconds)";
    
    echo json_encode([
        'success' => true,
        'user' => $user,
        'puzzle_stats' => $puzzleStats,
        'recent_games' => $recentGames,
        'rankings' => $rankings,
        'achievements' => $achievements,
        'total_wins' => $totalWins
    ]);
}

function getUserRankings($pdo, $userId, $puzzleSize) {
    $rankings = [];
    
    // Get user's best stats
    $stmt = $pdo->prepare("
        SELECT 
            MIN(CASE WHEN win_status = 1 THEN time_taken_seconds END) as best_time,
            MIN(CASE WHEN win_status = 1 THEN moves_count END) as best_moves,
            SUM(win_status) as total_wins
        FROM game_stats 
        WHERE user_id = ? AND puzzle_size = ?
    ");
    $stmt->execute([$userId, $puzzleSize]);
    $userStats = $stmt->fetch();
    
    if (!$userStats['best_time'] && !$userStats['best_moves']) {
        return null; // No wins for this puzzle size
    }
    
    // Time ranking
    if ($userStats['best_time']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) + 1 as time_rank
            FROM game_stats
            WHERE win_status = 1 
                AND puzzle_size = ?
                AND user_id IN (
                    SELECT user_id
                    FROM game_stats
                    WHERE win_status = 1 AND puzzle_size = ?
                    GROUP BY user_id
                    HAVING MIN(time_taken_seconds) < ?
                )
        ");
        $stmt->execute([$puzzleSize, $puzzleSize, $userStats['best_time']]);
        $rankings['time_rank'] = $stmt->fetch()['time_rank'];
    }
    
    // Moves ranking
    if ($userStats['best_moves']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT user_id) + 1 as moves_rank
            FROM game_stats
            WHERE win_status = 1 
                AND puzzle_size = ?
                AND user_id IN (
                    SELECT user_id
                    FROM game_stats
                    WHERE win_status = 1 AND puzzle_size = ?
                    GROUP BY user_id
                    HAVING MIN(moves_count) < ?
                )
        ");
        $stmt->execute([$puzzleSize, $puzzleSize, $userStats['best_moves']]);
        $rankings['moves_rank'] = $stmt->fetch()['moves_rank'];
    }
    
    // Wins ranking
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) + 1 as wins_rank
        FROM game_stats
        WHERE win_status = 1 
            AND puzzle_size = ?
            AND user_id IN (
                SELECT user_id
                FROM game_stats
                WHERE win_status = 1 AND puzzle_size = ?
                GROUP BY user_id
                HAVING COUNT(*) > ?
            )
    ");
    $stmt->execute([$puzzleSize, $puzzleSize, $userStats['total_wins']]);
    $rankings['wins_rank'] = $stmt->fetch()['wins_rank'];
    
    return array_merge($userStats, $rankings);
}

function getPuzzleSizeStats($pdo, $input) {
    $stmt = $pdo->query("
        SELECT 
            puzzle_size,
            COUNT(*) as total_games,
            SUM(win_status) as total_wins,
            COUNT(DISTINCT user_id) as unique_players,
            ROUND(AVG(time_taken_seconds), 2) as avg_time,
            ROUND(AVG(moves_count), 2) as avg_moves,
            MIN(CASE WHEN win_status = 1 THEN time_taken_seconds END) as best_time_ever,
            MIN(CASE WHEN win_status = 1 THEN moves_count END) as best_moves_ever,
            ROUND((SUM(win_status) / COUNT(*)) * 100, 2) as overall_win_rate
        FROM game_stats 
        GROUP BY puzzle_size
        ORDER BY puzzle_size
    ");
    
    $stats = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'puzzle_stats' => $stats
    ]);
}

function getRecentAchievements($pdo, $input) {
    $limit = min($input['limit'] ?? 10, 20);
    
    // Get recent best times achieved
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            gs.puzzle_size,
            gs.time_taken_seconds,
            gs.moves_count,
            gs.game_date,
            'best_time' as achievement_type
        FROM game_stats gs
        JOIN users u ON gs.user_id = u.user_id
        WHERE gs.win_status = 1
            AND gs.game_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND gs.time_taken_seconds = (
                SELECT MIN(time_taken_seconds)
                FROM game_stats gs2
                WHERE gs2.user_id = gs.user_id 
                    AND gs2.puzzle_size = gs.puzzle_size 
                    AND gs2.win_status = 1
            )
        ORDER BY gs.game_date DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    $recentAchievements = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'recent_achievements' => $recentAchievements
    ]);
}
?>
