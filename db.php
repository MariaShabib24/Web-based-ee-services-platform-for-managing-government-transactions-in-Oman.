<?php
// DATABASE CONNECTION - SQLite Version
class Database {
    private $db_file = 'database.sqlite';
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = new PDO('sqlite:' . __DIR__ . DIRECTORY_SEPARATOR . $this->db_file);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->createTables();
            $this->migrateTables();
            $this->seedData();
        } catch (PDOException $e) {
            die('Database Connection Failed: ' . $e->getMessage());
        }
    }

    private function createTables() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            user_id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            civil_id TEXT UNIQUE NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT NOT NULL,
            password_hash TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            admin_id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            full_name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
            transaction_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            service_type TEXT NOT NULL,
            status TEXT DEFAULT 'pending',
            request_date DATE NOT NULL,
            notes TEXT,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS service_types (
            service_id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_name TEXT NOT NULL,
            estimated_time INTEGER DEFAULT 10,
            fee REAL DEFAULT 0
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            notification_id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER DEFAULT 0,
            transaction_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE
        )");
    }

    private function migrateTables() {
        $cols = $this->pdo->query("PRAGMA table_info(notifications)")->fetchAll();
        $names = array_column($cols, 'name');
        if (!in_array('user_id', $names)) {
            $this->pdo->exec("ALTER TABLE notifications ADD COLUMN user_id INTEGER DEFAULT 0");
        }
    }

    private function seedData() {
        $check = $this->pdo->query("SELECT COUNT(*) as count FROM service_types")->fetch();
        if ($check['count'] == 0) {
            $services = [
                ['ID Card Renewal', 10, 5],
                ['Passport Application', 15, 20],
                ['Civil Certificate', 8, 3],
                ['Driving License Renewal', 12, 10],
                ['Birth Certificate', 10, 2],
                ['Vehicle Registration', 20, 15],
                ['Commercial License', 25, 50]
            ];
            $insert = $this->pdo->prepare("INSERT INTO service_types (service_name, estimated_time, fee) VALUES (?, ?, ?)");
            foreach ($services as $service) {
                $insert->execute($service);
            }
        }

        $adminCheck = $this->pdo->query("SELECT COUNT(*) as count FROM admins")->fetch();
        if ($adminCheck['count'] == 0) {
            $admin_password = password_hash('Admin@123', PASSWORD_DEFAULT);
            $insert = $this->pdo->prepare("INSERT INTO admins (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)");
            $insert->execute(['admin', $admin_password, 'admin@sanad.gov.om', 'System Administrator']);
        }

        $userCheck = $this->pdo->query("SELECT COUNT(*) as count FROM users")->fetch();
        if ($userCheck['count'] == 0) {
            $demo_password = password_hash('password123', PASSWORD_DEFAULT);
            $insert = $this->pdo->prepare("INSERT INTO users (full_name, civil_id, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            $insert->execute(['Demo User', '1234567890', 'demo@example.com', '99887766', $demo_password]);
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}

$db = new Database();
$pdo = $db->getConnection();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
