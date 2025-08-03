<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Route handling
switch ($path) {
    case '/api/auth/login':
        if ($method === 'POST') {
            handleLogin();
        }
        break;
    
    case '/api/auth/register':
        if ($method === 'POST') {
            handleRegister();
        }
        break;
    
    case '/api/auth/logout':
        if ($method === 'POST') {
            handleLogout();
        }
        break;
    
    case '/api/game/save':
        if ($method === 'POST') {
            handleSaveGameStats();
        }
        break;
    
    case '/api/game/stats':
        if ($method === 'GET') {
            handleGetUserStats();
        }
        break;
    
    case '/api/backgrounds':
        if ($method === 'GET') {
            handleGetBackgrounds();
        }
        break;
    
    case '/api/user/preferences':
        if ($method === 'GET') {
            handleGetUserPreferences();
        } elseif ($method === 'POST') {
            handleUpdateUserPreferences();
        }
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

// Authentication functions
function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username and password required']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT user_id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$input['username']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($input['password'], $user['password_hash'])) {
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$user['user_id']]);
            
            // Start session
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'id' => $user['user_id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function handleRegister() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['username']) || !isset($input['password']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, password, and email required']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        
        // Check if username or email already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$input['username'], $input['email']]);
        
        if ($checkStmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            return;
        }
        
        // Create new user
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");
        $stmt->execute([$input['username'], $passwordHash, $input['email']]);
        
        $userId = $pdo->lastInsertId();
        
        // Create default preferences
        $prefStmt = $pdo->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
        $prefStmt->execute([$userId]);
        
        echo json_encode(['success' => true, 'message' => 'User registered successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function handleLogout() {
    session_start();
    session_destroy();
    echo json_encode(['success' => true]);
}

// Game functions
function handleSaveGameStats() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO game_stats (user_id, puzzle_size, time_taken_seconds, moves_count, background_image_id, win_status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $input['puzzle_size'] ?? '4x4',
            $input['time_taken_seconds'],
            $input['moves_count'],
            $input['background_image_id'] ?? 1,
            $input['win_status']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function handleGetUserStats() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT puzzle_size, time_taken_seconds, moves_count, win_status, game_date 
            FROM game_stats 
            WHERE user_id = ? 
            ORDER BY game_date DESC 
            LIMIT 20
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $stats = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

// Background and preferences functions
function handleGetBackgrounds() {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT image_id, image_name, image_url FROM background_images WHERE is_active = TRUE");
        $backgrounds = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'backgrounds' => $backgrounds]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function handleGetUserPreferences() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $preferences = $stmt->fetch();
        
        echo json_encode(['success' => true, 'preferences' => $preferences]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}

function handleUpdateUserPreferences() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            UPDATE user_preferences 
            SET default_puzzle_size = ?, preferred_background_image_id = ?, sound_enabled = ?, animations_enabled = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $input['default_puzzle_size'] ?? '4x4',
            $input['preferred_background_image_id'] ?? 1,
            $input['sound_enabled'] ?? true,
            $input['animations_enabled'] ?? true,
            $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
}
?>
