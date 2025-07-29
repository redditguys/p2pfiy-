<?php
require_once 'config.php';

if (!isLoggedIn() || !isClient()) {
    showAlert('Access denied. Client account required.', 'danger');
    redirectTo('login.php');
}

$page_title = 'Client Dashboard';
$user = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $pdo = getDBConnection();
    
    if ($action === 'create_task') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = $_POST['category'];
        $price = (float)$_POST['price'];
        $estimated_time = trim($_POST['estimated_time']);
        $spots_available = (int)$_POST['spots_available'];
        
        if (!empty($title) && !empty($description) && $price >= MIN_TASK_PRICE) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO tasks (title, description, category, price, estimated_time, spots_available, client_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $category, $price, $estimated_time, $spots_available, $user['id']]);
                showAlert('Task posted successfully!', 'success');
            } catch (PDOException $e) {
                showAlert('Failed to post task: ' . $e->getMessage(), 'danger');
            }
        } else {
            showAlert('Please fill in all required fields and ensure price meets minimum requirement.', 'warning');
        }
    }
}

// Get client's tasks
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT t.*, 
           COUNT(s.id) as application_count,
           COUNT(CASE WHEN s.status = 'approved' THEN 1 END) as approved_count
    FROM tasks t
    LEFT JOIN submissions s ON t.id = s.task_id
    WHERE t.client_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute([$user['id']]);
$my_tasks = $stmt->fetchAll();

// Get recent transactions
$stmt = $pdo->prepare("
    SELECT t.*, w.username as worker_name, ta.title as task_title
    FROM transactions t
    JOIN users w ON t.worker_id = w.id
    JOIN tasks ta ON t.task_id = ta.id
    WHERE t.client_id = ?
    ORDER BY t.created_at DESC
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recent_transactions = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_tasks,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN price END), 0) as total_spent
    FROM tasks 
    WHERE client_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

include 'includes/header.php';
?>

<div class="container my-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-primary text-white rounded p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
                        <p class="mb-0">Manage your projects and track progress from your dashboard.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                            <i class="fas fa-plus me-2"></i>Post New Task
                        </button>
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
                    <i class="fas fa-tasks fa-2x text-primary mb-3"></i>
                    <h3 class="fw-bold"><?php echo $stats['total_tasks']; ?></h3>
                    <p class="text-muted mb-0">Total Tasks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                    <h3 class="fw-bold"><?php echo $stats['active_tasks']; ?></h3>
                    <p class="text-muted mb-0">Active Tasks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                    <h3 class="fw-bold"><?php echo $stats['completed_tasks']; ?></h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-dollar-sign fa-2x text-info mb-3"></i>
                    <h3 class="fw-bold"><?php echo formatPrice($stats['total_spent']); ?></h3>
                    <p class="text-muted mb-0">Total Spent</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">
        <!-- My Tasks -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">My Tasks</h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                        <i class="fas fa-plus me-1"></i>New Task
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($my_tasks)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                        <h5>No tasks yet</h5>
                        <p class="text-muted">Start by posting your first task to find skilled freelancers.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                            <i class="fas fa-plus me-1"></i>Post Your First Task
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Applications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                        <br><small class="text-muted"><?php echo formatDate($task['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo ucfirst($task['category']); ?></span>
                                    </td>
                                    <td class="fw-bold text-success"><?php echo formatPrice($task['price']); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $task['application_count']; ?></span>
                                        <?php if ($task['approved_count'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $task['approved_count']; ?> approved</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $task['status'] === 'active' ? 'success' : ($task['status'] === 'completed' ? 'primary' : 'secondary'); ?>">
                                            <?php echo ucfirst($task['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewTask('<?php echo $task['id']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($task['status'] === 'active'): ?>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editTask('<?php echo $task['id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>
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

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_transactions)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-exchange-alt fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No transactions yet</p>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_transactions as $txn): ?>
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($txn['task_title']); ?></h6>
                                    <p class="mb-1 text-muted small">
                                        Worker: <?php echo htmlspecialchars($txn['worker_name']); ?>
                                    </p>
                                    <small class="text-muted"><?php echo formatDate($txn['created_at']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold"><?php echo formatPrice($txn['amount']); ?></span>
                                    <br>
                                    <span class="badge bg-<?php echo $txn['status'] === 'completed' ? 'success' : 'warning'; ?> small">
                                        <?php echo ucfirst($txn['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                            <i class="fas fa-plus me-2"></i>Post New Task
                        </button>
                        <a href="index.php" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Browse Freelancers
                        </a>
                        <a href="profile.php" class="btn btn-outline-secondary">
                            <i class="fas fa-user me-2"></i>Update Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Task Modal -->
<div class="modal fade" id="newTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus me-2"></i>Post New Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_task">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label fw-medium">Task Title</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   placeholder="Enter a clear, descriptive title" required>
                            <div class="invalid-feedback">Please provide a task title.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label fw-medium">Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Choose category...</option>
                                <option value="data entry">Data Entry</option>
                                <option value="writing">Writing & Content</option>
                                <option value="design">Design & Graphics</option>
                                <option value="development">Development</option>
                                <option value="marketing">Marketing</option>
                                <option value="research">Research</option>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="price" class="form-label fw-medium">Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   min="<?php echo MIN_TASK_PRICE; ?>" step="0.01" required>
                            <div class="form-text">Minimum: <?php echo formatPrice(MIN_TASK_PRICE); ?></div>
                            <div class="invalid-feedback">Please enter a valid price.</div>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label fw-medium">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" 
                                      placeholder="Describe the task requirements, deliverables, and any specific instructions..." required></textarea>
                            <div class="invalid-feedback">Please provide a detailed description.</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estimated_time" class="form-label fw-medium">Estimated Time</label>
                            <input type="text" class="form-control" id="estimated_time" name="estimated_time" 
                                   placeholder="e.g., 2 hours, 1 day">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="spots_available" class="form-label fw-medium">Number of Workers Needed</label>
                            <input type="number" class="form-control" id="spots_available" name="spots_available" 
                                   value="1" min="1" max="10" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Post Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewTask(taskId) {
    // Implementation for viewing task details and applications
    window.location.href = `task-details.php?id=${taskId}`;
}

function editTask(taskId) {
    // Implementation for editing task
    alert('Edit task functionality coming soon');
}
</script>

<?php include 'includes/footer.php'; ?>