<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    showAlert('Access denied', 'danger');
    redirectTo('index.php');
}

$page_title = 'Admin Dashboard';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getDBConnection();
    
    if ($action === 'review_submission') {
        $submission_id = $_POST['submission_id'];
        $status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        try {
            // Update submission
            $stmt = $pdo->prepare("
                UPDATE submissions 
                SET status = ?, admin_notes = ?, reviewed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $admin_notes, $submission_id]);
            
            if ($status === 'approved') {
                // Get submission and task details
                $stmt = $pdo->prepare("
                    SELECT s.*, t.price, t.title 
                    FROM submissions s 
                    JOIN tasks t ON s.task_id = t.id 
                    WHERE s.id = ?
                ");
                $stmt->execute([$submission_id]);
                $submission = $stmt->fetch();
                
                if ($submission) {
                    // Add money to worker wallet
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET wallet_balance = wallet_balance + ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$submission['price'], $submission['worker_id']]);
                    
                    // Create transaction record
                    $stmt = $pdo->prepare("
                        INSERT INTO transactions (user_id, amount, type, description, task_id, submission_id) 
                        VALUES (?, ?, 'earning', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $submission['worker_id'],
                        $submission['price'],
                        'Payment for task: ' . $submission['title'],
                        $submission['task_id'],
                        $submission_id
                    ]);
                }
            }
            
            showAlert('Submission reviewed successfully', 'success');
        } catch (PDOException $e) {
            showAlert('Failed to review submission: ' . $e->getMessage(), 'danger');
        }
    } elseif ($action === 'process_withdrawal') {
        $withdrawal_id = $_POST['withdrawal_id'];
        $status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        try {
            $stmt = $pdo->prepare("
                UPDATE withdrawals 
                SET status = ?, admin_notes = ?, processed_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $admin_notes, $withdrawal_id]);
            
            showAlert('Withdrawal processed successfully', 'success');
        } catch (PDOException $e) {
            showAlert('Failed to process withdrawal: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get admin statistics
$pdo = getDBConnection();
$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) as total_tasks FROM tasks");
$stats['total_tasks'] = $stmt->fetch()['total_tasks'];

$stmt = $pdo->query("SELECT COUNT(*) as active_workers FROM users WHERE role = 'worker'");
$stats['active_workers'] = $stmt->fetch()['active_workers'];

$stmt = $pdo->query("SELECT COUNT(*) as active_clients FROM users WHERE role = 'client'");
$stats['active_clients'] = $stmt->fetch()['active_clients'];

$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total_earnings FROM transactions WHERE type = 'earning'");
$stats['total_earnings'] = $stmt->fetch()['total_earnings'];

// Get pending submissions
$stmt = $pdo->query("
    SELECT s.*, t.title as task_title, t.price, u.name as worker_name, u.email as worker_email
    FROM submissions s 
    JOIN tasks t ON s.task_id = t.id 
    JOIN users u ON s.worker_id = u.id 
    WHERE s.status = 'pending' 
    ORDER BY s.submitted_at DESC
");
$pending_submissions = $stmt->fetchAll();

// Get pending withdrawals
$stmt = $pdo->query("
    SELECT w.*, u.name as user_name, u.email as user_email
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'pending' 
    ORDER BY w.requested_at DESC
");
$pending_withdrawals = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Admin Panel</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#submissions">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Submissions <span class="badge bg-danger ms-1"><?php echo count($pending_submissions); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#withdrawals">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Withdrawals <span class="badge bg-warning ms-1"><?php echo count($pending_withdrawals); ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 pixel-font text-primary">
                    <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-success me-2">Admin Access</span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>

            <?php displayAlert(); ?>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-5" id="dashboard">
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_tasks']; ?></div>
                        <div class="text-muted">Total Tasks</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['active_workers']; ?></div>
                        <div class="text-muted">Active Workers</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['active_clients']; ?></div>
                        <div class="text-muted">Active Clients</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo formatPrice($stats['total_earnings']); ?></div>
                        <div class="text-muted">Total Earnings</div>
                    </div>
                </div>
            </div>

            <!-- Pending Submissions -->
            <div class="minecraft-card p-4 mb-5" id="submissions">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-clipboard-check me-2"></i>
                    Pending Submissions (<?php echo count($pending_submissions); ?>)
                </h3>
                
                <?php if (empty($pending_submissions)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No pending submissions</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Worker</th>
                                    <th>Price</th>
                                    <th>Submitted</th>
                                    <th>Proof</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_submissions as $submission): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($submission['task_title']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($submission['worker_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($submission['worker_email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="price-tag"><?php echo formatPrice($submission['price']); ?></span>
                                        </td>
                                        <td><?php echo formatDate($submission['submitted_at']); ?></td>
                                        <td>
                                            <button class="btn btn-outline-info btn-sm" onclick="viewProof('<?php echo htmlspecialchars($submission['proof_text']); ?>', '<?php echo htmlspecialchars($submission['proof_file_url']); ?>')">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="review_submission">
                                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="btn btn-success btn-sm me-1">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <button class="btn btn-danger btn-sm" onclick="rejectSubmission('<?php echo $submission['id']; ?>')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Withdrawals -->
            <div class="minecraft-card p-4 mb-5" id="withdrawals">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Withdrawal Requests (<?php echo count($pending_withdrawals); ?>)
                </h3>
                
                <?php if (empty($pending_withdrawals)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No pending withdrawals</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Details</th>
                                    <th>Requested</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_withdrawals as $withdrawal): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($withdrawal['user_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($withdrawal['user_email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="price-tag"><?php echo formatPrice($withdrawal['amount']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo strtoupper($withdrawal['payment_method']); ?></span>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($withdrawal['payment_details']); ?></code>
                                        </td>
                                        <td><?php echo formatDate($withdrawal['requested_at']); ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="process_withdrawal">
                                                <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-success btn-sm me-1">
                                                    <i class="fas fa-check"></i> Complete
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="process_withdrawal">
                                                <input type="hidden" name="withdrawal_id" value="<?php echo $withdrawal['id']; ?>">
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-clock"></i> Hold
                                                </button>
                                            </form>
                                        </td>
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

<!-- Proof Modal -->
<div class="modal fade" id="proofModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proof of Work</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="proof-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Submission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="review_submission">
                    <input type="hidden" name="submission_id" id="reject_submission_id">
                    <input type="hidden" name="status" value="rejected">
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" name="admin_notes" rows="3" placeholder="Explain why this submission is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Submission</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewProof(proofText, proofFileUrl) {
    let content = '<h6>Proof Text:</h6><p>' + proofText + '</p>';
    if (proofFileUrl) {
        content += '<h6>Proof File:</h6><a href="' + proofFileUrl + '" target="_blank" class="btn btn-outline-primary">View File</a>';
    }
    document.getElementById('proof-content').innerHTML = content;
    new bootstrap.Modal(document.getElementById('proofModal')).show();
}

function rejectSubmission(submissionId) {
    document.getElementById('reject_submission_id').value = submissionId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>