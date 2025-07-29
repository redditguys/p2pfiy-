    <!-- Footer -->
    <footer class="footer mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <h5 class="pixel-font text-primary mb-3"><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">The ultimate platform for micro-tasks and instant earnings with Minecraft-themed design.</p>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-3">For Workers</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-muted text-decoration-none">Browse Tasks</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">How to Apply</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Payment Methods</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Worker Guide</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-3">For Clients</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Post a Task</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Pricing</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Quality Guidelines</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Client Dashboard</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6 class="mb-3">Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Help Center</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Terms of Service</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">Payment methods: JazzCash, Easypaisa, Paytm, USDT</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Auth Modals -->
    <?php if (!isLoggedIn()): ?>
    <!-- Worker Registration Modal -->
    <div class="modal fade" id="workerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title pixel-font">
                        <i class="fas fa-user me-2"></i>Join as Worker
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="auth.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="role" value="worker">
                        
                        <div class="mb-3">
                            <label class="form-label">Access Key</label>
                            <input type="text" class="form-control" name="access_key" placeholder="Enter your worker key" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Your full name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="your@email.com" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Skills (comma-separated)</label>
                            <textarea class="form-control" name="skills" placeholder="Enter your skills separated by commas"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn minecraft-btn">Create Worker Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Client Registration Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title pixel-font">
                        <i class="fas fa-briefcase me-2"></i>Client Access
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="auth.php">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="register">
                        <input type="hidden" name="role" value="client">
                        
                        <div class="mb-3">
                            <label class="form-label">Client Access Key</label>
                            <input type="text" class="form-control" name="access_key" placeholder="Enter your client key" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name" placeholder="Your company name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" class="form-control" name="email" placeholder="contact@company.com" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn minecraft-btn">Access Client Dashboard</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Admin Key Detection Script -->
    <script>
        let keyBuffer = '';
        const adminKey = '<?php echo ADMIN_KEY; ?>';
        
        document.addEventListener('keydown', function(e) {
            if (e.key.length === 1) {
                keyBuffer = (keyBuffer + e.key).slice(-adminKey.length);
                if (keyBuffer === adminKey) {
                    window.location.href = 'auth.php?admin_access=1';
                    keyBuffer = '';
                }
            }
        });
        
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>