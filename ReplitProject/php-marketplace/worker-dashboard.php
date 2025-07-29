<?php
require_once 'config.php';

if (!isLoggedIn() || !isWorker()) {
    showAlert('Access denied. Worker account required.', 'danger');
    redirectTo('login.php');
}

$page_title = 'Worker Dashboard';
$user = getCurrentUser();

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'request_payout') {
    $amount = (float)$_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $payment_details = $_POST['payment_details'];
    
    if ($amount >= MIN_WITHDRAWAL && $amount <= $user['wallet_balance']) {
        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payouts (worker_id, amount, payment_method, payment_details)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $amount, $payment_method, $payment_details]);
            
            // Update wallet balance
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user['id']]);
            
            showAlert('Payout request submitted successfully!', 'success');
            redirectTo('worker-dashboard.php');
        } catch (PDOException $e) {
            showAlert('Failed to submit payout request: ' . $e->getMessage(), 'danger');
        }
    } else {
        showAlert('Invalid payout amount. Check minimum withdrawal and available balance.', 'warning');
    }
}

// Get worker's data
$pdo = getDBConnection();

// Get submissions
$stmt = $pdo->prepare("
    SELECT s.*, t.title, t.price, t.category, c.username as client_name
    FROM submissions s
    JOIN tasks t ON s.task_id = t.id
    JOIN users c ON t.client_id = c.id
    WHERE s.worker_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$user['id']]);
$my_submissions = $stmt->fetchAll();

// Get transactions
$stmt = $pdo->prepare("
    SELECT t.*, ta.title as task_title, c.username as client_name
    FROM transactions t
    JOIN tasks ta ON t.task_id = ta.id
    JOIN users c ON t.client_id = c.id
    WHERE t.worker_id = ?
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recent_earnings = $stmt->fetchAll();

// Get payout history
$stmt = $pdo->prepare("
    SELECT * FROM payouts 
    WHERE worker_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user['id']]);
$payout_history = $stmt->fetchAll();

// Calculate statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_submissions,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_submissions,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_submissions,
        COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.amount END), 0) as total_earned
    FROM submissions s
    LEFT JOIN transactions t ON s.task_id = t.task_id AND t.worker_id = s.worker_id
    WHERE s.worker_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

include 'includes/header.php';
?>

<div class="container my-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-success text-white rounded p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
                        <p class="mb-0">Track your earnings and manage your work submissions.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="bg-light text-dark rounded p-3">
                            <h4 class="fw-bold mb-0"><?php echo formatPrice($user['wallet_balance']); ?></h4>
                            <small>Available Balance</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clipboard-list fa-2x text-primary mb-3"></i>
                    <h3 class="fw-bold"><?php echo $stats['total_submissions']; ?></h3>
                    <p class="text-muted mb-0">Total Submissions</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                    <h3 class="fw-bold"><?php echo $stats['approved_submissions']; ?></h3>
                    <p class="text-muted mb-0">Approved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                    <h3 class="fw-bold"><?php echo $stats['pending_submissions']; ?></h3>
                    <p class="text-muted mb-0">Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-dollar-sign fa-2x text-info mb-3"></i>
                    <h3 class="fw-bold"><?php echo formatPrice($stats['total_earned']); ?></h3>
                    <p class="text-muted mb-0">Total Earned</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <!-- My Submissions -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">My Submissions</h5>
                    <a href="index.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-search me-1"></i>Find More Tasks
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($my_submissions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5>No submissions yet</h5>
                        <p class="text-muted">Start applying to tasks to build your portfolio and earn money.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Browse Available Tasks
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Client</th>
                                    <th>Price</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_submissions as $submission): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($submission['title']); ?></strong>
                                        <br><span class="badge bg-secondary small"><?php echo ucfirst($submission['category']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($submission['client_name']); ?></td>
                                    <td class="fw-bold text-success"><?php echo formatPrice($submission['price']); ?></td>
                                    <td><?php echo formatDate($submission['submitted_at']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $submission['status'] === 'approved' ? 'success' : 
                                                ($submission['status'] === 'rejected' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($submission['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewSubmission('<?php echo $submission['id']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Wallet Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-wallet me-2"></i>Wallet
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h3 class="fw-bold text-success"><?php echo formatPrice($user['wallet_balance']); ?></h3>
                        <p class="text-muted mb-0">Available Balance</p>
                    </div>
                    
                    <?php if ($user['wallet_balance'] >= MIN_WITHDRAWAL): ?>
                    <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#payoutModal">
                        <i class="fas fa-money-bill-wave me-2"></i>Request Payout
                    </button>
                    <?php else: ?>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Minimum withdrawal: <?php echo formatPrice(MIN_WITHDRAWAL); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Earnings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Recent Earnings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_earnings)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-coins fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No earnings yet</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_earnings as $earning): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 small"><?php echo htmlspecialchars($earning['task_title']); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        From: <?php echo htmlspecialchars($earning['client_name']); ?>
                                    </p>
                                    <small class="text-muted"><?php echo formatDate($earning['created_at']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-success small">+<?php echo formatPrice($earning['worker_payout']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payout History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Payout History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payout_history)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-history fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No payouts yet</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($payout_history as $payout): ?>
                        <div class="list-group-item px-0 py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold"><?php echo formatPrice($payout['amount']); ?></span>
                                    <br><small class="text-muted"><?php echo formatDate($payout['created_at']); ?></small>
                                </div>
                                <span class="badge bg-<?php 
                                    echo $payout['status'] === 'completed' ? 'success' : 
                                        ($payout['status'] === 'failed' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($payout['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payout Request Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-money-bill-wave me-2"></i>Request Payout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="request_payout">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i>
                        Available balance: <strong><?php echo formatPrice($user['wallet_balance']); ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label for="amount" class="form-label fw-medium">Payout Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               min="<?php echo MIN_WITHDRAWAL; ?>" 
                               max="<?php echo $user['wallet_balance']; ?>" 
                               step="0.01" required>
                        <div class="form-text">Minimum: <?php echo formatPrice(MIN_WITHDRAWAL); ?></div>
                        <div class="invalid-feedback">Please enter a valid amount.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_method" class="form-label fw-medium">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Choose payment method...</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="paytm">Paytm</option>
                            <option value="usdt">USDT (Crypto)</option>
                        </select>
                        <div class="invalid-feedback">Please select a payment method.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_details" class="form-label fw-medium">Payment Details</label>
                        <textarea class="form-control" id="payment_details" name="payment_details" rows="3" 
                                  placeholder="Enter your account details (account number, wallet address, etc.)" required></textarea>
                        <div class="invalid-feedback">Please provide payment details.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewSubmission(submissionId) {
    // Implementation for viewing submission details
    alert('Viewing submission: ' + submissionId);
}

// Auto-fill payout amount with full balance
document.addEventListener('DOMContentLoaded', function() {
    const payoutModal = document.getElementById('payoutModal');
    const amountInput = document.getElementById('amount');
    
    if (payoutModal && amountInput) {
        payoutModal.addEventListener('shown.bs.modal', function() {
            amountInput.value = <?php echo $user['wallet_balance']; ?>;
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>