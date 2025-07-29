<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    showAlert('Access denied. Admin credentials required.', 'danger');
    redirectTo('login.php');
}

$page_title = 'Admin Dashboard';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'process_payout':
            $payout_id = $_POST['payout_id'];
            $status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE payouts 
                    SET status = ?, processed_at = NOW(), failure_reason = ?
                    WHERE id = ?
                ");
                $stmt->execute([$status, $notes, $payout_id]);
                showAlert('Payout processed successfully', 'success');
            } catch (PDOException $e) {
                showAlert('Failed to process payout: ' . $e->getMessage(), 'danger');
            }
            break;
            
        case 'resolve_dispute':
            $dispute_id = $_POST['dispute_id'];
            $resolution = $_POST['resolution'];
            $dispute_status = $_POST['dispute_status'];
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE disputes 
                    SET status = ?, resolution = ?, resolved_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$dispute_status, $resolution, $dispute_id]);
                showAlert('Dispute resolved successfully', 'success');
            } catch (PDOException $e) {
                showAlert('Failed to resolve dispute: ' . $e->getMessage(), 'danger');
            }
            break;
            
        case 'update_settings':
            $commission_rate = (float)$_POST['commission_rate'];
            $processing_fee = (float)$_POST['processing_fee'];
            $min_withdrawal = (float)$_POST['min_withdrawal'];
            $payout_schedule = $_POST['payout_schedule'];
            
            try {
                $stmt = $pdo->prepare("
                    UPDATE platform_settings 
                    SET commission_rate = ?, processing_fee = ?, min_withdrawal = ?, 
                        payout_schedule = ?, updated_at = NOW()
                ");
                $stmt->execute([$commission_rate, $processing_fee, $min_withdrawal, $payout_schedule]);
                showAlert('Platform settings updated successfully', 'success');
            } catch (PDOException $e) {
                showAlert('Failed to update settings: ' . $e->getMessage(), 'danger');
            }
            break;
    }
}

// Get dashboard data
$pdo = getDBConnection();
$stats = getPlatformStats();

// Get recent transactions
$stmt = $pdo->query("
    SELECT t.*, 
           c.username as client_name, c.company_name,
           w.username as worker_name
    FROM transactions t
    JOIN users c ON t.client_id = c.id
    JOIN users w ON t.worker_id = w.id
    ORDER BY t.created_at DESC
    LIMIT 10
");
$recent_transactions = $stmt->fetchAll();

// Get pending payouts
$stmt = $pdo->query("
    SELECT p.*, u.username, u.email
    FROM payouts p
    JOIN users u ON p.worker_id = u.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at ASC
");
$pending_payouts = $stmt->fetchAll();

// Get open disputes
$stmt = $pdo->query("
    SELECT d.*, t.transaction_id as txn_id, t.amount,
           u.username as reporter_name
    FROM disputes d
    JOIN transactions t ON d.transaction_id = t.id
    JOIN users u ON d.reporter_id = u.id
    WHERE d.status = 'open'
    ORDER BY d.created_at ASC
");
$open_disputes = $stmt->fetchAll();

// Get platform settings
$stmt = $pdo->query("SELECT * FROM platform_settings LIMIT 1");
$settings = $stmt->fetch();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="p-3">
                <h5 class="fw-bold text-primary mb-4">
                    <i class="fas fa-cog me-2"></i>Admin Panel
                </h5>
                
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#overview" data-bs-toggle="pill">
                        <i class="fas fa-tachometer-alt me-2"></i>Overview
                    </a>
                    <a class="nav-link" href="#transactions" data-bs-toggle="pill">
                        <i class="fas fa-exchange-alt me-2"></i>Transactions
                    </a>
                    <a class="nav-link" href="#payouts" data-bs-toggle="pill">
                        <i class="fas fa-money-bill-wave me-2"></i>Payouts
                    </a>
                    <a class="nav-link" href="#disputes" data-bs-toggle="pill">
                        <i class="fas fa-gavel me-2"></i>Disputes
                    </a>
                    <a class="nav-link" href="#users" data-bs-toggle="pill">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a class="nav-link" href="#settings" data-bs-toggle="pill">
                        <i class="fas fa-cogs me-2"></i>Settings
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="p-4">
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold">Dashboard Overview</h2>
                            <span class="text-muted">Last updated: <?php echo date('M d, Y H:i'); ?></span>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row g-4 mb-5">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="fw-bold"><?php echo formatPrice($stats['total_revenue']); ?></h3>
                                                <p class="mb-0">Total Revenue</p>
                                            </div>
                                            <i class="fas fa-dollar-sign fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="fw-bold"><?php echo $stats['active_transactions']; ?></h3>
                                                <p class="mb-0">Active Transactions</p>
                                            </div>
                                            <i class="fas fa-exchange-alt fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="fw-bold"><?php echo $stats['pending_disputes']; ?></h3>
                                                <p class="mb-0">Pending Disputes</p>
                                            </div>
                                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h3 class="fw-bold"><?php echo $stats['active_users']; ?></h3>
                                                <p class="mb-0">Active Users</p>
                                            </div>
                                            <i class="fas fa-users fa-2x opacity-75"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="fw-bold mb-0">Recent Transactions</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-striped mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Transaction ID</th>
                                                        <th>Client</th>
                                                        <th>Worker</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_transactions as $txn): ?>
                                                    <tr>
                                                        <td class="font-monospace"><?php echo htmlspecialchars($txn['transaction_id']); ?></td>
                                                        <td><?php echo htmlspecialchars($txn['client_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($txn['worker_name']); ?></td>
                                                        <td><?php echo formatPrice($txn['amount']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $txn['status'] === 'completed' ? 'success' : ($txn['status'] === 'disputed' ? 'warning' : 'primary'); ?>">
                                                                <?php echo ucfirst($txn['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="fw-bold mb-0">Quick Actions</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary" onclick="showTab('payouts')">
                                                <i class="fas fa-money-bill-wave me-2"></i>Process Payouts (<?php echo count($pending_payouts); ?>)
                                            </button>
                                            <button class="btn btn-warning" onclick="showTab('disputes')">
                                                <i class="fas fa-gavel me-2"></i>Resolve Disputes (<?php echo count($open_disputes); ?>)
                                            </button>
                                            <button class="btn btn-info" onclick="showTab('users')">
                                                <i class="fas fa-users me-2"></i>Manage Users
                                            </button>
                                            <button class="btn btn-secondary" onclick="showTab('settings')">
                                                <i class="fas fa-cogs me-2"></i>Platform Settings
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Tab -->
                    <div class="tab-pane fade" id="transactions">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold">Transaction Management</h2>
                            <div class="btn-group">
                                <button class="btn btn-outline-primary">Export</button>
                                <button class="btn btn-outline-secondary">Filter</button>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Date</th>
                                                <th>Client</th>
                                                <th>Worker</th>
                                                <th>Amount</th>
                                                <th>Commission</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_transactions as $txn): ?>
                                            <tr>
                                                <td class="font-monospace"><?php echo htmlspecialchars($txn['transaction_id']); ?></td>
                                                <td><?php echo formatDate($txn['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($txn['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($txn['worker_name']); ?></td>
                                                <td><?php echo formatPrice($txn['amount']); ?></td>
                                                <td><?php echo formatPrice($txn['commission']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $txn['status'] === 'completed' ? 'success' : ($txn['status'] === 'disputed' ? 'warning' : 'primary'); ?>">
                                                        <?php echo ucfirst($txn['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="viewTransaction('<?php echo $txn['id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payouts Tab -->
                    <div class="tab-pane fade" id="payouts">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold">Payout Management</h2>
                            <button class="btn btn-primary">Process All Pending</button>
                        </div>

                        <?php if (empty($pending_payouts)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h4>All payouts are processed</h4>
                            <p class="text-muted">No pending payouts at this time.</p>
                        </div>
                        <?php else: ?>
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr>
                                                <th>Worker</th>
                                                <th>Amount</th>
                                                <th>Method</th>
                                                <th>Requested</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_payouts as $payout): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($payout['username']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($payout['email']); ?></small>
                                                </td>
                                                <td class="fw-bold text-success"><?php echo formatPrice($payout['amount']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo ucfirst($payout['payment_method']); ?></span>
                                                </td>
                                                <td><?php echo formatDate($payout['created_at']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-success me-1" onclick="processPayout('<?php echo $payout['id']; ?>', 'completed')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="processPayout('<?php echo $payout['id']; ?>', 'failed')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Disputes Tab -->
                    <div class="tab-pane fade" id="disputes">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold">Dispute Resolution</h2>
                            <span class="badge bg-warning fs-6"><?php echo count($open_disputes); ?> open disputes</span>
                        </div>

                        <?php if (empty($open_disputes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-balance-scale fa-3x text-success mb-3"></i>
                            <h4>No active disputes</h4>
                            <p class="text-muted">All disputes have been resolved.</p>
                        </div>
                        <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($open_disputes as $dispute): ?>
                            <div class="col-lg-6">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="fw-bold mb-0">
                                            Dispute #<?php echo substr($dispute['id'], 0, 8); ?>
                                            <span class="badge bg-warning ms-2"><?php echo formatPrice($dispute['amount']); ?></span>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Reporter:</strong> <?php echo htmlspecialchars($dispute['reporter_name']); ?></p>
                                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($dispute['reason']); ?></p>
                                        <p><strong>Description:</strong> <?php echo htmlspecialchars($dispute['description']); ?></p>
                                        <p><strong>Date:</strong> <?php echo formatDate($dispute['created_at']); ?></p>
                                        
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-success btn-sm" onclick="resolveDispute('<?php echo $dispute['id']; ?>', 'resolved')">
                                                <i class="fas fa-check me-1"></i>Resolve in Favor
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="resolveDispute('<?php echo $dispute['id']; ?>', 'closed')">
                                                <i class="fas fa-times me-1"></i>Reject Dispute
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Users Tab -->
                    <div class="tab-pane fade" id="users">
                        <h2 class="fw-bold mb-4">User Management</h2>
                        <!-- User management content would go here -->
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h4>User Management</h4>
                            <p class="text-muted">User management features coming soon.</p>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div class="tab-pane fade" id="settings">
                        <h2 class="fw-bold mb-4">Platform Settings</h2>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-0">Commission & Fees</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_settings">
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium">Commission Rate (%)</label>
                                            <input type="number" class="form-control" name="commission_rate" 
                                                   value="<?php echo $settings['commission_rate']; ?>" 
                                                   step="0.01" min="0" max="50" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium">Processing Fee ($)</label>
                                            <input type="number" class="form-control" name="processing_fee" 
                                                   value="<?php echo $settings['processing_fee']; ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium">Minimum Withdrawal ($)</label>
                                            <input type="number" class="form-control" name="min_withdrawal" 
                                                   value="<?php echo $settings['min_withdrawal']; ?>" 
                                                   step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-medium">Payout Schedule</label>
                                            <select class="form-select" name="payout_schedule" required>
                                                <option value="daily" <?php echo $settings['payout_schedule'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                <option value="weekly" <?php echo $settings['payout_schedule'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                <option value="monthly" <?php echo $settings['payout_schedule'] === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Process Payout Modal -->
<div class="modal fade" id="payoutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="process_payout">
                    <input type="hidden" name="payout_id" id="payout_id">
                    <input type="hidden" name="status" id="payout_status">
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" 
                                  placeholder="Add any notes about this payout..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="payout_action_btn">Process</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(tabId) {
    const tab = new bootstrap.Tab(document.querySelector(`[href="#${tabId}"]`));
    tab.show();
}

function processPayout(payoutId, status) {
    document.getElementById('payout_id').value = payoutId;
    document.getElementById('payout_status').value = status;
    document.getElementById('payout_action_btn').textContent = status === 'completed' ? 'Approve' : 'Reject';
    document.getElementById('payout_action_btn').className = `btn ${status === 'completed' ? 'btn-success' : 'btn-danger'}`;
    
    const modal = new bootstrap.Modal(document.getElementById('payoutModal'));
    modal.show();
}

function resolveDispute(disputeId, status) {
    if (confirm('Are you sure you want to resolve this dispute?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="resolve_dispute">
            <input type="hidden" name="dispute_id" value="${disputeId}">
            <input type="hidden" name="dispute_status" value="${status}">
            <input type="hidden" name="resolution" value="Resolved by admin">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewTransaction(transactionId) {
    // Implementation for viewing transaction details
    alert('Transaction details: ' + transactionId);
}
</script>

<?php include 'includes/footer.php'; ?>