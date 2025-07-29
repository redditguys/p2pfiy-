<?php
require_once 'config.php';

if (!isLoggedIn()) {
    showAlert('Please login to access your profile.', 'warning');
    redirectTo('login.php');
}

$page_title = 'Profile';
$user = getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company_name = trim($_POST['company_name'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    $pdo = getDBConnection();
    $errors = [];
    
    // Validate username
    if (empty($username) || strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email address is already in use';
        }
    }
    
    // Password validation
    if (!empty($new_password)) {
        if (empty($current_password) || !password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long';
        }
    }
    
    if (empty($errors)) {
        try {
            // Update profile
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, company_name = ?, skills = ?, password = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $company_name, $skills, $hashed_password, $user['id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, company_name = ?, skills = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $email, $company_name, $skills, $user['id']]);
            }
            
            showAlert('Profile updated successfully!', 'success');
            redirectTo('profile.php');
        } catch (PDOException $e) {
            showAlert('Failed to update profile: ' . $e->getMessage(), 'danger');
        }
    } else {
        foreach ($errors as $error) {
            showAlert($error, 'danger');
        }
    }
}

include 'includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="fw-bold mb-0">
                        <i class="fas fa-user me-2"></i>Profile Settings
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label fw-medium">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required minlength="3">
                                <div class="invalid-feedback">Username must be at least 3 characters long.</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-medium">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <div class="invalid-feedback">Please provide a valid email address.</div>
                            </div>
                            
                            <div class="col-12">
                                <label for="role" class="form-label fw-medium">Account Type</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                <div class="form-text">Contact support to change your account type.</div>
                            </div>
                            
                            <?php if ($user['role'] === 'client'): ?>
                            <div class="col-12">
                                <label for="company_name" class="form-label fw-medium">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($user['role'] === 'worker'): ?>
                            <div class="col-12">
                                <label for="skills" class="form-label fw-medium">Skills</label>
                                <textarea class="form-control" id="skills" name="skills" rows="3"
                                          placeholder="List your skills, e.g., Web Design, Data Entry, Writing..."><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-12">
                                <hr>
                                <h5 class="fw-bold">Change Password</h5>
                                <p class="text-muted">Leave blank to keep current password</p>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="current_password" class="form-label fw-medium">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="new_password" class="form-label fw-medium">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="<?php echo $user['role']; ?>-dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Account Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="fw-bold mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Member Since:</strong>
                            <p class="text-muted"><?php echo formatDate($user['created_at']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Account Status:</strong>
                            <p class="text-muted">
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <?php if ($user['profile_verified']): ?>
                                <span class="badge bg-primary ms-1">Verified</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($user['role'] === 'worker'): ?>
                        <div class="col-md-6">
                            <strong>Wallet Balance:</strong>
                            <p class="text-muted fw-bold text-success"><?php echo formatPrice($user['wallet_balance']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password validation
document.addEventListener('DOMContentLoaded', function() {
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    
    function validatePasswords() {
        if (newPassword.value && !currentPassword.value) {
            currentPassword.setCustomValidity('Current password is required to set new password');
        } else {
            currentPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswords);
    currentPassword.addEventListener('input', validatePasswords);
});
</script>

<?php include 'includes/footer.php'; ?>