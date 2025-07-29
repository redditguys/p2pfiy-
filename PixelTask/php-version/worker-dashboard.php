<?php
require_once 'config.php';

if (!isLoggedIn() || !isWorker()) {
    showAlert('Access denied', 'danger');
    redirectTo('index.php');
}

$page_title = 'Worker Dashboard';

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_withdrawal') {
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_details = trim($_POST['payment_details'] ?? '');
    
    if ($amount < MIN_WITHDRAWAL || $amount > $_SESSION['wallet_balance']) {
        showAlert('Invalid withdrawal amount. Minimum is $' . MIN_WITHDRAWAL, 'danger');
    } elseif (empty($payment_method) || empty($payment_details)) {
        showAlert('Please select a payment method and provide payment details', 'danger');
    } else {
        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO withdrawals (user_id, amount, payment_method, payment_details) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $amount,
                $payment_method,
                $payment_details
            ]);
            
            // Deduct from wallet balance
            $stmt = $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $_SESSION['user_id']]);
            $_SESSION['wallet_balance'] -= $amount;
            
            // Create transaction record
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, amount, type, description) 
                VALUES (?, ?, 'withdrawal', 'Withdrawal request')
            ");
            $stmt->execute([$_SESSION['user_id'], -$amount]);
            
            showAlert('Withdrawal request submitted successfully!', 'success');
        } catch (PDOException $e) {
            showAlert('Failed to submit withdrawal request: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get worker data
$pdo = getDBConnection();

// Update session wallet balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user) {
    $_SESSION['wallet_balance'] = $user['wallet_balance'];
}

// Get submissions
$stmt = $pdo->prepare("
    SELECT s.*, t.title as task_title, t.price, t.category
    FROM submissions s 
    JOIN tasks t ON s.task_id = t.id 
    WHERE s.worker_id = ? 
    ORDER BY s.submitted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$submissions = $stmt->fetchAll();

// Get withdrawals
$stmt = $pdo->prepare("
    SELECT * FROM withdrawals 
    WHERE user_id = ? 
    ORDER BY requested_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$withdrawals = $stmt->fetchAll();

// Get transactions
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();

// Calculate stats
$total_earnings = array_sum(array_column(array_filter($submissions, fn($s) => $s['status'] === 'approved'), 'price'));
$pending_submissions = count(array_filter($submissions, fn($s) => $s['status'] === 'pending'));
$approved_submissions = count(array_filter($submissions, fn($s) => $s['status'] === 'approved'));

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Worker Panel</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#submissions">
                            <i class="fas fa-clipboard-check me-2"></i>My Submissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#withdrawals">
                            <i class="fas fa-money-bill-wave me-2"></i>Withdrawals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#transactions">
                            <i class="fas fa-history me-2"></i>Transactions
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 pixel-font text-primary">
                    <i class="fas fa-user-hard-hat me-2"></i>Worker Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-success me-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fas fa-search me-1"></i>Browse Tasks
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>

            <?php displayAlert(); ?>

            <!-- Wallet & Stats -->
            <div class="row g-4 mb-5" id="dashboard">
                <div class="col-xl-3 col-md-6">
                    <div class="wallet-balance">
                        <div class="mb-2">Wallet Balance</div>
                        <div><?php echo formatPrice($_SESSION['wallet_balance']); ?></div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $total_earnings; ?></div>
                        <div class="text-muted">Total Earnings</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $approved_submissions; ?></div>
                        <div class="text-muted">Completed Tasks</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $pending_submissions; ?></div>
                        <div class="text-muted">Pending Review</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="minecraft-card p-4 mb-5">
                <h3 class="pixel-font text-primary mb-4">Quick Actions</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="index.php" class="btn minecraft-btn w-100">
                            <i class="fas fa-search me-2"></i>Browse New Tasks
                        </a>
                    </div>
                    <div class="col-md-4">
                        <button class="btn minecraft-btn w-100" data-bs-toggle="modal" data-bs-target="#withdrawalModal" 
                                <?php echo $_SESSION['wallet_balance'] < MIN_WITHDRAWAL ? 'disabled' : ''; ?>>
                            <i class="fas fa-money-bill-wave me-2"></i>Request Withdrawal
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="#submissions" class="btn btn-outline-primary w-100">
                            <i class="fas fa-clipboard-check me-2"></i>View Submissions
                        </a>
                    </div>
                </div>
            </div>

            <!-- My Submissions -->
            <div class="minecraft-card p-4 mb-5" id="submissions">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-clipboard-check me-2"></i>My Submissions (<?php echo count($submissions); ?>)
                </h3>
                
                <?php if (empty($submissions)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No submissions yet</h5>
                        <p class="text-muted">Start browsing and applying to tasks to see your submissions here!</p>
                        <a href="index.php" class="btn minecraft-btn">Browse Tasks</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($submission['task_title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo strtoupper($submission['category']); ?></span>
                                        </td>
                                        <td>
                                            <span class="price-tag"><?php echo formatPrice($submission['price']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $submission['status'] === 'approved' ? 'success' : 
                                                    ($submission['status'] === 'rejected' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo strtoupper($submission['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($submission['submitted_at']); ?></td>
                                        <td>
                                            <?php if ($submission['admin_notes']): ?>
                                                <button class="btn btn-outline-info btn-sm" onclick="showNotes('<?php echo htmlspecialchars($submission['admin_notes']); ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Withdrawals -->
            <div class="minecraft-card p-4 mb-5" id="withdrawals">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-money-bill-wave me-2"></i>Withdrawal History (<?php echo count($withdrawals); ?>)
                </h3>
                
                <?php if (empty($withdrawals)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-wallet fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No withdrawal requests yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Processed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($withdrawals as $withdrawal): ?>
                                    <tr>
                                        <td>
                                            <span class="price-tag"><?php echo formatPrice($withdrawal['amount']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo strtoupper($withdrawal['payment_method']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $withdrawal['status'] === 'completed' ? 'success' : 
                                                    ($withdrawal['status'] === 'rejected' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo strtoupper($withdrawal['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($withdrawal['requested_at']); ?></td>
                                        <td>
                                            <?php echo $withdrawal['processed_at'] ? formatDate($withdrawal['processed_at']) : '-'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Transactions -->
            <div class="minecraft-card p-4 mb-5" id="transactions">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-history me-2"></i>Recent Transactions
                </h3>
                
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No transactions yet</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php echo $transaction['type'] === 'earning' ? 'success' : 'danger'; ?>">
                                                <?php echo strtoupper($transaction['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="price-tag <?php echo $transaction['amount'] < 0 ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo formatPrice(abs($transaction['amount'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                        <td><?php echo formatDate($transaction['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title pixel-font">
                    <i class="fas fa-money-bill-wave me-2"></i>Request Withdrawal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="request_withdrawal">
                    
                    <div class="mb-3">
                        <div class="wallet-balance mb-3">
                            <div>Available Balance: <?php echo formatPrice($_SESSION['wallet_balance']); ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Withdrawal Amount</label>
                        <input type="number" class="form-control" name="amount" step="0.01" 
                               min="<?php echo MIN_WITHDRAWAL; ?>" max="<?php echo $_SESSION['wallet_balance']; ?>" 
                               placeholder="Minimum: $<?php echo MIN_WITHDRAWAL; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">Easypaisa</option>
                            <option value="paytm">Paytm</option>
                            <option value="usdt">USDT (Crypto)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Details</label>
                        <textarea class="form-control" name="payment_details" rows="3" 
                                  placeholder="Enter your payment details (account number, wallet address, etc.)" required></textarea>
                        <div class="form-text">
                            Provide accurate payment details. Incorrect details will delay processing.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn minecraft-btn">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="notes-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showNotes(notes) {
    document.getElementById('notes-content').innerHTML = '<p>' + notes + '</p>';
    new bootstrap.Modal(document.getElementById('notesModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>