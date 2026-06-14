<?php
require_once 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$_GET['mark_read'], $user_id]);
    header("Location: dashboard.php");
    exit();
}

$total = count($applications);
$pending = count(array_filter($applications, fn($a) => $a['status'] == 'pending'));
$completed = count(array_filter($applications, fn($a) => $a['status'] == 'completed'));
$unread = count(array_filter($notifications, fn($n) => $n['is_read'] == 0));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Government Services Portal</title>
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
        <div class="card">
            <h2>👋 Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <p>This is your citizen dashboard. You can apply for services and track your applications here.</p>
        </div>

        <div class="grid-3">
            <div class="stat-card">
                <h3>Total Applications</h3>
                <div class="number"><?php echo $total; ?></div>
            </div>
            <div class="stat-card" style="background:#ffc107;color:#333;">
                <h3>Pending</h3>
                <div class="number"><?php echo $pending; ?></div>
            </div>
            <div class="stat-card" style="background:#28a745;">
                <h3>Completed</h3>
                <div class="number"><?php echo $completed; ?></div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h2>👤 My Information</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Civil ID:</strong> <?php echo htmlspecialchars($user['civil_id']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                <a href="apply.php" class="btn mt-20">Apply for New Service</a>
            </div>

            <div class="card">
                <h2>🔔 Notifications <?php if($unread > 0) echo "($unread new)"; ?></h2>
                <?php if(count($notifications) > 0): ?>
                    <?php foreach($notifications as $notif): ?>
                        <div class="alert <?php echo $notif['is_read'] ? 'alert-info' : 'alert-success'; ?>">
                            <?php echo htmlspecialchars($notif['message']); ?><br>
                            <small><?php echo date('M d, Y H:i', strtotime($notif['created_at'])); ?></small>
                            <?php if(!$notif['is_read']): ?>
                                <br><a href="?mark_read=<?php echo $notif['notification_id']; ?>">Mark as read</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No notifications yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2>📋 My Applications</h2>
            <?php if(count($applications) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Service</th>
                                <th>Request Date</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $app): ?>
                                <tr>
                                    <td>#<?php echo $app['transaction_id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['service_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($app['request_date'])); ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($app['submitted_at'])); ?></td>
                                    <td><span class="status-<?php echo htmlspecialchars($app['status']); ?>"><?php echo ucfirst(htmlspecialchars($app['status'])); ?></span></td>
                                    <td><?php echo htmlspecialchars($app['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">You have not submitted any applications yet.</p>
                <p class="text-center mt-20"><a href="apply.php" class="btn">Submit First Application</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
