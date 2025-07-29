<?php
require_once 'config.php';

$page_title = 'Register';
$user_type = $_GET['type'] ?? 'worker';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'worker';
    $company_name = trim($_POST['company_name'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!in_array($role, ['client', 'worker'])) {
        $errors[] = 'Invalid account type';
    }
    
    if (empty($errors)) {
        $pdo = getDBConnection();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            showAlert('An account with this email already exists.', 'danger');
        } else {
            try {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, role, company_name, skills, is_active, profile_verified) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, 0)
                ");
                
                $stmt->execute([
                    $username,
                    $email,
                    $hashed_password,
                    $role,
                    $role === 'client' ? $company_name : null,
                    $role === 'worker' ? $skills : null
                ]);
                
                // Log the user in automatically
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_email'] = $email;
                
                showAlert('Account created successfully! Welcome to PixelTask.', 'success');
                
                // Redirect based on role
                if ($role === 'client') {
                    redirectTo('client-dashboard.php');
                } else {
                    redirectTo('worker-dashboard.php');
                }
                
            } catch (PDOException $e) {
                showAlert('Registration failed. Please try again.', 'danger');
            }
        }
    } else {
        foreach ($errors as $error) {
            showAlert($error, 'danger');
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-2x text-primary mb-3"></i>
                        <h3 class="fw-bold">Join PixelTask</h3>
                        <p class="text-muted">Create your account and start earning or hiring today</p>
                    </div>

                    <!-- Account Type Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-medium">Account Type</label>
                        <div class="row g-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="account_type" id="worker_type" 
                                       value="worker" <?php echo $user_type === 'worker' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 p-3" for="worker_type">
                                    <i class="fas fa-user-tie d-block mb-2"></i>
                                    <strong>Freelancer</strong>
                                    <small class="d-block text-muted">Find and complete tasks</small>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="account_type" id="client_type" 
                                       value="client" <?php echo $user_type === 'client' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-success w-100 p-3" for="client_type">
                                    <i class="fas fa-briefcase d-block mb-2"></i>
                                    <strong>Client</strong>
                                    <small class="d-block text-muted">Post tasks and hire</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="role" id="selected_role" value="<?php echo $user_type; ?>">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label fw-medium">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Choose a username" required minlength="3">
                                <div class="invalid-feedback">
                                    Username must be at least 3 characters long.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter your email" required>
                                <div class="invalid-feedback">
                                    Please provide a valid email address.
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-medium">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Create a password" required minlength="6">
                                <div class="invalid-feedback">
                                    Password must be at least 6 characters long.
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label fw-medium">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm your password" required>
                                <div class="invalid-feedback">
                                    Passwords must match.
                                </div>
                            </div>
                        </div>

                        <!-- Client-specific fields -->
                        <div id="client_fields" class="mb-3" style="display: <?php echo $user_type === 'client' ? 'block' : 'none'; ?>;">
                            <label for="company_name" class="form-label fw-medium">Company Name (Optional)</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" 
                                   placeholder="Your company or organization">
                        </div>

                        <!-- Worker-specific fields -->
                        <div id="worker_fields" class="mb-3" style="display: <?php echo $user_type === 'worker' ? 'block' : 'none'; ?>;">
                            <label for="skills" class="form-label fw-medium">Skills (Optional)</label>
                            <textarea class="form-control" id="skills" name="skills" rows="3" 
                                      placeholder="List your skills, e.g., Web Design, Data Entry, Writing..."></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="terms.php" target="_blank">Terms of Service</a> 
                                and <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                            <div class="invalid-feedback">
                                You must agree to the terms and conditions.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="fas fa-user-plus me-1"></i>Create Account
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="text-muted">
                            Already have an account? 
                            <a href="login.php" class="text-primary text-decoration-none">Sign in here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Benefits Section -->
<div class="bg-light py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h3 class="fw-bold">Why Choose PixelTask?</h3>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <i class="fas fa-dollar-sign fa-2x text-success mb-3"></i>
                <h6 class="fw-bold">Competitive Rates</h6>
                <p class="text-muted small">Earn fair wages for your skills</p>
            </div>
            <div class="col-md-3 text-center">
                <i class="fas fa-clock fa-2x text-primary mb-3"></i>
                <h6 class="fw-bold">Flexible Schedule</h6>
                <p class="text-muted small">Work when you want</p>
            </div>
            <div class="col-md-3 text-center">
                <i class="fas fa-shield-alt fa-2x text-warning mb-3"></i>
                <h6 class="fw-bold">Secure Payments</h6>
                <p class="text-muted small">Protected transactions</p>
            </div>
            <div class="col-md-3 text-center">
                <i class="fas fa-chart-line fa-2x text-info mb-3"></i>
                <h6 class="fw-bold">Grow Your Business</h6>
                <p class="text-muted small">Build your reputation</p>
            </div>
        </div>
    </div>
</div>

<script>
// Handle account type switching
document.addEventListener('DOMContentLoaded', function() {
    const workerRadio = document.getElementById('worker_type');
    const clientRadio = document.getElementById('client_type');
    const roleInput = document.getElementById('selected_role');
    const clientFields = document.getElementById('client_fields');
    const workerFields = document.getElementById('worker_fields');
    
    function toggleFields() {
        if (clientRadio.checked) {
            roleInput.value = 'client';
            clientFields.style.display = 'block';
            workerFields.style.display = 'none';
        } else {
            roleInput.value = 'worker';
            clientFields.style.display = 'none';
            workerFields.style.display = 'block';
        }
    }
    
    workerRadio.addEventListener('change', toggleFields);
    clientRadio.addEventListener('change', toggleFields);
    
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (password.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Passwords do not match');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    password.addEventListener('change', validatePassword);
    confirmPassword.addEventListener('keyup', validatePassword);
});
</script>

<?php include 'includes/footer.php'; ?>