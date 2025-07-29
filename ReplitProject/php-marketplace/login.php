<?php
require_once 'config.php';

$page_title = 'Login';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        $pdo = getDBConnection();
        
        // Check for admin login
        if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
            $_SESSION['user_id'] = 'admin';
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_email'] = ADMIN_EMAIL;
            showAlert('Welcome back, Admin!', 'success');
            redirectTo('admin-dashboard.php');
        }
        
        // Regular user login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            
            showAlert('Welcome back, ' . $user['username'] . '!', 'success');
            
            // Redirect based on role
            switch ($user['role']) {
                case 'client':
                    redirectTo('client-dashboard.php');
                case 'worker':
                    redirectTo('worker-dashboard.php');
                default:
                    redirectTo('index.php');
            }
        } else {
            showAlert('Invalid email or password. Please try again.', 'danger');
        }
    } else {
        showAlert('Please fill in all fields.', 'warning');
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-sign-in-alt fa-2x text-primary mb-3"></i>
                        <h3 class="fw-bold">Welcome Back</h3>
                        <p class="text-muted">Sign in to your account</p>
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label fw-medium">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="Enter your email" required>
                            <div class="invalid-feedback">
                                Please provide a valid email address.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-medium">Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                            <div class="invalid-feedback">
                                Please provide your password.
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-sign-in-alt me-1"></i>Sign In
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="text-muted">
                            Don't have an account? 
                            <a href="register.php" class="text-primary text-decoration-none">Sign up here</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Access -->
            <div class="card mt-4">
                <div class="card-body p-3">
                    <h6 class="fw-bold mb-3">Quick Access</h6>
                    <div class="d-grid gap-2">
                        <a href="register.php?type=worker" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus me-1"></i>Join as Freelancer
                        </a>
                        <a href="register.php?type=client" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-briefcase me-1"></i>Post a Project
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <i class="fas fa-shield-alt fa-2x text-primary mb-3"></i>
                <h5 class="fw-bold">Secure Payments</h5>
                <p class="text-muted">Your payments are protected with our secure escrow system</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-users fa-2x text-primary mb-3"></i>
                <h5 class="fw-bold">Verified Freelancers</h5>
                <p class="text-muted">Work with skilled professionals verified by our team</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                <h5 class="fw-bold">24/7 Support</h5>
                <p class="text-muted">Get help whenever you need it with our support team</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>