<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?> - Minecraft-themed freelancing platform">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Minecraft Style -->
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --minecraft-purple: #7c3aed;
            --minecraft-purple-light: #8b5cf6;
            --minecraft-purple-dark: #6d28d9;
            --minecraft-brown: #8b4513;
            --minecraft-tan: #deb887;
            --minecraft-green: #22c55e;
            --minecraft-gray: #6b7280;
            --pixel-font: 'Press Start 2P', cursive;
            --cyber-font: 'Orbitron', monospace;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            font-family: var(--cyber-font);
            color: #e2e8f0;
            min-height: 100vh;
        }

        .pixel-font {
            font-family: var(--pixel-font);
            font-size: 0.8rem;
        }

        .minecraft-card {
            background: rgba(30, 41, 59, 0.9);
            border: 2px solid var(--minecraft-purple);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(124, 58, 237, 0.3);
            backdrop-filter: blur(10px);
        }

        .minecraft-btn {
            background: linear-gradient(45deg, var(--minecraft-purple), var(--minecraft-purple-light));
            border: 2px solid var(--minecraft-purple-light);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(124, 58, 237, 0.4);
        }

        .minecraft-btn:hover {
            background: linear-gradient(45deg, var(--minecraft-purple-light), var(--minecraft-purple));
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(124, 58, 237, 0.6);
            color: white;
        }

        .navbar-brand {
            font-family: var(--pixel-font);
            color: var(--minecraft-purple-light) !important;
            font-size: 1.2rem;
        }

        .nav-link {
            color: #cbd5e1 !important;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--minecraft-purple-light) !important;
        }

        .task-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--minecraft-purple);
            border-radius: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .task-card:hover {
            border-color: var(--minecraft-purple-light);
            box-shadow: 0 8px 30px rgba(124, 58, 237, 0.3);
            transform: translateY(-3px);
        }

        .badge-category {
            background: linear-gradient(45deg, var(--minecraft-purple), var(--minecraft-purple-light));
            color: white;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }

        .price-tag {
            color: var(--minecraft-green);
            font-family: var(--pixel-font);
            font-size: 1.1rem;
            text-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
        }

        .hero-section {
            background: linear-gradient(135deg, rgba(124, 58, 237, 0.1), rgba(139, 92, 246, 0.1));
            border-bottom: 2px solid var(--minecraft-purple);
            padding: 4rem 0;
        }

        .stats-card {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--minecraft-purple);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            border-color: var(--minecraft-purple-light);
            box-shadow: 0 10px 40px rgba(124, 58, 237, 0.2);
        }

        .stats-number {
            font-family: var(--pixel-font);
            color: var(--minecraft-purple-light);
            font-size: 2rem;
            text-shadow: 0 0 15px rgba(139, 92, 246, 0.8);
        }

        .form-control, .form-select {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--minecraft-purple);
            color: #e2e8f0;
            backdrop-filter: blur(5px);
        }

        .form-control:focus, .form-select:focus {
            background: rgba(30, 41, 59, 0.9);
            border-color: var(--minecraft-purple-light);
            box-shadow: 0 0 15px rgba(124, 58, 237, 0.3);
            color: #e2e8f0;
        }

        .sidebar {
            background: rgba(15, 23, 42, 0.95);
            border-right: 2px solid var(--minecraft-purple);
            backdrop-filter: blur(10px);
        }

        .sidebar .nav-link {
            border-radius: 8px;
            margin: 0.25rem 0;
            padding: 0.75rem 1rem;
        }

        .sidebar .nav-link.active {
            background: linear-gradient(45deg, var(--minecraft-purple), var(--minecraft-purple-light));
            color: white !important;
        }

        .wallet-balance {
            background: linear-gradient(45deg, #059669, #10b981);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            font-family: var(--pixel-font);
            font-size: 1.5rem;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.8);
        }

        .table-dark {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(5px);
        }

        .table-dark th {
            border-color: var(--minecraft-purple);
            color: var(--minecraft-purple-light);
            font-weight: 700;
        }

        .table-dark td {
            border-color: rgba(124, 58, 237, 0.3);
        }

        .modal-content {
            background: rgba(15, 23, 42, 0.95);
            border: 2px solid var(--minecraft-purple);
            backdrop-filter: blur(15px);
        }

        .modal-header {
            border-bottom: 1px solid var(--minecraft-purple);
        }

        .footer {
            background: rgba(15, 23, 42, 0.95);
            border-top: 2px solid var(--minecraft-purple);
            backdrop-filter: blur(10px);
        }

        .alert {
            border: 1px solid;
            backdrop-filter: blur(5px);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border-color: var(--minecraft-green);
            color: var(--minecraft-green);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border-color: #ef4444;
            color: #fca5a5;
        }

        .spinner-border {
            color: var(--minecraft-purple-light);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-cube me-2"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Tasks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#support">Support</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <?php if (isWorker()): ?>
                            <li class="nav-item">
                                <span class="navbar-text me-3">
                                    <i class="fas fa-wallet me-1"></i>
                                    <span class="price-tag"><?php echo formatPrice($_SESSION['wallet_balance'] ?? 0); ?></span>
                                </span>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <?php if (isClient()): ?>
                                    <li><a class="dropdown-item" href="client-dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                <?php elseif (isWorker()): ?>
                                    <li><a class="dropdown-item" href="worker-dashboard.php">
                                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                    </a></li>
                                <?php elseif (isAdmin()): ?>
                                    <li><a class="dropdown-item" href="admin-dashboard.php">
                                        <i class="fas fa-shield-alt me-2"></i>Admin Panel
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item me-2">
                            <button class="btn minecraft-btn btn-sm" data-bs-toggle="modal" data-bs-target="#workerModal">
                                <i class="fas fa-user me-1"></i>Join as Worker
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="btn minecraft-btn btn-sm" data-bs-toggle="modal" data-bs-target="#clientModal">
                                <i class="fas fa-briefcase me-1"></i>Post Tasks
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>