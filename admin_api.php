<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Handle file uploads differently
    if (isset($_POST['action']) && $_POST['action'] === 'upload_image') {
        handleImageUpload($pdo);
        exit;
    }
    
    // Get JSON input for other actions
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request');
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'get_users':
            getUsersData($pdo);
            break;
        
        case 'toggle_user_status':
            toggleUserStatus($pdo, $input);
            break;
            
        case 'get_images':
            getImagesData($pdo);
            break;
            
        case 'toggle_image_status':
            toggleImageStatus($pdo, $input);
            break;
            
        case 'delete_image':
            deleteImage($pdo, $input);
            break;
            
        case 'get_stats':
            getGameStats($pdo, $input);
            break;
            
        case 'get_announcements':
            getAnnouncements($pdo);
            break;
            
        case 'create_announcement':
            createAnnouncement($pdo, $input);
            break;
            
        case 'edit_announcement':
            editAnnouncement($pdo, $input);
            break;
            
        case 'toggle_announcement_status':
            toggleAnnouncementStatus($pdo, $input);
            break;
            
        case 'delete_announcement':
            deleteAnnouncement($pdo, $input);
            break;
            
        case 'get_leaderboards':
            getLeaderboards($pdo, $input);
            break;
            
        case 'get_user_rankings':
            getUserRankings($pdo, $input);
            break;
            
        case 'get_puzzle_sizes':
            getPuzzleSizes($pdo);
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

function getUsersData($pdo) {
    $stmt = $pdo->query("
        SELECT user_id, username, email, role, registration_date, last_login,
               CASE WHEN role = 'admin' OR last_login > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END as is_active
        FROM users 
        ORDER BY registration_date DESC
    ");
    
    $users = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
}

function toggleUserStatus($pdo, $input) {
    $userId = $input['user_id'];
    $isActive = $input['is_active'];
    
    // For this implementation, we'll use a simple approach:
    // Active = last_login within 30 days OR role is admin
    // Inactive = set last_login to NULL (for non-admin users)
    
    if ($isActive) {
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ? AND role != 'admin'");
    } else {
        // Don't allow deactivating admin users
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_login = NULL 
            WHERE user_id = ? AND role != 'admin'
        ");
    }
    
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'User status updated successfully'
    ]);
}

function handleImageUpload($pdo) {
    try {
        $imageName = $_POST['image_name'];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'File upload failed';
            if (isset($_FILES['image_file']['error'])) {
                switch($_FILES['image_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errorMsg .= ': File exceeds upload_max_filesize';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMsg .= ': File exceeds MAX_FILE_SIZE';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMsg .= ': File was only partially uploaded';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMsg .= ': No file was uploaded';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMsg .= ': Missing temporary folder';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMsg .= ': Failed to write file to disk';
                        break;
                    default:
                        $errorMsg .= ': Unknown upload error';
                }
            }
            throw new Exception($errorMsg);
        }
        
        $file = $_FILES['image_file'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
        }
        
        // Check file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'bg_' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;
        $urlPath = 'uploads/' . $filename; // Relative path for database storage
        
        // Debug information
        error_log("Upload attempt - Source: " . $file['tmp_name'] . ", Destination: " . $uploadPath);
        error_log("Upload dir exists: " . (is_dir($uploadDir) ? 'yes' : 'no'));
        error_log("Upload dir writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
        error_log("Temp file exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no'));
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $error = "Failed to save uploaded file. ";
            $error .= "Upload dir: " . $uploadDir . " ";
            $error .= "Writable: " . (is_writable($uploadDir) ? 'yes' : 'no') . " ";
            $error .= "Temp file: " . $file['tmp_name'] . " ";
            $error .= "Exists: " . (file_exists($file['tmp_name']) ? 'yes' : 'no');
            throw new Exception($error);
        }
        
        // Save to database
        $stmt = $pdo->prepare("
            INSERT INTO background_images (image_name, image_url, is_active, uploaded_by_user_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$imageName, $urlPath, $isActive, $_SESSION['user_id']]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'image_id' => $pdo->lastInsertId(),
            'image_url' => $urlPath
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getImagesData($pdo) {
    $stmt = $pdo->query("
        SELECT bg.*, u.username as uploaded_by_username
        FROM background_images bg
        LEFT JOIN users u ON bg.uploaded_by_user_id = u.user_id
        ORDER BY bg.upload_date DESC
    ");
    
    $images = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'images' => $images
    ]);
}

function toggleImageStatus($pdo, $input) {
    $imageId = $input['image_id'];
    $isActive = $input['is_active'] ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE background_images SET is_active = ? WHERE image_id = ?");
    $stmt->execute([$isActive, $imageId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Image status updated successfully'
    ]);
}

function deleteImage($pdo, $input) {
    $imageId = $input['image_id'];
    
    // Get image details first
    $stmt = $pdo->prepare("SELECT image_url FROM background_images WHERE image_id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        throw new Exception('Image not found');
    }
    
    // Don't delete the default image
    if ($imageId == 1) {
        throw new Exception('Cannot delete default image');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM background_images WHERE image_id = ?");
    $stmt->execute([$imageId]);
    
    // Delete file from filesystem
    if (file_exists($image['image_url']) && $image['image_url'] !== 'background.jpg') {
        unlink($image['image_url']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Image deleted successfully'
    ]);
}

function getGameStats($pdo, $input) {
    $filter = $input['filter'] ?? 'all';
    
    $whereClause = '';
    switch ($filter) {
        case 'today':
            $whereClause = "WHERE DATE(game_date) = CURDATE()";
            break;
        case 'week':
            $whereClause = "WHERE game_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $whereClause = "WHERE game_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
    }
    
    // Get overall statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT user_id) as total_players,
            COUNT(*) as total_games,
            AVG(time_taken_seconds) as average_time,
            AVG(win_status) * 100 as win_rate
        FROM game_stats 
        $whereClause
    ");
    $stats = $stmt->fetch();
    
    // Get recent games
    $stmt = $pdo->query("
        SELECT gs.*, u.username
        FROM game_stats gs
        JOIN users u ON gs.user_id = u.user_id
        $whereClause
        ORDER BY gs.game_date DESC
        LIMIT 20
    ");
    $recentGames = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recent_games' => $recentGames
    ]);
}

function getAnnouncements($pdo) {
    $stmt = $pdo->query("
        SELECT a.*, u.username as created_by_username
        FROM announcements a
        LEFT JOIN users u ON a.created_by_user_id = u.user_id
        ORDER BY a.created_date DESC
    ");
    
    $announcements = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);
}

function createAnnouncement($pdo, $input) {
    $title = trim($input['title']);
    $content = trim($input['content']);
    $isActive = $input['is_active'] ? 1 : 0;
    
    if (empty($title) || empty($content)) {
        throw new Exception('Title and content are required');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO announcements (title, content, is_active, created_by_user_id) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$title, $content, $isActive, $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Announcement created successfully'
    ]);
}

function editAnnouncement($pdo, $input) {
    try {
        if (!isset($input['announcement_id']) || !isset($input['title']) || !isset($input['content'])) {
            throw new Exception('Missing required parameters');
        }
        
        $announcementId = $input['announcement_id'];
        $title = trim($input['title']);
        $content = trim($input['content']);
        $isActive = isset($input['is_active']) ? ($input['is_active'] ? 1 : 0) : 1;
        
        if (empty($title) || empty($content)) {
            throw new Exception('Title and content are required');
        }
        
        // Check if announcement exists
        $checkStmt = $pdo->prepare("SELECT announcement_id FROM announcements WHERE announcement_id = ?");
        $checkStmt->execute([$announcementId]);
        if (!$checkStmt->fetch()) {
            throw new Exception("Announcement with ID $announcementId not found");
        }
        
        $stmt = $pdo->prepare("
            UPDATE announcements 
            SET title = ?, content = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE announcement_id = ?
        ");
        $result = $stmt->execute([$title, $content, $isActive, $announcementId]);
        
        if (!$result) {
            throw new Exception('Failed to update announcement');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Announcement updated successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

function toggleAnnouncementStatus($pdo, $input) {
    try {
        if (!isset($input['announcement_id'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Missing announcement_id parameter',
                'debug_received' => array_keys($input ?? [])
            ]);
            return;
        }
        if (!isset($input['is_active'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Missing is_active parameter',
                'debug_received' => array_keys($input ?? [])
            ]);
            return;
        }
        
        $announcementId = intval($input['announcement_id']);
        $isActive = $input['is_active'] ? 1 : 0;
        
        // Check if announcement exists
        $checkStmt = $pdo->prepare("SELECT announcement_id FROM announcements WHERE announcement_id = ?");
        $checkStmt->execute([$announcementId]);
        if (!$checkStmt->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => "Announcement with ID $announcementId not found"
            ]);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE announcement_id = ?");
        $result = $stmt->execute([$isActive, $announcementId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Announcement status updated successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to update announcement status - no rows affected'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Toggle error: ' . $e->getMessage()
        ]);
    }
}

function deleteAnnouncement($pdo, $input) {
    try {
        if (!isset($input['announcement_id'])) {
            echo json_encode([
                'success' => false, 
                'message' => 'Missing announcement_id parameter',
                'debug_received' => array_keys($input ?? [])
            ]);
            return;
        }
        
        $announcementId = intval($input['announcement_id']);
        
        // Check if announcement exists
        $checkStmt = $pdo->prepare("SELECT announcement_id FROM announcements WHERE announcement_id = ?");
        $checkStmt->execute([$announcementId]);
        if (!$checkStmt->fetch()) {
            echo json_encode([
                'success' => false, 
                'message' => "Announcement with ID $announcementId not found"
            ]);
            return;
        }
        
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        $result = $stmt->execute([$announcementId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Announcement deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Failed to delete announcement - no rows affected'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Delete error: ' . $e->getMessage()
        ]);
    }
}

function getLeaderboards($pdo, $input) {
    $puzzleSize = $input['puzzle_size'] ?? '4x4';
    $type = $input['type'] ?? 'best_time'; // best_time, best_moves, most_wins
    $limit = $input['limit'] ?? 20;
    
    $leaderboards = [];
    
    // Validate puzzle size format
    if (!preg_match('/^\d+x\d+$/', $puzzleSize)) {
        $puzzleSize = '4x4'; // Default fallback
    }
    
    // Global Best Times Leaderboard
    if ($type === 'best_time' || $type === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                MIN(gs.time_taken_seconds) as best_time,
                gs.moves_count as moves_for_best_time,
                gs.game_date as date_achieved,
                ROW_NUMBER() OVER (ORDER BY MIN(gs.time_taken_seconds) ASC) as rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 AND gs.puzzle_size = ?
            GROUP BY gs.user_id, u.username
            ORDER BY best_time ASC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['best_time'] = $stmt->fetchAll();
    }
    
    // Global Best Moves Leaderboard
    if ($type === 'best_moves' || $type === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                MIN(gs.moves_count) as best_moves,
                gs.time_taken_seconds as time_for_best_moves,
                gs.game_date as date_achieved,
                ROW_NUMBER() OVER (ORDER BY MIN(gs.moves_count) ASC) as rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 AND gs.puzzle_size = ?
            GROUP BY gs.user_id, u.username
            ORDER BY best_moves ASC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['best_moves'] = $stmt->fetchAll();
    }
    
    // Most Wins Leaderboard
    if ($type === 'most_wins' || $type === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                COUNT(*) as total_wins,
                AVG(gs.time_taken_seconds) as avg_time,
                AVG(gs.moves_count) as avg_moves,
                MIN(gs.time_taken_seconds) as best_time,
                MIN(gs.moves_count) as best_moves,
                ROW_NUMBER() OVER (ORDER BY COUNT(*) DESC) as rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 AND gs.puzzle_size = ?
            GROUP BY gs.user_id, u.username
            ORDER BY total_wins DESC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['most_wins'] = $stmt->fetchAll();
    }
    
    // Recent Best Performances (Last 7 days)
    if ($type === 'recent' || $type === 'all') {
        $stmt = $pdo->prepare("
            SELECT 
                u.username,
                gs.time_taken_seconds,
                gs.moves_count,
                gs.game_date,
                ROW_NUMBER() OVER (ORDER BY gs.time_taken_seconds ASC) as time_rank,
                ROW_NUMBER() OVER (ORDER BY gs.moves_count ASC) as moves_rank
            FROM game_stats gs
            JOIN users u ON gs.user_id = u.user_id
            WHERE gs.win_status = 1 
                AND gs.puzzle_size = ?
                AND gs.game_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY gs.time_taken_seconds ASC
            LIMIT ?
        ");
        $stmt->execute([$puzzleSize, $limit]);
        $leaderboards['recent'] = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'leaderboards' => $leaderboards,
        'puzzle_size' => $puzzleSize,
        'type' => $type
    ]);
}

function getUserRankings($pdo, $input) {
    $userId = $input['user_id'] ?? $_SESSION['user_id'];
    $puzzleSize = $input['puzzle_size'] ?? '4x4';
    
    // Get user's personal stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_games,
            SUM(win_status) as total_wins,
            AVG(time_taken_seconds) as avg_time,
            AVG(moves_count) as avg_moves,
            MIN(CASE WHEN win_status = 1 THEN time_taken_seconds END) as best_time,
            MIN(CASE WHEN win_status = 1 THEN moves_count END) as best_moves,
            MAX(game_date) as last_played
        FROM game_stats 
        WHERE user_id = ? AND puzzle_size = ?
    ");
    $stmt->execute([$userId, $puzzleSize]);
    $userStats = $stmt->fetch();
    
    // Get user's global rankings
    $rankings = [];
    
    // Time ranking
    if ($userStats['best_time']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as time_rank
            FROM (
                SELECT MIN(time_taken_seconds) as best_time
                FROM game_stats
                WHERE win_status = 1 AND puzzle_size = ?
                GROUP BY user_id
                HAVING best_time < ?
            ) as better_times
        ");
        $stmt->execute([$puzzleSize, $userStats['best_time']]);
        $rankings['time_rank'] = $stmt->fetch()['time_rank'];
    }
    
    // Moves ranking
    if ($userStats['best_moves']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as moves_rank
            FROM (
                SELECT MIN(moves_count) as best_moves
                FROM game_stats
                WHERE win_status = 1 AND puzzle_size = ?
                GROUP BY user_id
                HAVING best_moves < ?
            ) as better_moves
        ");
        $stmt->execute([$puzzleSize, $userStats['best_moves']]);
        $rankings['moves_rank'] = $stmt->fetch()['moves_rank'];
    }
    
    // Wins ranking
    $stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 as wins_rank
        FROM (
            SELECT COUNT(*) as total_wins
            FROM game_stats
            WHERE win_status = 1 AND puzzle_size = ?
            GROUP BY user_id
            HAVING total_wins > ?
        ) as more_wins
    ");
    $stmt->execute([$puzzleSize, $userStats['total_wins']]);
    $rankings['wins_rank'] = $stmt->fetch()['wins_rank'];
    
    // Get user's recent improvement (last 5 games vs previous 5)
    $stmt = $pdo->prepare("
        (SELECT AVG(time_taken_seconds) as recent_avg_time, AVG(moves_count) as recent_avg_moves
         FROM (
             SELECT time_taken_seconds, moves_count
             FROM game_stats 
             WHERE user_id = ? AND puzzle_size = ? AND win_status = 1
             ORDER BY game_date DESC 
             LIMIT 5
         ) as recent)
        UNION ALL
        (SELECT AVG(time_taken_seconds) as prev_avg_time, AVG(moves_count) as prev_avg_moves
         FROM (
             SELECT time_taken_seconds, moves_count
             FROM game_stats 
             WHERE user_id = ? AND puzzle_size = ? AND win_status = 1
             ORDER BY game_date DESC 
             LIMIT 5 OFFSET 5
         ) as previous)
    ");
    $stmt->execute([$userId, $puzzleSize, $userId, $puzzleSize]);
    $improvement = $stmt->fetchAll();
    
    // Get friends' scores (if friends system exists)
    // For now, we'll show top 5 players for comparison
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            MIN(gs.time_taken_seconds) as best_time,
            MIN(gs.moves_count) as best_moves,
            COUNT(*) as total_wins
        FROM game_stats gs
        JOIN users u ON gs.user_id = u.user_id
        WHERE gs.win_status = 1 AND gs.puzzle_size = ?
        GROUP BY gs.user_id, u.username
        ORDER BY best_time ASC
        LIMIT 5
    ");
    $stmt->execute([$puzzleSize]);
    $topPlayers = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'user_stats' => $userStats,
        'rankings' => $rankings,
        'improvement' => $improvement,
        'top_players' => $topPlayers,
        'puzzle_size' => $puzzleSize
    ]);
}

function getPuzzleSizes($pdo) {
    // Get all unique puzzle sizes from the database
    $stmt = $pdo->query("
        SELECT DISTINCT puzzle_size, COUNT(*) as game_count
        FROM game_stats 
        GROUP BY puzzle_size 
        ORDER BY puzzle_size
    ");
    
    $sizes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'puzzle_sizes' => $sizes
    ]);
}
?>
