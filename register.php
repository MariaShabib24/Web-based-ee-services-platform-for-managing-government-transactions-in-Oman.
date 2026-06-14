<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $civil_id = trim($_POST['civil_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($full_name) || empty($civil_id) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE civil_id = ? OR email = ?");
        $stmt->execute([$civil_id, $email]);
        
        if ($stmt->fetch()) {
            $error = "Civil ID or Email already registered. Please login.";
        } else {
            // Create new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name, civil_id, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$full_name, $civil_id, $email, $phone, $password_hash])) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Government Services Portal</title>
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
        <div class="card" style="max-width: 550px; margin: 0 auto;">
            <h2>📝 Create New Account</h2>
            <p style="margin-bottom: 20px; color: #666;">Enter your information once. Use it for all government services.</p>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?> <a href="login.php">Click here to login</a></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="Enter your full name">
                </div>
                
                <div class="form-group">
                    <label>Civil ID Number *</label>
                    <input type="text" name="civil_id" required placeholder="1234567890">
                    <small style="color: #666;">Your unique national identification number</small>
                </div>
                
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required placeholder="your@email.com">
                </div>
                
                <div class="form-group">
                    <label>Phone Number *</label>
                    <input type="tel" name="phone" required placeholder="99xxxxxx">
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Minimum 6 characters">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit">Register Account</button>
                
                <p class="text-center mt-20">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </form>
        </div>
        
        <div class="card text-center" style="background: #e8f5e9;">
            <h3>🔒 Your Data is Safe With Us</h3>
            <p>All personal information is encrypted and stored securely. Your data is only used for government transactions and never shared without your consent.</p>
        </div>
    </div>
</body>
</html>