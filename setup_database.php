<?php
require_once 'config.php';

// Test database connection first
echo "Testing database connection...\n";

try {
    // First, try to connect without specifying a database to create it
    echo "Connecting to MySQL server...\n";
    $mysqli = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    } else {
        echo "✓ Connected to MySQL server successfully!\n";
        echo "MySQL Server Info: " . $mysqli->get_server_info() . "\n";
    }
    
    // Create database if it doesn't exist
    $dbName = DB_DATABASE;
    echo "Creating database '$dbName' if it doesn't exist...\n";
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
    echo "✓ Database '$dbName' ready\n";
    
    // Select the database
    $mysqli->select_db($dbName);
    echo "✓ Selected database '$dbName'\n";
    $mysqli->close();
    
    // Now proceed with PDO for table creation
    echo "Establishing PDO connection...\n";
    $pdo = getDBConnection();
    echo "✓ PDO connection established\n\n";
    
    // Create users table
    $sql_users = "
    CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        role ENUM('player', 'admin') DEFAULT 'player' NOT NULL,
        registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Create background_images table
    $sql_background_images = "
    CREATE TABLE IF NOT EXISTS background_images (
        image_id INT AUTO_INCREMENT PRIMARY KEY,
        image_name VARCHAR(100) NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        uploaded_by_user_id INT,
        upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Create user_preferences table
    $sql_user_preferences = "
    CREATE TABLE IF NOT EXISTS user_preferences (
        preference_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        default_puzzle_size VARCHAR(10) DEFAULT '4x4',
        preferred_background_image_id INT,
        sound_enabled BOOLEAN DEFAULT TRUE,
        animations_enabled BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (preferred_background_image_id) REFERENCES background_images(image_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Create game_stats table
    $sql_game_stats = "
    CREATE TABLE IF NOT EXISTS game_stats (
        stat_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        puzzle_size VARCHAR(10) NOT NULL,
        time_taken_seconds INT NOT NULL,
        moves_count INT NOT NULL,
        background_image_id INT,
        win_status BOOLEAN NOT NULL,
        game_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (background_image_id) REFERENCES background_images(image_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Create announcements table
    $sql_announcements = "
    CREATE TABLE IF NOT EXISTS announcements (
        announcement_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_by_user_id INT,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Execute table creation
    $pdo->exec($sql_users);
    echo "✓ Users table created successfully\n";
    
    $pdo->exec($sql_background_images);
    echo "✓ Background images table created successfully\n";
    
    $pdo->exec($sql_user_preferences);
    echo "✓ User preferences table created successfully\n";
    
    $pdo->exec($sql_game_stats);
    echo "✓ Game stats table created successfully\n";
    
    $pdo->exec($sql_announcements);
    echo "✓ Announcements table created successfully\n";
    
    // Insert default background image
    $default_image = "
    INSERT IGNORE INTO background_images (image_id, image_name, image_url, is_active) 
    VALUES (1, 'Default Background', 'background.jpg', TRUE);
    ";
    $pdo->exec($default_image);
    echo "✓ Default background image added\n";
    
    // Create default admin user (password: admin123)
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_user = "
    INSERT IGNORE INTO users (username, password_hash, email, role) 
    VALUES ('admin', '$admin_password', 'admin@puzzlegame.com', 'admin');
    ";
    $pdo->exec($admin_user);
    echo "✓ Default admin user created (username: admin, password: admin123)\n";
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "Please update the database connection details in config.php\n";
    
} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
?>
