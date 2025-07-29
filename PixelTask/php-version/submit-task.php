<?php
require_once 'config.php';

if (!isLoggedIn() || !isWorker()) {
    showAlert('Access denied', 'danger');
    redirectTo('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_id = $_POST['task_id'] ?? '';
    $proof_text = trim($_POST['proof_text'] ?? '');
    $proof_file_url = trim($_POST['proof_file_url'] ?? '');
    
    if (empty($task_id) || empty($proof_text)) {
        showAlert('Task ID and proof text are required', 'danger');
        redirectTo('index.php');
    }
    
    $pdo = getDBConnection();
    
    // Check if task exists and has available spots
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND status = 'active' AND spots_available > 0");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        showAlert('Task not found or no spots available', 'danger');
        redirectTo('index.php');
    }
    
    // Check if user already submitted for this task
    $stmt = $pdo->prepare("SELECT id FROM submissions WHERE task_id = ? AND worker_id = ?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        showAlert('You have already submitted proof for this task', 'warning');
        redirectTo('worker-dashboard.php');
    }
    
    try {
        // Insert submission
        $stmt = $pdo->prepare("
            INSERT INTO submissions (task_id, worker_id, proof_text, proof_file_url) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $task_id,
            $_SESSION['user_id'],
            $proof_text,
            $proof_file_url ?: null
        ]);
        
        // Update task spots
        $stmt = $pdo->prepare("UPDATE tasks SET spots_available = spots_available - 1 WHERE id = ?");
        $stmt->execute([$task_id]);
        
        showAlert('Proof submitted successfully! Waiting for admin review.', 'success');
        redirectTo('worker-dashboard.php');
        
    } catch (PDOException $e) {
        showAlert('Failed to submit proof: ' . $e->getMessage(), 'danger');
        redirectTo('index.php');
    }
} else {
    redirectTo('index.php');
}
?>