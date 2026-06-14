<?php
require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Government Services Portal - Oman</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">
                Gov<span>Services</span>
                <small>Sultanate of Oman</small>
            </div>
            <div class="nav">
                <a href="index.php">Home</a>
                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user'): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="apply.php">Apply</a>
                    <a href="logout.php">Logout</a>
                <?php elseif(isset($_SESSION['admin_id']) && $_SESSION['user_type'] == 'admin'): ?>
                    <a href="admin_dashboard.php">Admin Dashboard</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Hero Section -->
        <div class="hero">
            <h1>🇴🇲 Secure & Facilitated<br>Government Services</h1>
            <p>One platform for all your government transactions in the Sultanate of Oman</p>
            <?php if(!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])): ?>
                <div class="mt-20">
                    <a href="register.php" class="btn">Get Started →</a>
                </div>
            <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user'): ?>
                <div class="mt-20">
                    <a href="dashboard.php" class="btn">Go to Dashboard →</a>
                </div>
            <?php elseif(isset($_SESSION['admin_id']) && $_SESSION['user_type'] == 'admin'): ?>
                <div class="mt-20">
                    <a href="admin_dashboard.php" class="btn">Go to Admin Panel →</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Problem & Solution -->
        <div class="grid-2">
            <div class="card">
                <h2>📋 The Current Problem</h2>
                <p>Citizens visiting Sanad Offices face multiple challenges:</p>
                <ul style="margin-top: 15px; margin-left: 20px; line-height: 1.8;">
                    <li>❌ Repeated data entry for each transaction</li>
                    <li>❌ Long waiting times (2-3 hours)</li>
                    <li>❌ Risk of personal data leakage</li>
                    <li>❌ Manual data entry errors</li>
                    <li>❌ Multiple physical office visits required</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>✅ Our Solution</h2>
                <p>This secure web-based platform provides:</p>
                <ul style="margin-top: 15px; margin-left: 20px; line-height: 1.8;">
                    <li>✓ Enter personal data once, use for all services</li>
                    <li>✓ Complete transactions in 10-15 minutes</li>
                    <li>✓ Enhanced data privacy & encryption</li>
                    <li>✓ No repeated office visits needed</li>
                    <li>✓ Track application status online</li>
                </ul>
            </div>
        </div>
        
        <!-- Available Services -->
        <div class="card">
            <h2>📜 Available Government Services</h2>
            <div class="grid-3">
                <div class="feature-box">
                    <div class="icon">🆔</div>
                    <h3>Civil Services</h3>
                    <p>ID Card Renewal<br>Civil Certificate<br>Birth Certificate</p>
                </div>
                <div class="feature-box">
                    <div class="icon">🛂</div>
                    <h3>Travel Documents</h3>
                    <p>Passport Application<br>Passport Renewal<br>Visa Services</p>
                </div>
                <div class="feature-box">
                    <div class="icon">🚗</div>
                    <h3>Vehicle Services</h3>
                    <p>Driving License<br>Vehicle Registration<br>Traffic Fines</p>
                </div>
                <div class="feature-box">
                    <div class="icon">🏢</div>
                    <h3>Business Services</h3>
                    <p>Commercial License<br>Business Registration<br>Tax Services</p>
                </div>
                <div class="feature-box">
                    <div class="icon">🏠</div>
                    <h3>Property Services</h3>
                    <p>Property Registration<br>Title Deeds<br>Municipal Services</p>
                </div>
                <div class="feature-box">
                    <div class="icon">💼</div>
                    <h3>Employment Services</h3>
                    <p>Work Permits<br>Labor Cards<br>Social Insurance</p>
                </div>
            </div>
        </div>
        
        <!-- How It Works -->
        <div class="grid-2">
            <div class="card">
                <h2>🔒 Security Features</h2>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>✓ Password hashing (bcrypt)</li>
                    <li>✓ SQL injection prevention</li>
                    <li>✓ Session-based authentication</li>
                    <li>✓ Encrypted data storage</li>
                    <li>✓ Secure data transmission</li>
                </ul>
            </div>
            
            <div class="card">
                <h2>💡 Key Benefits</h2>
                <ul style="margin-left: 20px; line-height: 1.8;">
                    <li>✓ 90% reduction in transaction time</li>
                    <li>✓ Zero repeated data entry</li>
                    <li>✓ 24/7 access from anywhere</li>
                    <li>✓ Real-time status updates</li>
                    <li>✓ Reduced carbon footprint</li>
                </ul>
            </div>
        </div>
        
        <!-- Call to Action -->
        <div class="card text-center">
            <h2>Ready to experience hassle-free government services?</h2>
            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'user'): ?>
                <a href="dashboard.php" class="btn mt-20">Go to Dashboard →</a>
            <?php elseif(isset($_SESSION['admin_id']) && $_SESSION['user_type'] == 'admin'): ?>
                <a href="admin_dashboard.php" class="btn mt-20">Go to Admin Panel →</a>
            <?php else: ?>
                <div class="mt-20">
                    <a href="register.php" class="btn">Create Free Account</a>
                    <a href="login.php" class="btn" style="margin-left: 10px;">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <p>© <?php echo date('Y'); ?> Government Services Portal - Sultanate of Oman</p>
        <p style="font-size: 12px; margin-top: 10px;">Secure • Reliable • Efficient</p>
    </div>
</body>
</html>