# PixelTask - Professional Freelance Marketplace

A comprehensive PHP-based freelance marketplace platform with advanced payment management capabilities. This platform connects clients who need tasks completed with skilled freelancers worldwide.

## 🚀 Features

### Multi-Role System
- **Admin Panel**: Complete payment management, transaction monitoring, dispute resolution
- **Client Dashboard**: Post tasks, manage projects, track spending
- **Worker Dashboard**: Apply to tasks, track earnings, request payouts

### Payment Management
- **Secure Transactions**: Escrow-based payment system
- **Commission System**: Configurable platform fees (default 5%)
- **Payout Processing**: Multiple payment methods (Bank, JazzCash, EasyPaisa, Paytm, USDT)
- **Dispute Resolution**: Built-in dispute handling system

### Advanced Admin Features
- **Real-time Analytics**: Revenue tracking, user metrics, transaction monitoring
- **Payout Queue**: Batch processing of worker payments
- **Platform Settings**: Configurable commission rates and fees
- **User Management**: Monitor user activity and verification

## 🛠️ Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Database Setup**
   ```bash
   mysql -u root -p < database.sql
   ```

2. **Configuration**
   - Edit `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'pixeltask_marketplace');
   ```

3. **File Permissions**
   ```bash
   chmod 755 php-marketplace/
   chmod 644 php-marketplace/*.php
   ```

4. **Web Server Setup**
   - Point document root to `php-marketplace/` directory
   - Ensure PHP sessions are enabled
   - Enable URL rewriting if needed

## 👤 Default Access

### Admin Access
- **Email**: mathfun103@gmail.com
- **Password**: aass1122@FRP@
- **Alternative**: Click "Admin" in footer and enter special key: `nafisabat103@FR`

### Sample Data
The database includes sample users, tasks, and transactions for testing.

## 📁 File Structure

```
php-marketplace/
├── config.php                 # Database & configuration
├── database.sql              # MySQL schema & sample data
├── index.php                 # Homepage with task listings
├── login.php                 # User authentication
├── register.php              # User registration
├── logout.php                # Session cleanup
├── admin-dashboard.php       # Admin management panel
├── client-dashboard.php      # Client project management
├── worker-dashboard.php      # Worker earnings & submissions
├── includes/
│   ├── header.php            # Navigation & styles
│   └── footer.php            # Footer & modals
└── README.md                 # This documentation
```

## 🎯 User Workflows

### For Freelancers (Workers)
1. **Registration**: Create account as freelancer
2. **Browse Tasks**: Search and filter available tasks
3. **Apply**: Submit proposals with portfolios
4. **Complete Work**: Deliver high-quality results
5. **Get Paid**: Automatic payments upon approval
6. **Withdraw**: Request payouts to preferred payment method

### For Clients
1. **Registration**: Create account as client
2. **Post Tasks**: Define requirements and budget
3. **Review Applications**: Choose best freelancers
4. **Monitor Progress**: Track task completion
5. **Approve Work**: Release payments for completed tasks

### For Administrators
1. **Monitor Platform**: Track revenue, users, transactions
2. **Process Payouts**: Approve worker payment requests
3. **Resolve Disputes**: Handle conflicts between parties
4. **Manage Settings**: Configure commission rates and fees

## 💳 Payment System

### Transaction Flow
1. **Client Posts Task**: Task created with specified budget
2. **Worker Applies**: Freelancer submits proposal
3. **Work Completion**: Worker delivers results
4. **Payment Processing**: Automatic calculation of fees
5. **Payout Distribution**: Worker receives payment minus platform fees

### Fee Structure
- **Platform Commission**: 5% of task value (configurable)
- **Processing Fee**: $0.30 per transaction (configurable)
- **Minimum Withdrawal**: $3.00 (configurable)

### Supported Payment Methods
- Bank Transfer (ACH)
- JazzCash (Pakistan)
- EasyPaisa (Pakistan)
- Paytm (India)
- USDT (Cryptocurrency)

## 🔧 Configuration Options

### Platform Settings (via Admin Panel)
```php
// Commission & Fees
COMMISSION_RATE = 5.0          // Platform commission percentage
PROCESSING_FEE = 0.30          // Fixed processing fee per transaction
MIN_WITHDRAWAL = 3.00          // Minimum payout amount
MIN_TASK_PRICE = 0.02          // Minimum task budget

// Payout Schedule
PAYOUT_SCHEDULE = 'weekly'     // daily, weekly, monthly
```

### Database Configuration
```php
// Database Connection
DB_HOST = 'localhost'
DB_USER = 'your_username'
DB_PASS = 'your_password'
DB_NAME = 'pixeltask_marketplace'

// Admin Credentials
ADMIN_EMAIL = 'mathfun103@gmail.com'
ADMIN_PASSWORD = 'aass1122@FRP@'
```

## 🎨 Design Features

### Modern UI/UX
- **Bootstrap 5**: Responsive design framework
- **Professional Theme**: Clean, modern interface
- **Dark Mode Support**: Coming soon
- **Mobile Responsive**: Works on all devices

### Key Components
- **Interactive Dashboards**: Real-time data visualization
- **Advanced Filtering**: Search and filter capabilities
- **Modal Dialogs**: Streamlined user interactions
- **Toast Notifications**: User feedback system

## 🔒 Security Features

### Data Protection
- **SQL Injection Prevention**: Prepared statements
- **Password Hashing**: bcrypt encryption
- **Session Security**: Secure session management
- **Input Validation**: Server-side validation

### Access Control
- **Role-Based Permissions**: Admin, Client, Worker roles
- **Authentication Required**: Protected routes
- **CSRF Protection**: Form security tokens
- **Data Sanitization**: XSS prevention

## 📊 Database Schema

### Core Tables
- **users**: User accounts with roles and balances
- **tasks**: Task listings with categories and pricing
- **transactions**: Payment records with commission tracking
- **disputes**: Conflict resolution system
- **payouts**: Worker payment requests
- **platform_settings**: Configurable system parameters

### Key Relationships
- Users can be clients, workers, or admins
- Tasks belong to clients and can have multiple applications
- Transactions link clients, workers, and tasks
- Disputes reference transactions for resolution

## 🚀 Development

### Adding New Features
1. **Database Changes**: Update `database.sql` schema
2. **Backend Logic**: Add functions to `config.php`
3. **Frontend Pages**: Create new PHP files
4. **Navigation**: Update `includes/header.php`

### Customization
- **Styling**: Modify CSS variables in `includes/header.php`
- **Business Logic**: Update functions in `config.php`
- **Email Templates**: Add email notification system
- **API Integration**: Connect external payment processors

## 📈 Analytics & Reporting

### Admin Analytics
- **Revenue Tracking**: Total earnings and commission
- **User Metrics**: Registration and activity stats
- **Transaction Monitoring**: Payment flow analysis
- **Performance Indicators**: Success rates and trends

### Export Capabilities
- **Transaction Reports**: CSV/Excel export
- **User Lists**: Filterable user data
- **Financial Summaries**: Accounting reports
- **Activity Logs**: Audit trail system

## 🆘 Troubleshooting

### Common Issues
1. **Database Connection**: Check credentials in `config.php`
2. **Session Problems**: Verify PHP session configuration
3. **Permission Errors**: Check file/folder permissions
4. **Missing Data**: Import sample data from `database.sql`

### Performance Optimization
- **Database Indexing**: Optimize query performance
- **Caching**: Implement Redis/Memcached
- **CDN Integration**: Serve static assets efficiently
- **Image Optimization**: Compress uploaded files

## 🔄 Updates & Maintenance

### Regular Tasks
- **Database Backup**: Schedule automated backups
- **Security Updates**: Keep PHP/MySQL updated
- **Log Monitoring**: Check error logs regularly
- **Performance Review**: Monitor system metrics

### Version Control
- **Git Integration**: Track code changes
- **Deployment Pipeline**: Automated deployments
- **Testing Environment**: Separate staging server
- **Rollback Procedures**: Quick recovery options

## 📞 Support

### Getting Help
- **Documentation**: Comprehensive guides available
- **Community**: Join developer discussions
- **Bug Reports**: Submit issues on GitHub
- **Feature Requests**: Suggest improvements

### Professional Services
- **Custom Development**: Tailored modifications
- **Migration Support**: Platform transitions
- **Performance Tuning**: Optimization services
- **Training Programs**: Team education

---

**PixelTask** - Empowering freelancers and clients with a secure, efficient marketplace platform.

*Built with ❤️ for the global freelance community*