<?php
require_once 'db.php';

// Only allow this script to run from localhost for security
$allowed_hosts = ['localhost', '127.0.0.1'];
if (!in_array($_SERVER['HTTP_HOST'], $allowed_hosts) && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    die("Access denied. This script can only be run locally.");
}

// Check if admin table needs to be created
$pdo->exec("
    CREATE TABLE IF NOT EXISTS admins (
        admin_id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        full_name TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

// Add is_admin column to users table if not exists (for future use)
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_admin INTEGER DEFAULT 0");
} catch(PDOException $e) {
    // Column might already exist
}

// Check if admin already exists
$stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
$result = $stmt->fetch();

if ($result['count'] == 0) {
    $admin_password = password_hash("Admin@123", PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO admins (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)");
    $insert->execute(['admin', $admin_password, 'admin@sanad.gov.om', 'System Administrator']);
    
    echo "<h2>✅ Admin Account Created Successfully!</h2>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> Admin@123</p>";
    echo "<p><strong>Email:</strong> admin@sanad.gov.om</p>";
    echo "<p><a href='admin_login.php'>Click here to login as Admin</a></p>";
} else {
    echo "<h2>⚠️ Admin Account Already Exists</h2>";
    echo "<p><a href='admin_login.php'>Go to Admin Login</a></p>";
}
?>