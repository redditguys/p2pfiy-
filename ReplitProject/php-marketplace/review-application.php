<?php
require_once 'config.php';

if (!isLoggedIn() || !isClient()) {
    showAlert('Access denied. Client account required.', 'danger');
    redirectTo('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = $_POST['application_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (!empty($application_id) && in_array($status, ['approved', 'rejected'])) {
        $pdo = getDBConnection();
        
        try {
            // Get application details
            $stmt = $pdo->prepare("
                SELECT s.*, t.title, t.price, t.client_id
                FROM submissions s
                JOIN tasks t ON s.task_id = t.id
                WHERE s.id = ? AND t.client_id = ?
            ");
            $stmt->execute([$application_id, $_SESSION['user_id']]);
            $application = $stmt->fetch();
            
            if ($application) {
                // Update application status
                $stmt = $pdo->prepare("
                    UPDATE submissions 
                    SET status = ?, reviewed_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$status, $application_id]);
                
                if ($status === 'approved') {
                    // Create transaction record
                    $commission = calculateCommission($application['price']);
                    $worker_payout = calculateWorkerPayout($application['price']);
                    $transaction_id = generateTransactionId();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO transactions 
                        (transaction_id, client_id, worker_id, task_id, amount, commission, 
                         commission_rate, processing_fee, worker_payout, status, description)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
                    ");
                    $stmt->execute([
                        $transaction_id,
                        $application['client_id'],
                        $application['worker_id'],
                        $application['task_id'],
                        $application['price'],
                        $commission,
                        COMMISSION_RATE,
                        PROCESSING_FEE,
                        $worker_payout,
                        'Payment for task: ' . $application['title']
                    ]);
                    
                    // Add money to worker's wallet
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET wallet_balance = wallet_balance + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$worker_payout, $application['worker_id']]);
                    
                    showAlert('Application approved and payment processed!', 'success');
                } else {
                    showAlert('Application rejected.', 'info');
                }
                
                redirectTo('task-details.php?id=' . $application['task_id']);
            } else {
                showAlert('Application not found or access denied.', 'danger');
                redirectTo('client-dashboard.php');
            }
        } catch (PDOException $e) {
            showAlert('Failed to process application: ' . $e->getMessage(), 'danger');
            redirectTo('client-dashboard.php');
        }
    } else {
        showAlert('Invalid request.', 'danger');
        redirectTo('client-dashboard.php');
    }
} else {
    redirectTo('client-dashboard.php');
}
?>