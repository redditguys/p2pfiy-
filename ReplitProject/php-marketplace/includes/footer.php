    <!-- Admin Access Modal -->
    <div class="modal fade" id="adminAccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-shield-alt me-2"></i>Admin Access
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Admin Access Key</label>
                            <input type="password" class="form-control" name="admin_access_key" 
                                   placeholder="Enter admin access key" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Access Admin Panel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>PixelTask</h5>
                    <p class="text-muted">Professional freelance marketplace connecting clients with skilled workers worldwide.</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted">Browse Tasks</a></li>
                        <li><a href="register.php" class="text-muted">Join as Worker</a></li>
                        <li><a href="register.php" class="text-muted">Post Tasks</a></li>
                        <li><a href="about.php" class="text-muted">About Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="help.php" class="text-muted">Help Center</a></li>
                        <li><a href="contact.php" class="text-muted">Contact Us</a></li>
                        <li><a href="terms.php" class="text-muted">Terms of Service</a></li>
                        <li><a href="privacy.php" class="text-muted">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2025 PixelTask. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <a href="#" onclick="showAdminAccess()" class="text-decoration-none">Admin</a>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Admin access trigger
        function showAdminAccess() {
            const modal = new bootstrap.Modal(document.getElementById('adminAccessModal'));
            modal.show();
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                }, 5000);
            });
        });

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });

        // Price formatting
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        }

        // Time ago formatting
        function timeAgo(date) {
            const now = new Date();
            const diff = now - new Date(date);
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);

            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes} minutes ago`;
            if (hours < 24) return `${hours} hours ago`;
            return `${days} days ago`;
        }

        // Copy to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // You could show a toast notification here
                console.log('Copied to clipboard');
            });
        }

        // Confirm actions
        function confirmAction(message) {
            return confirm(message || 'Are you sure you want to perform this action?');
        }

        // Loading state for buttons
        function setLoadingState(button, loading) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Loading...';
            } else {
                button.disabled = false;
                // Restore original text if needed
            }
        }

        // Real-time updates (if needed)
        function startRealTimeUpdates() {
            // This could be implemented with WebSockets or Server-Sent Events
            setInterval(function() {
                // Check for updates
                if (typeof updateDashboard === 'function') {
                    updateDashboard();
                }
            }, 30000); // Check every 30 seconds
        }
    </script>
</body>
</html>