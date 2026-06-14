<?php
require_once 'db.php';

$error = '';

// Create admins table if not exists (run once)
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

// Insert default admin if no admin exists
$stmt = $pdo->query("SELECT COUNT(*) as count FROM admins");
$result = $stmt->fetch();
if ($result['count'] == 0) {
    $admin_password = password_hash("Admin@123", PASSWORD_DEFAULT);
    $insert = $pdo->prepare("INSERT INTO admins (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)");
    $insert->execute(['admin', $admin_password, 'admin@sanad.gov.om', 'System Administrator']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');  // Can be Civil ID OR Username
    $password = $_POST['password'] ?? '';
    
    if (empty($login_input) || empty($password)) {
        $error = "Please enter Civil ID/Username and Password";
    } else {
        // FIRST: Check if this is an admin (by username)
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$login_input]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // ADMIN LOGIN SUCCESS
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';
            header("Location: admin_dashboard.php");
            exit();
        }
        
        // SECOND: Check if this is a regular user (by Civil ID)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE civil_id = ?");
        $stmt->execute([$login_input]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // USER LOGIN SUCCESS
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_civil_id'] = $user['civil_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = 'user';
            header("Location: dashboard.php");
            exit();
        }
        
        // If neither admin nor user found
        $error = "Invalid Civil ID/Username or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Government Services Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">Gov<span>Services</span><small>Sultanate of Oman</small></div>
            <div class="nav">
                <a href="index.php">Home</a>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="grid-2">
            <div class="card">
                <h2>🔐 Login to Your Account</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Civil ID (Citizen) OR Username (Admin)</label>
                        <input type="text" name="login_input" required placeholder="Enter Civil ID or Username">
                        <small style="color: #666;">Citizens: Use your Civil ID | Admin: Use "admin"</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                    
                    <button type="submit">Login</button>
                    
                    <p class="text-center mt-20">
                        Don't have an account? <a href="register.php">Register here</a>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>📱 Demo Accounts</h2>
                
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <h3 style="margin-bottom: 10px;">👤 Citizen Demo</h3>
                    <p><strong>Civil ID:</strong> 1234567890</p>
                    <p><strong>Password:</strong> password123</p>
                </div>
                
                <div style="background: #fff3e0; padding: 15px; border-radius: 8px;">
                    <h3 style="margin-bottom: 10px;">👨‍💼 Admin Demo</h3>
                    <p><strong>Username:</strong> admin</p>
                    <p><strong>Password:</strong> Admin@123</p>
                </div>
                
                <div class="alert alert-info mt-20">
                    <strong>💡 Tip:</strong> 
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li>Citizens: Login with Civil ID → Goes to User Dashboard</li>
                        <li>Admin: Login with "admin" → Goes to Admin Dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>