<?php
require_once 'config.php';

if (!isLoggedIn() || !isClient()) {
    showAlert('Access denied', 'danger');
    redirectTo('index.php');
}

$page_title = 'Client Dashboard';

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_task') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $estimated_time = trim($_POST['estimated_time'] ?? '');
    $spots_available = intval($_POST['spots_available'] ?? 1);
    
    if (empty($title) || empty($description) || empty($category) || $price < MIN_TASK_PRICE) {
        showAlert('Please fill all required fields and ensure price is at least $' . MIN_TASK_PRICE, 'danger');
    } else {
        $pdo = getDBConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, category, price, estimated_time, spots_available, client_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $description,
                $category,
                $price,
                $estimated_time ?: null,
                $spots_available,
                $_SESSION['user_id']
            ]);
            
            showAlert('Task created successfully!', 'success');
        } catch (PDOException $e) {
            showAlert('Failed to create task: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get client's tasks
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT t.*, 
           COUNT(s.id) as submission_count,
           COUNT(CASE WHEN s.status = 'pending' THEN 1 END) as pending_submissions,
           COUNT(CASE WHEN s.status = 'approved' THEN 1 END) as approved_submissions
    FROM tasks t 
    LEFT JOIN submissions s ON t.id = s.task_id 
    WHERE t.client_id = ? 
    GROUP BY t.id 
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                    <span>Client Panel</span>
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tasks">
                            <i class="fas fa-tasks me-2"></i>My Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#create">
                            <i class="fas fa-plus me-2"></i>Create Task
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2 pixel-font text-primary">
                    <i class="fas fa-briefcase me-2"></i>Client Dashboard
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <span class="badge bg-success me-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php" class="btn btn-outline-primary btn-sm me-2">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>

            <?php displayAlert(); ?>

            <!-- Quick Stats -->
            <div class="row g-4 mb-5" id="dashboard">
                <div class="col-xl-4 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count($tasks); ?></div>
                        <div class="text-muted">Total Tasks Posted</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'active')); ?></div>
                        <div class="text-muted">Active Tasks</div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo array_sum(array_column($tasks, 'submission_count')); ?></div>
                        <div class="text-muted">Total Submissions</div>
                    </div>
                </div>
            </div>

            <!-- Create Task Section -->
            <div class="minecraft-card p-4 mb-5" id="create">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-plus me-2"></i>Create New Task
                </h3>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_task">
                    
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Task Title</label>
                            <input type="text" class="form-control" name="title" placeholder="Enter task title" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="">Select category</option>
                                <option value="data entry">Data Entry</option>
                                <option value="writing">Writing</option>
                                <option value="design">Design</option>
                                <option value="research">Research</option>
                                <option value="social media">Social Media</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" placeholder="Describe the task requirements in detail" required></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Price ($)</label>
                            <input type="number" class="form-control" name="price" step="0.01" min="<?php echo MIN_TASK_PRICE; ?>" placeholder="<?php echo MIN_TASK_PRICE; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estimated Time</label>
                            <input type="text" class="form-control" name="estimated_time" placeholder="e.g., 2 hours">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Available Spots</label>
                            <input type="number" class="form-control" name="spots_available" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn minecraft-btn btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Task
                        </button>
                    </div>
                </form>
            </div>

            <!-- My Tasks -->
            <div class="minecraft-card p-4 mb-5" id="tasks">
                <h3 class="pixel-font text-primary mb-4">
                    <i class="fas fa-tasks me-2"></i>My Tasks (<?php echo count($tasks); ?>)
                </h3>
                
                <?php if (empty($tasks)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No tasks created yet</h5>
                        <p class="text-muted">Create your first task to get started!</p>
                        <a href="#create" class="btn minecraft-btn">Create Task</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-lg-6">
                                <div class="task-card p-4 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge-category"><?php echo strtoupper($task['category']); ?></span>
                                        <div class="text-end">
                                            <span class="price-tag d-block"><?php echo formatPrice($task['price']); ?></span>
                                            <span class="badge bg-<?php echo $task['status'] === 'active' ? 'success' : 'secondary'; ?> mt-1">
                                                <?php echo strtoupper($task['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <h5 class="text-light mb-2"><?php echo htmlspecialchars($task['title']); ?></h5>
                                    <p class="text-muted mb-3"><?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>...</p>
                                    
                                    <div class="row text-sm mb-3">
                                        <div class="col-6">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $task['estimated_time'] ?: 'Not specified'; ?>
                                        </div>
                                        <div class="col-6">
                                            <i class="fas fa-users me-1"></i>
                                            <?php echo $task['spots_available']; ?> spots left
                                        </div>
                                    </div>
                                    
                                    <div class="row text-sm mb-3">
                                        <div class="col-4">
                                            <div class="text-center">
                                                <div class="h5 text-primary mb-1"><?php echo $task['submission_count']; ?></div>
                                                <small class="text-muted">Total</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-center">
                                                <div class="h5 text-warning mb-1"><?php echo $task['pending_submissions']; ?></div>
                                                <small class="text-muted">Pending</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-center">
                                                <div class="h5 text-success mb-1"><?php echo $task['approved_submissions']; ?></div>
                                                <small class="text-muted">Approved</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-auto">
                                        <button class="btn btn-outline-primary btn-sm w-100" onclick="viewTaskSubmissions('<?php echo $task['id']; ?>')">
                                            <i class="fas fa-eye me-1"></i>View Submissions
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Submissions Modal -->
<div class="modal fade" id="submissionsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Task Submissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="submissions-content">
                    <div class="text-center">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2">Loading submissions...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewTaskSubmissions(taskId) {
    const modal = new bootstrap.Modal(document.getElementById('submissionsModal'));
    modal.show();
    
    // Here you would typically fetch submissions via AJAX
    // For now, we'll show a placeholder
    setTimeout(() => {
        document.getElementById('submissions-content').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-clipboard-check fa-2x text-muted mb-3"></i>
                <p class="text-muted">Submissions feature will be implemented in the full version.</p>
                <p class="text-muted">This will show all worker submissions for this task.</p>
            </div>
        `;
    }, 1000);
}
</script>

<?php include 'includes/footer.php'; ?>