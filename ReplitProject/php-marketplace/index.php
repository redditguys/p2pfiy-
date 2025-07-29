<?php
require_once 'config.php';

$page_title = 'Home';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$price_range = $_GET['price_range'] ?? '';

// Get tasks from database
$pdo = getDBConnection();
$query = "SELECT t.*, u.username as client_name, u.company_name
          FROM tasks t
          JOIN users u ON t.client_id = u.id
          WHERE t.status = 'active' AND t.spots_available > 0";
$params = [];

// Apply filters
if (!empty($search)) {
    $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $query .= " AND t.category = ?";
    $params[] = $category;
}

if (!empty($price_range)) {
    switch ($price_range) {
        case '0.02-10.00':
            $query .= " AND t.price BETWEEN 0.02 AND 10.00";
            break;
        case '10.00-50.00':
            $query .= " AND t.price BETWEEN 10.00 AND 50.00";
            break;
        case '50.00+':
            $query .= " AND t.price > 50.00";
            break;
    }
}

$query .= " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get platform stats
$stats = getPlatformStats();

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4">Professional Freelance Marketplace</h1>
                <p class="lead mb-5">Connect with skilled professionals for your digital projects. From design to development, find the perfect freelancer for your needs.</p>
                
                <!-- Quick Stats -->
                <div class="row g-4 mt-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['total_tasks']; ?></div>
                            <div class="text-light">Active Tasks</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo formatPrice($stats['total_revenue']); ?></div>
                            <div class="text-light">Total Paid</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['total_workers']; ?></div>
                            <div class="text-light">Active Freelancers</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number"><?php echo $stats['completed_tasks']; ?></div>
                            <div class="text-light">Completed Tasks</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-5">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php?type=worker" class="btn btn-light btn-lg me-3">
                            <i class="fas fa-user-plus me-2"></i>Join as Freelancer
                        </a>
                        <a href="register.php?type=client" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-briefcase me-2"></i>Post a Project
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="container my-5">
    <div class="card p-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-medium">Search Tasks</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search for tasks...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-medium">Category</label>
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <option value="data entry" <?php echo $category === 'data entry' ? 'selected' : ''; ?>>Data Entry</option>
                    <option value="writing" <?php echo $category === 'writing' ? 'selected' : ''; ?>>Writing & Content</option>
                    <option value="design" <?php echo $category === 'design' ? 'selected' : ''; ?>>Design & Graphics</option>
                    <option value="research" <?php echo $category === 'research' ? 'selected' : ''; ?>>Research</option>
                    <option value="development" <?php echo $category === 'development' ? 'selected' : ''; ?>>Development</option>
                    <option value="marketing" <?php echo $category === 'marketing' ? 'selected' : ''; ?>>Marketing</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-medium">Budget</label>
                <select class="form-select" name="price_range">
                    <option value="">Any Budget</option>
                    <option value="0.02-10.00" <?php echo $price_range === '0.02-10.00' ? 'selected' : ''; ?>>$0.02 - $10</option>
                    <option value="10.00-50.00" <?php echo $price_range === '10.00-50.00' ? 'selected' : ''; ?>>$10 - $50</option>
                    <option value="50.00+" <?php echo $price_range === '50.00+' ? 'selected' : ''; ?>>$50+</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
            <div class="col-md-1">
                <a href="index.php" class="btn btn-outline-secondary w-100" title="Clear filters">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Task Listings -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Available Tasks</h2>
        <span class="badge bg-primary fs-6"><?php echo count($tasks); ?> tasks found</span>
    </div>

    <?php if (empty($tasks)): ?>
        <div class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No tasks found</h4>
            <p class="text-muted">Try adjusting your search criteria or check back later for new tasks.</p>
            <a href="index.php" class="btn btn-primary">View All Tasks</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tasks as $task): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="task-card p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge-category"><?php echo strtoupper($task['category']); ?></span>
                            <span class="price-tag"><?php echo formatPrice($task['price']); ?></span>
                        </div>
                        
                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($task['title']); ?></h5>
                        <p class="text-muted mb-3 flex-grow-1">
                            <?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>...
                        </p>
                        
                        <div class="row text-sm text-muted mb-3">
                            <div class="col-6">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo $task['estimated_time'] ?: 'Flexible'; ?>
                            </div>
                            <div class="col-6">
                                <i class="fas fa-users me-1"></i>
                                <?php echo $task['spots_available']; ?> spots
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-building me-1"></i>
                                <?php echo htmlspecialchars($task['company_name'] ?: $task['client_name']); ?>
                            </small>
                        </div>
                        
                        <?php if (isLoggedIn() && isWorker()): ?>
                            <button class="btn btn-primary w-100" onclick="applyForTask('<?php echo $task['id']; ?>')">
                                <i class="fas fa-paper-plane me-1"></i>Apply Now
                            </button>
                        <?php elseif (isLoggedIn() && isClient()): ?>
                            <button class="btn btn-outline-primary w-100" disabled>
                                <i class="fas fa-info-circle me-1"></i>Client View Only
                            </button>
                        <?php else: ?>
                            <a href="register.php?type=worker" class="btn btn-primary w-100">
                                <i class="fas fa-user-plus me-1"></i>Join to Apply
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if (count($tasks) >= 12): ?>
            <div class="text-center mt-5">
                <button class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Load More Tasks
                </button>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- How It Works Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">How PixelTask Works</h2>
            <p class="lead text-muted">Simple steps to get started</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-user-plus fa-3x text-primary"></i>
                </div>
                <h5 class="fw-bold">1. Sign Up</h5>
                <p class="text-muted">Create your account as a client or freelancer in minutes</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-search fa-3x text-primary"></i>
                </div>
                <h5 class="fw-bold">2. Find or Post</h5>
                <p class="text-muted">Browse tasks or post your project requirements</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-3">
                    <i class="fas fa-handshake fa-3x text-primary"></i>
                </div>
                <h5 class="fw-bold">3. Get Paid</h5>
                <p class="text-muted">Complete work and receive secure payments</p>
            </div>
        </div>
    </div>
</div>

<!-- Apply for Task Modal -->
<?php if (isLoggedIn() && isWorker()): ?>
<div class="modal fade" id="applyTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-paper-plane me-2"></i>Apply for Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="submit-application.php">
                <div class="modal-body">
                    <input type="hidden" name="task_id" id="apply_task_id">
                    <div id="task_details" class="mb-3"></div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-medium">Your Proposal</label>
                        <textarea class="form-control" name="proposal" rows="4" 
                                  placeholder="Describe how you will complete this task and any relevant experience..." 
                                  required></textarea>
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
    const tasks = <?php echo json_encode($tasks); ?>;
    const task = tasks.find(t => t.id === taskId);
    
    if (task) {
        document.getElementById('apply_task_id').value = taskId;
        document.getElementById('task_details').innerHTML = `
            <div class="card bg-light p-3">
                <h6 class="fw-bold text-primary">${task.title}</h6>
                <p class="text-muted mb-1">${task.description}</p>
                <div class="d-flex justify-content-between mt-2">
                    <small><i class="fas fa-tag me-1"></i>${task.category}</small>
                    <small class="fw-bold text-success">${parseFloat(task.price).toFixed(2)}</small>
                </div>
            </div>
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('applyTaskModal'));
        modal.show();
    }
}
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>