<?php
require_once 'config.php';

$page_title = 'Home';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$price_range = $_GET['price_range'] ?? '';

// Get tasks from database
$pdo = getDBConnection();
$query = "SELECT t.*, u.name as client_name, u.company_name 
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
        case '0.02-1.00':
            $query .= " AND t.price BETWEEN 0.02 AND 1.00";
            break;
        case '1.00-5.00':
            $query .= " AND t.price BETWEEN 1.00 AND 5.00";
            break;
        case '5.00+':
            $query .= " AND t.price > 5.00";
            break;
    }
}

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get stats
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM tasks WHERE status = 'active') as total_tasks,
        (SELECT COUNT(*) FROM users WHERE role = 'worker') as total_workers,
        (SELECT COUNT(*) FROM users WHERE role = 'client') as total_clients
";
$stmt = $pdo->query($stats_query);
$stats = $stmt->fetch();

include 'includes/header.php';
?>

<div class="container-fluid px-0">
    <?php displayAlert(); ?>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="display-4 pixel-font text-primary mb-4">Craft Your Income</h1>
                    <p class="lead mb-5">Complete micro-tasks and earn real money. From data entry to creative work - find your perfect gig in our cyber marketplace.</p>
                    
                    <!-- Stats -->
                    <div class="row g-4 mt-4">
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $stats['total_tasks']; ?></div>
                                <div class="text-muted">Active Tasks</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number">$<?php echo number_format(MIN_TASK_PRICE, 2); ?>+</div>
                                <div class="text-muted">Min Task Value</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="stats-number"><?php echo $stats['total_workers']; ?></div>
                                <div class="text-muted">Active Workers</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Filters -->
    <div class="container my-5">
        <div class="minecraft-card p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search Tasks</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search tasks...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="">All Categories</option>
                        <option value="data entry" <?php echo $category === 'data entry' ? 'selected' : ''; ?>>Data Entry</option>
                        <option value="writing" <?php echo $category === 'writing' ? 'selected' : ''; ?>>Writing</option>
                        <option value="design" <?php echo $category === 'design' ? 'selected' : ''; ?>>Design</option>
                        <option value="research" <?php echo $category === 'research' ? 'selected' : ''; ?>>Research</option>
                        <option value="social media" <?php echo $category === 'social media' ? 'selected' : ''; ?>>Social Media</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Price Range</label>
                    <select class="form-select" name="price_range">
                        <option value="">All Prices</option>
                        <option value="0.02-1.00" <?php echo $price_range === '0.02-1.00' ? 'selected' : ''; ?>>$0.02 - $1.00</option>
                        <option value="1.00-5.00" <?php echo $price_range === '1.00-5.00' ? 'selected' : ''; ?>>$1.00 - $5.00</option>
                        <option value="5.00+" <?php echo $price_range === '5.00+' ? 'selected' : ''; ?>>$5.00+</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn minecraft-btn w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="index.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Task Listings -->
    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="pixel-font text-primary">Available Tasks</h2>
            <span class="badge bg-primary fs-6"><?php echo count($tasks); ?> tasks found</span>
        </div>
        
        <?php if (empty($tasks)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No tasks found</h4>
                <p class="text-muted">Try adjusting your search criteria or check back later for new tasks.</p>
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
                            
                            <h5 class="text-light mb-2"><?php echo htmlspecialchars($task['title']); ?></h5>
                            <p class="text-muted mb-3 flex-grow-1"><?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>...</p>
                            
                            <div class="row text-sm text-muted mb-3">
                                <div class="col-6">
                                    <i class="fas fa-clock me-1"></i>
                                    <?php echo $task['estimated_time'] ?: 'Not specified'; ?>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $task['spots_available']; ?> spots left
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-building me-1"></i>
                                    <?php echo htmlspecialchars($task['company_name'] ?: $task['client_name']); ?>
                                </small>
                            </div>
                            
                            <?php if (isLoggedIn() && isWorker()): ?>
                                <button class="btn minecraft-btn w-100" onclick="applyForTask('<?php echo $task['id']; ?>')">
                                    <i class="fas fa-paper-plane me-1"></i>Apply Now
                                </button>
                            <?php else: ?>
                                <button class="btn minecraft-btn w-100" data-bs-toggle="modal" data-bs-target="#workerModal">
                                    <i class="fas fa-user-plus me-1"></i>Join to Apply
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Load More Section -->
            <?php if (count($tasks) >= 12): ?>
                <div class="text-center mt-5">
                    <button class="btn minecraft-btn btn-lg">
                        <i class="fas fa-plus me-2"></i>Load More Tasks
                    </button>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Apply for Task Modal -->
<?php if (isLoggedIn() && isWorker()): ?>
<div class="modal fade" id="applyTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title pixel-font">
                    <i class="fas fa-paper-plane me-2"></i>Submit Proof of Work
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="submit-task.php">
                <div class="modal-body">
                    <input type="hidden" name="task_id" id="apply_task_id">
                    <div id="task_details" class="mb-3"></div>
                    
                    <div class="mb-3">
                        <label class="form-label">Proof of Work</label>
                        <textarea class="form-control" name="proof_text" rows="5" 
                                  placeholder="Describe your completed work or provide links to your submission" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Proof File URL (optional)</label>
                        <input type="url" class="form-control" name="proof_file_url" 
                               placeholder="https://example.com/your-work.jpg">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn minecraft-btn">Submit Proof</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyForTask(taskId) {
    // Find task details
    const tasks = <?php echo json_encode($tasks); ?>;
    const task = tasks.find(t => t.id === taskId);
    
    if (task) {
        document.getElementById('apply_task_id').value = taskId;
        document.getElementById('task_details').innerHTML = `
            <div class="minecraft-card p-3">
                <h6 class="text-primary">${task.title}</h6>
                <p class="text-muted mb-1">${task.description}</p>
                <div class="d-flex justify-content-between">
                    <small><i class="fas fa-tag me-1"></i>${task.category}</small>
                    <small class="price-tag">${parseFloat(task.price).toFixed(2)}</small>
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