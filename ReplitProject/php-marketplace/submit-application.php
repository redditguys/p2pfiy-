<?php
require_once 'config.php';

if (!isLoggedIn() || !isWorker()) {
    showAlert('Access denied. Worker account required.', 'danger');
    redirectTo('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? '';
    $proposal = trim($_POST['proposal'] ?? '');
    $portfolio_url = trim($_POST['portfolio_url'] ?? '');
    
    if (!empty($task_id) && !empty($proposal)) {
        $pdo = getDBConnection();
        
        try {
            // Check if already applied
            $stmt = $pdo->prepare("SELECT id FROM submissions WHERE task_id = ? AND worker_id = ?");
            $stmt->execute([$task_id, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                showAlert('You have already applied to this task.', 'warning');
            } else {
                // Submit application
                $stmt = $pdo->prepare("
                    INSERT INTO submissions (task_id, worker_id, proof_text, proof_file_url)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$task_id, $_SESSION['user_id'], $proposal, $portfolio_url]);
                
                showAlert('Application submitted successfully!', 'success');
            }
        } catch (PDOException $e) {
            showAlert('Failed to submit application: ' . $e->getMessage(), 'danger');
        }
    } else {
        showAlert('Please fill in all required fields.', 'warning');
    }
}

redirectTo('index.php');
?>