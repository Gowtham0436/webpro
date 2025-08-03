<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request');
    }
    
    $action = $input['action'];
    
    switch ($action) {
        case 'get_active_announcements':
            getActiveAnnouncements($pdo);
            break;
            
        case 'get_active_background_images':
            getActiveBackgroundImages($pdo);
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

function getActiveAnnouncements($pdo) {
    $stmt = $pdo->query("
        SELECT id, title, content, created_date as created_date
        FROM announcements 
        WHERE is_active = 1 
        ORDER BY created_date DESC 
        LIMIT 10
    ");
    
    $announcements = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);
}

function getActiveBackgroundImages($pdo) {
    $stmt = $pdo->query("
        SELECT image_id, image_name, image_url
        FROM background_images 
        WHERE is_active = 1 
        ORDER BY image_name
    ");
    
    $images = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'images' => $images
    ]);
}
?>
