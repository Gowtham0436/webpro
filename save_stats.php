<?php
session_start();
require_once 'config.php';

// Set JSON response header
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }

    // Validate required fields
    $required_fields = ['time_taken', 'moves_count', 'background_used'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $user_id = $_SESSION['user_id'];
    $time_taken = intval($input['time_taken']); // in seconds
    $moves_count = intval($input['moves_count']);
    $background_used = trim($input['background_used']);
    $difficulty = $input['difficulty'] ?? 'normal';

    // Validate data
    if ($time_taken <= 0) {
        throw new Exception('Invalid time taken');
    }

    if ($moves_count <= 0) {
        throw new Exception('Invalid moves count');
    }

    // Connect to database
    $pdo = getDBConnection();

    // Insert game statistics
    $stmt = $pdo->prepare("
        INSERT INTO game_stats 
        (user_id, time_taken, moves_count, background_used, difficulty, completed_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $success = $stmt->execute([
        $user_id, 
        $time_taken, 
        $moves_count, 
        $background_used, 
        $difficulty
    ]);

    if ($success) {
        $game_id = $pdo->lastInsertId();
        
        // Get user's best time for this difficulty
        $bestStmt = $pdo->prepare("
            SELECT MIN(time_taken) as best_time, MIN(moves_count) as best_moves 
            FROM game_stats 
            WHERE user_id = ? AND difficulty = ?
        ");
        $bestStmt->execute([$user_id, $difficulty]);
        $best = $bestStmt->fetch();

        $response = [
            'success' => true,
            'message' => 'Game statistics saved successfully',
            'game_id' => $game_id,
            'is_best_time' => ($best['best_time'] == $time_taken),
            'is_best_moves' => ($best['best_moves'] == $moves_count),
            'best_time' => $best['best_time'],
            'best_moves' => $best['best_moves']
        ];

        echo json_encode($response);
    } else {
        throw new Exception('Failed to save game statistics');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
