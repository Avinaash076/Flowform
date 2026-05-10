# 🎯 FlowForm - Secure PHP Form Management System

A powerful, secure PHP MVC application for creating and managing dynamic forms with an intuitive drag-and-drop form builder, role-based access control, and enterprise-grade security.
username-admin@flowform.com
password-admin@123
**Live Demo:** [flowform.free.nf](http://flowform.free.nf)

---

## ✨ Key Features

- 🔐 **Enterprise Security**: CSRF protection, session management, SQL injection prevention, URL masking
- 🎨 **Visual Form Builder**: Drag-and-drop interface to create complex forms without coding
- 👥 **Role-Based Access**: Admin, Manager, and Employee roles with granular permissions
- 📊 **Form Submissions**: Track, filter, and analyze submitted form data
- 🔄 **Form Sequences**: Create multi-step workflows and conditional logic
- 📱 **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- 🚀 **MVC Architecture**: Clean separation of concerns with controllers, models, and views
- ⚡ **AJAX Integration**: Smooth user experience with real-time updates
- 🎭 **Session Management**: 30-minute timeout with IP and User-Agent verification
- 📦 **Composer Support**: Easy dependency management with PHP autoloader

---

## 🚀 Quick Start

### Prerequisites
- **PHP 8.0+** (Apache or Nginx with PHP-FPM)
- **Apache with mod_rewrite enabled**
- **MySQL/MariaDB** database
- **Composer** for dependency management
- **Git** for version control

### Local Installation

#### 1. Clone the Repository
```bash
git clone https://github.com/YOUR_USERNAME/flowform.git
cd flowform
```

#### 2. Install Dependencies
```bash
composer install
```

#### 3. Set Up Environment Variables
Create a `.env` file in the project root (see `.env.example`):
```php
DB_HOST=localhost
DB_USER=flowform_user
DB_PASSWORD=your_secure_password
DB_NAME=flowform_db
```

#### 4. Configure Database
```bash
# Create database
mysql -u root -p < database/schema.sql
```

#### 5. Set File Permissions
```bash
# Linux/Mac
chmod 755 storage/sessions
chmod 644 storage/sessions/*

# Windows - Run as Administrator
icacls "storage\sessions" /grant:r "%USERNAME%":F
```

#### 6. Configure Apache (Local Development)
Edit `httpd.conf` and set:
```apache
LoadModule rewrite_module modules/mod_rewrite.so

<Directory "C:/xampp/htdocs/flowform">
    AllowOverride All
</Directory>
```

#### 7. Access Application
```
http://localhost/flowform/login
```

**Default Login Credentials:**
- Username: `admin`
- Password: `password123`

---

## 📁 Project Structure

```
flowform/
├── .htaccess                  # URL rewriting rules
├── index.php                  # Single entry point (ONLY exposed file)
├── .env.example               # Environment variables template
├── composer.json              # Dependencies configuration
├── config/
│   ├── config.php             # Global constants & configuration
│   ├── db.php                 # Database connection
│   ├── session.php            # Session management
│   └── mail.php               # Email configuration
├── app/
│   ├── controllers/           # Request handlers
│   │   ├── AuthController.php
│   │   ├── AdminController.php
│   │   ├── FormController.php
│   │   └── ...
│   ├── models/                # Database models
│   └── views/                 # HTML templates
│       ├── auth/
│       ├── admin/
│       ├── employee/
│       └── layouts/
├── assets/
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript
│   └── cursors/               # Custom cursors
├── storage/
│   └── sessions/              # Session files
└── vendor/                    # Composer dependencies
```

---

## 🔒 Security Features

### URL Masking & Routing
All requests are routed through `index.php` - file paths are never exposed:
```
/flowform/login          → app/views/auth/login.php
/flowform/dashboard      → app/views/admin/dashboard.php
/flowform/forms          → app/views/admin/forms.php
```

### CSRF Protection
- Unique token generated per session
- Automatic validation on all form submissions
- Seamless AJAX integration with token headers

### Session Security
- 30-minute inactivity timeout
- IP address and User-Agent verification
- Automatic login redirect on expiry
- Session fixation attack prevention

### Direct Access Prevention
- `.htaccess` blocks direct access to `/app/` and `/config/`
- All PHP files return 403 Forbidden if accessed directly
- Only `index.php` is publicly accessible

### Additional Security Headers
```php
X-Frame-Options: SAMEORIGIN          # Clickjacking protection
X-Content-Type-Options: nosniff       # MIME sniffing prevention
X-XSS-Protection: 1; mode=block       # XSS attack protection
```

---

## 👤 User Roles & Permissions

### Admin
- Full system access
- Create, edit, delete forms
- Manage all form submissions
- User and employee management
- System configuration

### Manager
- Manage assigned forms
- View submission analytics
- Manage team employees
- Limited user management

### Employee
- Fill and submit forms
- View personal submissions
- Access assigned forms only

---

## 🛠️ API Endpoints

### Authentication
- `POST /api/login` - User login
- `GET /api/logout` - User logout
- `GET /api/user` - Get current user

### Forms
- `GET /api/forms` - List all forms
- `POST /api/forms` - Create new form
- `GET /api/forms/:id` - Get form details
- `PUT /api/forms/:id` - Update form
- `DELETE /api/forms/:id` - Delete form

### Submissions
- `GET /api/submissions` - List submissions
- `POST /api/submissions` - Submit form
- `GET /api/submissions/:id` - Get submission

---

## 🎯 Usage Examples

### Creating a Form Programmatically
```php
$formModel = new FormModel();
$formId = $formModel->create([
    'name' => 'Employee Feedback Form',
    'description' => 'Monthly feedback collection',
    'created_by' => 1,
    'is_active' => true
]);
```

### Adding Session Protection
```php
public function dashboard() {
    $this->checkAccess();  // ← Verify user is logged in
    
    // Your code here
    $data['title'] = 'Dashboard';
    return $this->view('admin/dashboard', $data);
}
```

### Verifying CSRF Token
```php
// Automatically handled in base controller
if (!$this->verifyCsrfToken()) {
    http_response_code(403);
    die('CSRF token invalid');
}
```

---

## 🌐 Environment Variables

Create a `.env` file in the root directory:
```php
# Database
DB_HOST=localhost
DB_USER=flowform_user
DB_PASSWORD=your_secure_password
DB_NAME=flowform_db

# Application
APP_NAME=FlowForm
APP_ENV=production
APP_DEBUG=false

# Email (Optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your_email@gmail.com
MAIL_PASSWORD=your_app_password

# Session
SESSION_TIMEOUT=1800
SESSION_PATH=/storage/sessions
```

---

## 📚 Documentation

- [SECURITY_GUIDE.php](SECURITY_GUIDE.php) - Complete security documentation
- [ANIMATION_IMPLEMENTATION_SUMMARY.md](ANIMATION_IMPLEMENTATION_SUMMARY.md) - UI animations guide
- [APACHE_SETUP.txt](APACHE_SETUP.txt) - Apache configuration instructions

---

## 🐛 Troubleshooting

### 404 Errors - URLs not routing
- Ensure Apache `mod_rewrite` is enabled
- Check `.htaccess` file exists in root
- Verify `AllowOverride All` in Apache config

### Database Connection Failed
- Check MySQL is running
- Verify `.env` database credentials
- Ensure database user has proper permissions

### Session Errors
- Check `storage/sessions/` folder exists and is writable
- Verify session timeout setting in `config/session.php`
- Clear browser cookies and try again

### Permission Denied Errors
- Run `chmod 755 storage/sessions` (Linux/Mac)
- Run as Administrator (Windows)
- Check file ownership with `ls -la` (Linux)

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 💬 Support

For issues, questions, or suggestions:
- Open an issue on GitHub
- Contact: support@flowform.free.nf
- Live Site: [flowform.free.nf](http://flowform.free.nf)

---

## 👨‍💻 Author

Created with ❤️ by the FlowForm Team

**Happy Form Building! 🎉**

For role-based access:
```php
$this->checkAdminAccess();      // Admin only
$this->checkEmployeeAccess();   // Employee only
```

## 🔐 Usage Examples

### Create Protected Page
```php
class ReportsController extends BaseController {
    public function view() {
        $this->checkAdminAccess();  // Must be admin
        
        $data = $this->getReports();
        $this->set('reports', $data);
        $this->render('reports');  // Auto finds app/views/reports/view.php
    }
}
```

### Form with CSRF Protection
```html
<form method="POST" action="<?php echo APP_URL; ?>/save">
    <input type="hidden" name="csrf_token" 
           value="<?php echo generateCsrfToken(); ?>">
    
    <input type="text" name="title" required>
    <button type="submit">Save</button>
</form>
```

### AJAX Request
```javascript
// Automatically includes CSRF token
ajaxRequest('/flowform/api?action=list', 'GET')
    .then(response => console.log(response))
    .catch(error => console.error(error));

// Show notification
showNotification('Form saved!', 'success');
```

### Flash Messages
```php
// In controller
setFlashMessage('Profile updated successfully!', 'success');
$this->redirect('/dashboard');

// In view (auto-displays)
<?php $msg = getFlashMessage(); ?>
```

## 🛣️ Route Mapping

| Route | Controller | Action |
|-------|-----------|--------|
| /login | AuthController | login |
| /logout | AuthController | logout |
| /dashboard | AdminController | dashboard |
| /employees | AdminController | employees |
| /forms | AdminController | forms |
| /create-form | FormController | create |
| /fill-form | EmployeeController | fillForm |
| /employee-dashboard | EmployeeController | dashboard |
| /api | FormController | api (AJAX only) |

## 🔑 Core Functions

### Session Functions
```php
registerSession($userId, $userRole)     // Login user
isLoggedIn()                             // Check login status
isSessionExpired()                       // Check timeout
getCurrentUserId()                       // Get user ID
getUserRole()                            // Get user role
logout()                                 // Logout user
```

### CSRF Functions
```php
generateCsrfToken()                      // Get/create token
verifyCsrfToken($token)                  // Verify token
```

### Message Functions
```php
setFlashMessage($msg, $type)             // Set one-time message
getFlashMessage()                        // Get and clear message
```

### Controller Functions
```php
$this->render($view, $data, $layout)     // Render view
$this->redirect($path)                   // Redirect
$this->jsonResponse($data)               // JSON response
$this->jsonError($message)               // JSON error
```

## 🌐 Browser URLs vs Actual Files

When you visit URLs in your browser, the actual execution flow is:

```
User visits: http://localhost/flowform/dashboard

↓

.htaccess rewrites to: http://localhost/flowform/index.php?route=dashboard

↓

index.php routes to: AdminController::dashboard()

↓

AdminController calls: $this->render('dashboard')

↓

Renders view: app/views/admin/dashboard.php

↓

Within layout: app/views/layouts/main.php

✓ User sees: http://localhost/flowform/dashboard (clean URL!)
```

## ⏱️ Session Timeout Flow

```
User logs in
    ↓
Session created: $_SESSION['last_activity'] = time()
    ↓
User active → $_SESSION['last_activity'] updated
    ↓
No activity for 30 minutes
    ↓
Next request: isSessionExpired() returns true
    ↓
Session destroyed
    ↓
Redirect to /login with message: "Session expired, please login again"
    ↓
After login → Redirect back to originally requested page
```

## 🔒 Security Checklist

- ✓ URL masking (no file extensions visible)
- ✓ No direct file access possible
- ✓ Session timeout with auto-redirect
- ✓ CSRF protection on all forms
- ✓ Session fixation prevention
- ✓ Session hijacking detection
- ✓ XSS protection
- ✓ MIME sniffing prevention
- ✓ Clickjacking prevention
- ✓ API AJAX-only enforcement

## 🚨 Important Notes

1. **Apache Configuration**
   - mod_rewrite MUST be enabled
   - AllowOverride MUST be set to All
   - Restart Apache after changes

2. **File Permissions**
   - Keep `/config/` files outside web root in production
   - Set appropriate permissions (644 for files, 755 for directories)

3. **Error Handling**
   - In production: Set `APP_DEBUG = false` in config.php
   - Errors won't be displayed to users

4. **HTTPS**
   - For production: Enable HTTPS
   - Add `Secure` flag to session cookies in session.php

5. **Database**
   - Use prepared statements for all queries
   - Never concatenate user input into SQL queries

## 📚 Documentation

See `SECURITY_GUIDE.php` for:
- Complete security implementation details
- Advanced configuration options
- Troubleshooting guide
- Best practices

## 🆘 Troubleshooting

**Getting 404 errors on clean URLs?**
- Ensure Apache mod_rewrite is enabled
- Verify AllowOverride is set to All
- Restart Apache

**Can I access /app/controllers/file.php directly?**
- No! .htaccess returns 403
- If you can, mod_rewrite isn't working (see above)

**Session expires randomly?**
- Check SESSION_TIMEOUT in config/session.php (default: 30 minutes)
- Verify Apache hasn't restarted (clears all sessions)

**CSRF token errors?**
- Ensure form includes: `<input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">`
- All POST/PUT/DELETE forms require token

## 📝 Demo Credentials

```
Admin Account:
  Email: admin@flowform.com
  Password: password123

Employee Account:
  Email: emp@flowform.com
  Password: password123
```

## 🤝 Support

For detailed implementation questions, refer to SECURITY_GUIDE.php or review controller examples in the codebase.

---

**FlowForm** - Secure Form Management System | Built with PHP 8+ | Apache | MVC Pattern
