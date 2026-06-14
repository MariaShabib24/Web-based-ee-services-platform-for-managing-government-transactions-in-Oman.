<?php
require_once 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin')) {
    header("Location: login.php");
    exit();
}

// Handle status update from AJAX or POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $transaction_id = $_POST['transaction_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    $valid_statuses = ['pending', 'approved', 'completed'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE transaction_id = ?");
        if ($stmt->execute([$new_status, $transaction_id])) {
            // Get transaction details for notification
            $stmt2 = $pdo->prepare("SELECT t.*, u.full_name, u.email, u.user_id FROM transactions t JOIN users u ON t.user_id = u.user_id WHERE t.transaction_id = ?");
            $stmt2->execute([$transaction_id]);
            $transaction = $stmt2->fetch();
            
            // Create notification for this user
            $message = "Your application #{$transaction_id} for '{$transaction['service_type']}' has been {$new_status}.";
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, transaction_id, message) VALUES (?, ?, ?)");
            $notif_stmt->execute([$transaction['user_id'], $transaction_id, $message]);
            
            $response['success'] = true;
            $response['message'] = "Status updated to {$new_status}";
        } else {
            $response['message'] = "Failed to update status";
        }
    } else {
        $response['message'] = "Invalid status";
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Get all applications with user details
$applications = $pdo->query("
    SELECT t.*, u.full_name, u.civil_id, u.email, u.phone 
    FROM transactions t 
    JOIN users u ON t.user_id = u.user_id 
    ORDER BY t.submitted_at DESC
")->fetchAll();

// Get statistics
$total_applications = count($applications);
$pending = count(array_filter($applications, fn($a) => $a['status'] == 'pending'));
$approved = count(array_filter($applications, fn($a) => $a['status'] == 'approved'));
$completed = count(array_filter($applications, fn($a) => $a['status'] == 'completed'));

// Get unread notifications count (using existing table without user_id)
$unread_count = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch()['count'];

// Get recent notifications
$notifications = $pdo->query("
    SELECT n.*, t.service_type, u.full_name as user_name
    FROM notifications n
    JOIN transactions t ON n.transaction_id = t.transaction_id
    LEFT JOIN users u ON n.user_id = u.user_id
    ORDER BY n.created_at DESC LIMIT 10
")->fetchAll();

// Mark notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?")->execute([$_GET['mark_read']]);
    header("Location: admin_dashboard.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $pdo->exec("UPDATE notifications SET is_read = 1");
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Government Services Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notification-bell {
            position: relative;
            cursor: pointer;
        }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
        }
        .notification-panel {
            position: absolute;
            right: 0;
            top: 40px;
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1000;
            display: none;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-panel.show {
            display: block;
        }
        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: #e3f2fd;
            border-left: 3px solid #2196f3;
        }
        .notification-item .message {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .notification-item .time {
            font-size: 11px;
            color: #999;
        }
        .notification-item .mark-read {
            float: right;
            font-size: 11px;
            color: #667eea;
            text-decoration: none;
        }
        .status-select {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 12px;
            cursor: pointer;
        }
        .status-select:focus {
            outline: none;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="logo">Gov<span>Services</span><small>Admin Panel</small></div>
            <div class="nav">
                <a href="index.php">Home</a>
                <a href="admin_dashboard.php">Dashboard</a>
                <div class="notification-bell" onclick="toggleNotifications()">
                    🔔
                    <?php if($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                    <div id="notificationPanel" class="notification-panel">
                        <div style="padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between;">
                            <strong>Notifications</strong>
                            <?php if($unread_count > 0): ?>
                                <a href="?mark_all_read=1" style="font-size: 12px;">Mark all as read</a>
                            <?php endif; ?>
                        </div>
                        <?php if(count($notifications) > 0): ?>
                            <?php foreach($notifications as $notif): ?>
                                <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                                    <a href="?mark_read=<?php echo $notif['notification_id']; ?>" class="mark-read">✓</a>
                                    <div class="message"><?php if(!empty($notif['user_name'])): ?><strong><?php echo htmlspecialchars($notif['user_name']); ?></strong><br><?php endif; ?><?php echo htmlspecialchars($notif['message']); ?></div>
                                    <div class="time"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="notification-item">No notifications yet</div>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>👋 Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h2>
            <p>Manage and process citizen applications from this dashboard.</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid-3">
            <div class="stat-card">
                <h3>Total Applications</h3>
                <div class="number"><?php echo $total_applications; ?></div>
            </div>
            <div class="stat-card" style="background: #ffc107; color: #333;">
                <h3>Pending</h3>
                <div class="number"><?php echo $pending; ?></div>
            </div>
            <div class="stat-card" style="background: #28a745;">
                <h3>Completed</h3>
                <div class="number"><?php echo $completed; ?></div>
            </div>
        </div>
        
        <!-- Applications Table -->
        <div class="card">
            <h2>📋 All Citizen Applications</h2>
            
            <?php if(count($applications) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Citizen Name</th>
                                <th>Civil ID</th>
                                <th>Service</th>
                                <th>Request Date</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($applications as $app): ?>
                                <tr>
                                    <td>#<?php echo $app['transaction_id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                    <td><?php echo $app['civil_id']; ?></td>
                                    <td><?php echo htmlspecialchars($app['service_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($app['request_date'])); ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($app['submitted_at'])); ?></td>
                                    <td>
                                        <span class="status-<?php echo $app['status']; ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select class="status-select" data-id="<?php echo $app['transaction_id']; ?>">
                                            <option value="pending" <?php echo $app['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $app['status'] == 'approved' ? 'selected' : ''; ?>>Approve</option>
                                            <option value="completed" <?php echo $app['status'] == 'completed' ? 'selected' : ''; ?>>Complete</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No applications submitted yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function toggleNotifications() {
            var panel = document.getElementById('notificationPanel');
            panel.classList.toggle('show');
        }
        
        document.addEventListener('click', function(event) {
            var bell = document.querySelector('.notification-bell');
            var panel = document.getElementById('notificationPanel');
            if (bell && !bell.contains(event.target)) {
                panel.classList.remove('show');
            }
        });
        
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                var transactionId = this.dataset.id;
                var newStatus = this.value;
                var selectElement = this;
                
                fetch('admin_dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=update&transaction_id=' + transactionId + '&status=' + newStatus
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var row = selectElement.closest('tr');
                        var statusSpan = row.querySelector('[class^="status-"]');
                        if (statusSpan) {
                            statusSpan.className = 'status-' + newStatus;
                            statusSpan.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                        }
                        showToast(data.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast(data.message, 'error');
                        selectElement.value = selectElement.querySelector('option[selected]')?.value || 'pending';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Update failed', 'error');
                });
            });
        });
        
        function showToast(message, type = 'success') {
            var toast = document.createElement('div');
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : '#dc3545'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 9999;
                animation: fadeInOut 2s ease;
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }
        
        var style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInOut {
                0% { opacity: 0; transform: translateY(20px); }
                15% { opacity: 1; transform: translateY(0); }
                85% { opacity: 1; transform: translateY(0); }
                100% { opacity: 0; transform: translateY(20px); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>