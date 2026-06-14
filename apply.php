<?php
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user info for display
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get all available services
$services = $pdo->query("SELECT * FROM service_types ORDER BY service_name")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_type = $_POST['service_type'] ?? '';
    $request_date = $_POST['request_date'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($service_type)) {
        $error = "Please select a service";
    } else {
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, service_type, status, request_date, notes) VALUES (?, ?, 'pending', ?, ?)");
        
        if ($stmt->execute([$user_id, $service_type, $request_date, $notes])) {
            $success = "Your application has been submitted successfully! You can track its status in your dashboard.";
        } else {
            $error = "Submission failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Service | Government Services Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">Gov<span>Services</span><small>Sultanate of Oman</small></div>
            <div class="nav">
                <a href="index.php">Home</a>
                <a href="dashboard.php">Dashboard</a>
                <a href="apply.php">Apply</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="grid-2">
            <!-- Application Form -->
            <div class="card">
                <h2>📝 Apply for Government Service</h2>
                
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Select Service *</label>
                        <select name="service_type" required>
                            <option value="">-- Select a service --</option>
                            <?php foreach($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['service_name']); ?>">
                                    <?php echo htmlspecialchars($service['service_name']); ?> 
                                    (<?php echo $service['estimated_time']; ?> min - <?php echo $service['fee']; ?> OMR)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Preferred Date</label>
                        <input type="date" name="request_date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Additional Notes (Optional)</label>
                        <textarea name="notes" rows="4" placeholder="Any special requirements, preferred time, or additional information..."></textarea>
                    </div>
                    
                    <button type="submit">Submit Application</button>
                </form>
            </div>
            
            <!-- Information Panel -->
            <div>
                <div class="card">
                    <h2>👤 Your Information</h2>
                    <div style="background: #f0f7ff; padding: 15px; border-radius: 8px;">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><strong>Civil ID:</strong> <?php echo $user['civil_id']; ?></p>
                        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                        <p><strong>Phone:</strong> <?php echo $user['phone']; ?></p>
                    </div>
                    <p class="mt-20" style="font-size: 14px; color: #666;">
                        ✅ Your information is automatically included with every application.<br>
                        No need to re-enter for each service!
                    </p>
                </div>
                
                <div class="card" style="background: #e8f5e9;">
                    <h3>💡 How It Works</h3>
                    <ol style="margin-left: 20px; line-height: 1.8;">
                        <li>Select the service you need</li>
                        <li>Choose your preferred date</li>
                        <li>Add any special notes</li>
                        <li>Submit your application</li>
                        <li>Track status in your dashboard</li>
                    </ol>
                    <p class="mt-20"><strong>🎯 Benefit:</strong> You entered your personal data once during registration. Now it's automatically used for ALL applications!</p>
                </div>
            </div>
        </div>
        
        <!-- Available Services List -->
        <div class="card">
            <h2>📋 All Available Services</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Estimated Time</th>
                            <th>Fee (OMR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($services as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <td><?php echo $service['estimated_time']; ?> minutes</td>
                                <td><?php echo $service['fee']; ?> OMR</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>