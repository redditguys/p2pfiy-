<?php
require_once 'config.php';

if (!isLoggedIn() || !isWorker()) {
    showAlert('Access denied. Worker account required.', 'danger');
    redirectTo('login.php');
}

$page_title = 'Wallet';
$user = getCurrentUser();

// Get wallet transactions
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT t.*, ta.title as task_title, c.username as client_name
    FROM transactions t
    JOIN tasks ta ON t.task_id = ta.id
    JOIN users c ON t.client_id = c.id
    WHERE t.worker_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll();

// Get payout history
$stmt = $pdo->prepare("
    SELECT * FROM payouts 
    WHERE worker_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$payouts = $stmt->fetchAll();

// Calculate stats
$total_earned = array_sum(array_column($transactions, 'worker_payout'));
$total_withdrawn = array_sum(array_column(array_filter($payouts, function($p) { 
    return $p['status'] === 'completed'; 
}), 'amount'));

include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <!-- Wallet Overview -->
        <div class="col-lg-4">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-wallet fa-3x mb-3"></i>
                    <h2 class="fw-bold"><?php echo formatPrice($user['wallet_balance']); ?></h2>
                    <p class="mb-0">Available Balance</p>
                    
                    <?php if ($user['wallet_balance'] >= MIN_WITHDRAWAL): ?>
                    <button class="btn btn-light mt-3" data-bs-toggle="modal" data-bs-target="#payoutModal">
                        <i class="fas fa-money-bill-wave me-1"></i>Request Payout
                    </button>
                    <?php else: ?>
                    <div class="alert alert-light mt-3 small">
                        Minimum withdrawal: <?php echo formatPrice(MIN_WITHDRAWAL); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row g-3">
                <div class="col-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="fw-bold text-success"><?php echo formatPrice($total_earned); ?></h5>
                            <small class="text-muted">Total Earned</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <h5 class="fw-bold text-info"><?php echo formatPrice($total_withdrawn); ?></h5>
                            <small class="text-muted">Total Withdrawn</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transaction History -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <h5>No transactions yet</h5>
                        <p class="text-muted">Your earnings will appear here as you complete tasks.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Find Tasks
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Task</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Commission</th>
                                    <th>You Earned</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $txn): ?>
                                <tr>
                                    <td><?php echo formatDate($txn['created_at']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($txn['task_title']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($txn['transaction_id']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($txn['client_name']); ?></td>
                                    <td><?php echo formatPrice($txn['amount']); ?></td>
                                    <td class="text-muted">-<?php echo formatPrice($txn['commission'] + $txn['processing_fee']); ?></td>
                                    <td class="fw-bold text-success">+<?php echo formatPrice($txn['worker_payout']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $txn['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($txn['status']); ?>
                                        </span>
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
    </div>
    
    <!-- Payout History -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Payout History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payouts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h5>No payouts yet</h5>
                        <p class="text-muted">Your payout requests will appear here.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date Requested</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Processed Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payouts as $payout): ?>
                                <tr>
                                    <td><?php echo formatDate($payout['created_at']); ?></td>
                                    <td class="fw-bold"><?php echo formatPrice($payout['amount']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo ucfirst($payout['payment_method']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $payout['status'] === 'completed' ? 'success' : 
                                                ($payout['status'] === 'failed' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($payout['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $payout['processed_at'] ? formatDate($payout['processed_at']) : '-'; ?>
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
    </div>
</div>

<!-- Payout Request Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Request Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="worker-dashboard.php">
                <div class="modal-body">
                    <input type="hidden" name="action" value="request_payout">
                    
                    <div class="alert alert-info">
                        Available balance: <strong><?php echo formatPrice($user['wallet_balance']); ?></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Payout Amount</label>
                        <input type="number" class="form-control" name="amount" 
                               value="<?php echo $user['wallet_balance']; ?>"
                               min="<?php echo MIN_WITHDRAWAL; ?>" 
                               max="<?php echo $user['wallet_balance']; ?>" 
                               step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">Choose payment method...</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="paytm">Paytm</option>
                            <option value="usdt">USDT (Crypto)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Payment Details</label>
                        <textarea class="form-control" name="payment_details" rows="3" required
                                  placeholder="Enter your account details (account number, wallet address, etc.)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>