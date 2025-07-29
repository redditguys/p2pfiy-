<?php
require_once 'config.php';

$task_id = $_GET['id'] ?? '';
if (empty($task_id)) {
    showAlert('Task not found.', 'danger');
    redirectTo('index.php');
}

$pdo = getDBConnection();

// Get task details
$stmt = $pdo->prepare("
    SELECT t.*, u.username as client_name, u.company_name, u.email as client_email
    FROM tasks t
    JOIN users u ON t.client_id = u.id
    WHERE t.id = ?
");
$stmt->execute([$task_id]);
$task = $stmt->fetch();

if (!$task) {
    showAlert('Task not found.', 'danger');
    redirectTo('index.php');
}

// Get applications for this task (if client owns it)
$applications = [];
if (isLoggedIn() && isClient() && $task['client_id'] === $_SESSION['user_id']) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.email, u.skills
        FROM submissions s
        JOIN users u ON s.worker_id = u.id
        WHERE s.task_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$task_id]);
    $applications = $stmt->fetchAll();
}

$page_title = $task['title'];
include 'includes/header.php';
?>

<div class="container my-4">
    <!-- Task Details -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($task['title']); ?></h4>
                        <span class="badge bg-primary"><?php echo ucfirst($task['category']); ?></span>
                    </div>
                    <div class="text-end">
                        <h3 class="fw-bold text-success mb-0"><?php echo formatPrice($task['price']); ?></h3>
                        <small class="text-muted">Per task</small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5 class="fw-bold">Description</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Estimated Time</h6>
                            <p class="text-muted"><?php echo htmlspecialchars($task['estimated_time'] ?: 'Not specified'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Spots Available</h6>
                            <p class="text-muted"><?php echo $task['spots_available']; ?> positions</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-bold">Posted by</h6>
                        <p class="text-muted">
                            <?php echo htmlspecialchars($task['company_name'] ?: $task['client_name']); ?>
                            <br><small>Posted on <?php echo formatDate($task['created_at']); ?></small>
                        </p>
                    </div>
                    
                    <?php if (isLoggedIn() && isWorker()): ?>
                    <div class="d-grid">
                        <button class="btn btn-primary btn-lg" onclick="applyForTask('<?php echo $task['id']; ?>')">
                            <i class="fas fa-paper-plane me-2"></i>Apply for This Task
                        </button>
                    </div>
                    <?php elseif (!isLoggedIn()): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <a href="register.php?type=worker">Sign up as a freelancer</a> to apply for this task.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Task Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Task Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Category:</span>
                        <span class="fw-bold"><?php echo ucfirst($task['category']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Budget:</span>
                        <span class="fw-bold text-success"><?php echo formatPrice($task['price']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Status:</span>
                        <span class="badge bg-<?php echo $task['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($task['status']); ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Posted:</span>
                        <span><?php echo formatDate($task['created_at']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Similar Tasks -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Similar Tasks</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">More tasks coming soon...</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Applications (for task owner) -->
    <?php if (isLoggedIn() && isClient() && $task['client_id'] === $_SESSION['user_id']): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Applications (<?php echo count($applications); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($applications)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No applications yet</h5>
                        <p class="text-muted">Applications will appear here as freelancers apply to your task.</p>
                    </div>
                    <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($applications as $app): ?>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h6 class="fw-bold"><?php echo htmlspecialchars($app['username']); ?></h6>
                                        <span class="badge bg-<?php echo $app['status'] === 'approved' ? 'success' : ($app['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($app['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($app['proof_text']); ?></p>
                                    
                                    <?php if ($app['proof_file_url']): ?>
                                    <p class="mb-3">
                                        <a href="<?php echo htmlspecialchars($app['proof_file_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-external-link-alt me-1"></i>View Portfolio
                                        </a>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <small class="text-muted">Applied: <?php echo formatDate($app['submitted_at']); ?></small>
                                    
                                    <?php if ($app['status'] === 'pending'): ?>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-success me-2" onclick="reviewApplication('<?php echo $app['id']; ?>', 'approved')">
                                            Accept
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="reviewApplication('<?php echo $app['id']; ?>', 'rejected')">
                                            Reject
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Apply Modal (for workers) -->
<?php if (isLoggedIn() && isWorker()): ?>
<div class="modal fade" id="applyTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Apply for Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="submit-application.php">
                <div class="modal-body">
                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Your Proposal</label>
                        <textarea class="form-control" name="proposal" rows="4" required
                                  placeholder="Explain how you will complete this task and your relevant experience..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Portfolio/Sample URL (Optional)</label>
                        <input type="url" class="form-control" name="portfolio_url" 
                               placeholder="https://your-portfolio.com">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyForTask(taskId) {
    const modal = new bootstrap.Modal(document.getElementById('applyTaskModal'));
    modal.show();
}
</script>
<?php endif; ?>

<script>
function reviewApplication(applicationId, status) {
    if (confirm(`Are you sure you want to ${status} this application?`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'review-application.php';
        form.innerHTML = `
            <input type="hidden" name="application_id" value="${applicationId}">
            <input type="hidden" name="status" value="${status}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>