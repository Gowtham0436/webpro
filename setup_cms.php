<?php
require_once 'config.php';

echo "Adding Announcements Table for CMS...\n";
echo "====================================\n\n";

try {
    $pdo = getDBConnection();
    
    // Create announcements table
    $sql_announcements = "
    CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        priority ENUM('normal', 'high', 'urgent') DEFAULT 'normal',
        is_active BOOLEAN DEFAULT TRUE,
        created_by_user_id INT NOT NULL,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_active_priority (is_active, priority),
        INDEX idx_created_date (created_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_announcements);
    echo "✓ Announcements table created successfully\n";
    
    // Add a sample announcement
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO announcements (title, content, priority, created_by_user_id) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'Welcome to Fifteen Puzzle!',
        'Welcome to our enhanced Fifteen Puzzle game with user accounts and statistics tracking. Try to solve the puzzle in the shortest time with the fewest moves!',
        'high',
        1 // Admin user
    ]);
    
    echo "✓ Sample announcement added\n";
    
    // Also add user status column for better user management
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
        echo "✓ Added is_active column to users table\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ is_active column already exists in users table\n";
        } else {
            echo "✗ Error adding is_active column: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 CMS tables setup completed successfully!\n\n";
    
    echo "New Admin Features Available:\n";
    echo "- User Management (activate/deactivate users)\n";
    echo "- Background Image Management (add/remove/toggle)\n";
    echo "- Game Statistics Monitoring\n";
    echo "- Announcements System\n";
    echo "- System Overview Dashboard\n\n";
    
    echo "Access admin dashboard at:\n";
    echo "http://codd.cs.gsu.edu/~sjuyal1/Puzzle_Game/admin.html\n";
    echo "Login with: username=admin, password=admin123\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
