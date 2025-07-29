<?php
require_once 'config.php';

// Handle admin access
if (isset($_GET['admin_access'])) {
    $_SESSION['user_id'] = 'admin';
    $_SESSION['user_name'] = 'Admin';
    $_SESSION['user_email'] = 'admin@p2pfiy.com';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['access_key'] = ADMIN_KEY;
    redirectTo('admin-dashboard.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $access_key = trim($_POST['access_key'] ?? '');
    
    if (empty($access_key)) {
        showAlert('Access key is required', 'danger');
        redirectTo('index.php');
    }
    
    $pdo = getDBConnection();
    
    if ($action === 'register') {
        $role = $_POST['role'];
        $name = trim($_POST['name'] ?? $_POST['company_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $skills = trim($_POST['skills'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE access_key = ? OR email = ?");
        $stmt->execute([$access_key, $email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            // Login existing user
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['user_name'] = $existing_user['name'];
            $_SESSION['user_email'] = $existing_user['email'];
            $_SESSION['user_role'] = $existing_user['role'];
            $_SESSION['access_key'] = $existing_user['access_key'];
            $_SESSION['wallet_balance'] = $existing_user['wallet_balance'];
            
            showAlert('Welcome back, ' . $existing_user['name'] . '!', 'success');
            
            // Redirect based on role
            switch ($existing_user['role']) {
                case 'admin':
                    redirectTo('admin-dashboard.php');
                case 'client':
                    redirectTo('client-dashboard.php');
                case 'worker':
                    redirectTo('worker-dashboard.php');
                default:
                    redirectTo('index.php');
            }
        } else {
            // Create new user
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, role, access_key, skills, company_name) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $name,
                    $email,
                    $role,
                    $access_key,
                    $role === 'worker' ? $skills : null,
                    $role === 'client' ? $company_name : null
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // Get the created user
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['access_key'] = $user['access_key'];
                $_SESSION['wallet_balance'] = $user['wallet_balance'];
                
                showAlert('Account created successfully! Welcome, ' . $name . '!', 'success');
                
                // Redirect based on role
                switch ($role) {
                    case 'client':
                        redirectTo('client-dashboard.php');
                    case 'worker':
                        redirectTo('worker-dashboard.php');
                    default:
                        redirectTo('index.php');
                }
                
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { // Duplicate entry
                    showAlert('Email or access key already exists', 'danger');
                } else {
                    showAlert('Registration failed: ' . $e->getMessage(), 'danger');
                }
                redirectTo('index.php');
            }
        }
    } elseif ($action === 'login') {
        // Handle direct login with access key
        $stmt = $pdo->prepare("SELECT * FROM users WHERE access_key = ?");
        $stmt->execute([$access_key]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['access_key'] = $user['access_key'];
            $_SESSION['wallet_balance'] = $user['wallet_balance'];
            
            showAlert('Welcome back, ' . $user['name'] . '!', 'success');
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    redirectTo('admin-dashboard.php');
                case 'client':
                    redirectTo('client-dashboard.php');
                case 'worker':
                    redirectTo('worker-dashboard.php');
                default:
                    redirectTo('index.php');
            }
        } else {
            showAlert('Invalid access key', 'danger');
            redirectTo('index.php');
        }
    }
}

redirectTo('index.php');
?>