# P2PFIY - PHP Version

## Overview

This is the complete PHP/MySQL version of P2PFIY, a Minecraft-themed micro-task freelancing platform. The platform connects clients who need small tasks completed with workers who can perform them, featuring a cyberpunk-styled dark theme with purple accents.

## Features

### Core Functionality
- **Three User Roles**: Admin, Client, and Worker with separate dashboards
- **Key-Based Authentication**: No email/password - uses unique access keys
- **Task Management**: Create, browse, and apply to micro-tasks
- **Wallet System**: Virtual wallet with $3.00 minimum withdrawal
- **Payment Processing**: Manual payment via JazzCash, Easypaisa, Paytm, and USDT
- **Admin Review System**: Admin approval/rejection of task submissions

### Design Features
- **Minecraft Theme**: Pixel-perfect styling with cyberpunk purple accents
- **Bootstrap 5 Dark**: Responsive design with modern dark theme
- **Real-time Updates**: Dynamic content loading and filtering
- **Mobile Responsive**: Works seamlessly on all device sizes

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Database Setup**
   ```bash
   # Import the database schema
   mysql -u root -p < database.sql
   ```

2. **Configuration**
   - Edit `config.php` with your database credentials
   - Update database connection settings:
     ```php
     define('DB_HOST', 'your_host');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'p2pfiy_db');
     ```

3. **Web Server**
   - Point your web server document root to the project directory
   - Ensure PHP sessions are enabled
   - Enable URL rewriting if needed

### Default Access

- **Admin Access**: Type `nafisabat103@FR` on any page to access admin panel
- **Sample Data**: Database includes sample tasks and admin user

## File Structure

```
php-version/
├── config.php              # Database & configuration
├── database.sql             # MySQL schema & sample data
├── index.php               # Homepage with task listings
├── auth.php                # Authentication handler
├── logout.php              # Session cleanup
├── submit-task.php         # Task submission handler
├── admin-dashboard.php     # Admin management panel
├── client-dashboard.php    # Client task management
├── worker-dashboard.php    # Worker earnings & submissions
├── includes/
│   ├── header.php          # Navigation & styles
│   └── footer.php          # Footer & modals
└── README.md               # This file
```

## User Workflows

### Worker Journey
1. Visit homepage and browse available tasks
2. Click "Join as Worker" and enter access key
3. Apply to tasks by submitting proof of work
4. Earn money when submissions are approved
5. Request withdrawals (minimum $3.00)

### Client Journey
1. Click "Post Tasks" and enter client access key
2. Create tasks with price, description, and requirements
3. Monitor submissions from workers
4. Admin reviews and approves/rejects submissions

### Admin Functions
1. Type admin key anywhere to access admin panel
2. Review and approve/reject worker submissions
3. Process withdrawal requests
4. Monitor platform statistics

## Technical Details

### Database Schema
- **users**: User accounts with roles and wallet balances
- **tasks**: Task listings with categories and pricing
- **submissions**: Worker proof submissions for tasks
- **withdrawals**: Payment requests with status tracking
- **transactions**: Financial transaction history

### Security Features
- SQL injection protection with prepared statements
- Session-based authentication
- Input validation and sanitization
- Hidden admin access (no visible login)

### Payment Methods
- **JazzCash**: Pakistani mobile payment
- **Easypaisa**: Pakistani digital wallet
- **Paytm**: Indian payment platform
- **USDT**: Cryptocurrency payments

## Configuration Options

Edit `config.php` to customize:

```php
define('MIN_WITHDRAWAL', 3.00);     # Minimum withdrawal amount
define('MIN_TASK_PRICE', 0.02);     # Minimum task price
define('ADMIN_KEY', 'your_key');    # Admin access key
```

## Styling & Theme

The application uses:
- **Bootstrap 5** for responsive layout
- **Font Awesome** for icons
- **Google Fonts** (Press Start 2P, Orbitron) for Minecraft styling
- **Custom CSS** for purple cyberpunk theme
- **CSS Variables** for easy color customization

## Development Notes

This PHP version maintains full feature parity with the React/Node.js version while using traditional server-side rendering. The codebase follows PHP best practices with:

- Object-oriented database connections
- Prepared statements for security
- Session management for authentication
- Modular file structure for maintainability

## Support

For issues or questions:
- Check database connection settings
- Verify PHP session configuration
- Ensure proper file permissions
- Review error logs for debugging

The platform is designed to be lightweight and easy to deploy on any standard LAMP/LEMP stack.