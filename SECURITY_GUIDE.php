/**
 * =========================================================================
 * FLOWFORM - SECURE PHP MVC IMPLEMENTATION GUIDE
 * =========================================================================
 * 
 * Complete URL masking, session handling, and security implementation
 * for a modern PHP MVC application.
 * 
 * Base URL: http://localhost/flowform
 * Entry Point: index.php (ONLY exposed file in app folder)
 * =========================================================================
 */

// =========================================================================
// 1. URL MASKING - How it Works
// =========================================================================

VISIBLE URL                  →  ACTUAL FILE              CONTROLLER ACTION
/flowform/login              →  index.php route          AuthController::login()
/flowform/dashboard          →  index.php route          AdminController::dashboard()
/flowform/employees          →  index.php route          AdminController::employees()
/flowform/forms              →  index.php route          AdminController::forms()
/flowform/create-form        →  index.php route          FormController::create()
/flowform/fill-form          →  index.php route          EmployeeController::fillForm()
/flowform/api?action=list    →  index.php route (AJAX)   FormController::api()

MECHANISM:
  → .htaccess rewrites all requests to index.php?route=...
  → index.php extracts the route and maps to controller/action
  → .htaccess blocks direct access to /app/ and /config/ folders
  → All PHP files in /app/ return 403 if accessed directly

// =========================================================================
// 2. SESSION TIMEOUT & REDIRECT LOGIC
// =========================================================================

SESSION TIMEOUT: 30 minutes of inactivity

FLOW:
  1. User visits /flowform/employees (not logged in)
  2. index.php checks: isLoggedIn() → isSessionExpired()
  3. If expired/not logged in:
     → Store intended URL in $_SESSION['redirect_after_login']
     → Redirect to /flowform/login with message
  4. User logs in successfully
  5. AuthController calls registerSession($userId, 'admin')
  6. index.php redirects to original page
  
MESSAGE SHOWN:
  "Session expired, please login again"

// =========================================================================
// 3. ADDING SESSION CHECK TO ANY PAGE (ONE LINE!)
// =========================================================================

In your controller action, add this single line at the start:

    public function dashboard() {
        $this->checkAccess();  // ← ONE LINE HANDLES EVERYTHING
        
        // Your code here - user is guaranteed to be logged in
        $data = $this->getData();
    }

For admin-only pages:
    $this->checkAdminAccess();  // ← Checks role is 'admin'

For employee-only pages:
    $this->checkEmployeeAccess();  // ← Checks role is 'employee'

// =========================================================================
// 4. SECURITY FEATURES
// =========================================================================

✓ DIRECT FILE ACCESS BLOCKED
  - .htaccess in /app/ blocks all requests
  - .htaccess in /config/ blocks all requests
  - Only index.php is publicly accessible

✓ SESSION FIXATION PROTECTION
  - Session ID regenerated on login
  - IP address and User-Agent verified on each request
  - Session hijacking detected and logged out

✓ CSRF PROTECTION
  - Unique token generated per session
  - Token added to all forms
  - Token verified on form submission
  - Functions: generateCsrfToken(), verifyCsrfToken()

✓ XSS PROTECTION
  - All output HTML-escaped in views
  - X-XSS-Protection headers set
  - JavaScript escapeHtml() function available

✓ CLICKJACKING PROTECTION
  - X-Frame-Options: SAMEORIGIN header
  - Prevents page from being framed

✓ MIME TYPE SNIFFING PROTECTION
  - X-Content-Type-Options: nosniff header

✓ API SECURITY
  - All APIs require X-Requested-With: XMLHttpRequest header
  - Router detects and rejects direct API calls
  - CSRF tokens sent with all AJAX POST requests

// =========================================================================
// 5. USAGE EXAMPLES
// =========================================================================

EXAMPLE 1: Create Admin Page with Session Check
─────────────────────────────────────────────────

class AdminController extends BaseController {
    public function reports() {
        $this->checkAdminAccess();  // ← ONE LINE session check
        
        // Only admins get here
        $reports = $this->getReports();
        $this->set('reports', $reports);
        $this->render('reports');
    }
}

EXAMPLE 2: Form with CSRF Protection
─────────────────────────────────────

<form method="POST" action="<?php echo APP_URL; ?>/save-form">
    <!-- CSRF token automatically included -->
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    
    <div class="form-group">
        <label>Form Name</label>
        <input type="text" name="form_name" required>
    </div>
    
    <button type="submit">Save</button>
</form>

EXAMPLE 3: AJAX Request with CSRF
─────────────────────────────────

// Simple AJAX call - CSRF token automatically added
ajaxRequest('/flowform/api?action=list', 'GET')
    .then(response => {
        showNotification('Forms loaded', 'success');
    })
    .catch(error => {
        showNotification('Error loading forms', 'error');
    });

EXAMPLE 4: Show Flash Message
──────────────────────────────

// In controller
setFlashMessage('Form saved successfully!', 'success');
$this->redirect('/forms');

// In view (automatically displayed)
<?php 
$message = getFlashMessage();
if ($message) {
    echo '<div class="alert alert-' . $message['type'] . '">';
    echo htmlspecialchars($message['text']);
    echo '</div>';
}
?>

// =========================================================================
// 6. ROUTING TABLE (Auto-mapped in index.php)
// =========================================================================

Route                   Controller              Action
────────────────────────────────────────────────────────
/login                  AuthController         login
/logout                 AuthController         logout
/register               AuthController         register
/dashboard              AdminController        dashboard
/employees              AdminController        employees
/forms                  AdminController        forms
/create-form            FormController         create
/fill-form              EmployeeController     fillForm
/employee-dashboard     EmployeeController     dashboard
/api                    FormController         api (AJAX only)

// =========================================================================
// 7. PUBLIC vs PROTECTED ROUTES
// =========================================================================

PUBLIC ROUTES (no login required):
  - /login
  - /logout
  - /register

ALL OTHER ROUTES require login.

ROLE CHECKING:
  - checkAccess()          → Must be logged in
  - checkAdminAccess()     → Must be logged in + role = 'admin'
  - checkEmployeeAccess()  → Must be logged in + role = 'employee'

// =========================================================================
// 8. SESSION MANAGEMENT FUNCTIONS
// =========================================================================

registerSession($userId, $userRole)
  → Create new session for logged-in user
  → Usage: registerSession(123, 'admin');

isLoggedIn()
  → Check if user is logged in
  → Usage: if (isLoggedIn()) { ... }

isSessionExpired()
  → Check if session has expired
  → Returns true if expired (auto-destroys session)

getCurrentUserId()
  → Get current user ID
  → Usage: $userId = getCurrentUserId();

getUserRole()
  → Get current user role
  → Usage: if (getUserRole() === 'admin') { ... }

generateCsrfToken()
  → Generate or retrieve CSRF token
  → Usage: <input name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

verifyCsrfToken($token)
  → Verify CSRF token
  → Returns true if valid

setFlashMessage($message, $type)
  → Set one-time message (shown once, then cleared)
  → Types: 'info', 'success', 'warning', 'error'

getFlashMessage()
  → Get and clear flash message
  → Returns array with 'text' and 'type' keys

logout()
  → Destroy session and redirect to login

// =========================================================================
// 9. DIRECTORY STRUCTURE
// =========================================================================

flowform/
├── .htaccess              ← Main URL rewriting + security
├── index.php              ← Entry point (ONLY exposed PHP file)
│
├── app/
│   ├── .htaccess          ← Block direct access
│   ├── controllers/
│   │   ├── BaseController.php  ← Extends all controllers
│   │   ├── AuthController.php
│   │   ├── AdminController.php
│   │   ├── EmployeeController.php
│   │   ├── FormController.php
│   │   └── SequenceController.php
│   │
│   ├── models/
│   │   ├── UserModel.php
│   │   ├── FormModel.php
│   │   ├── SequenceModel.php
│   │   ├── FieldModel.php
│   │   └── SubmissionModel.php
│   │
│   └── views/
│       ├── layouts/
│       │   ├── main.php    ← Admin layout with nav
│       │   └── auth.php    ← Login layout
│       ├── auth/
│       │   └── login.php
│       ├── admin/
│       │   ├── dashboard.php
│       │   ├── employees.php
│       │   ├── forms.php
│       │   └── create-form.php
│       └── employee/
│           ├── dashboard.php
│           └── fill-form.php
│
├── config/
│   ├── .htaccess          ← Block direct access
│   ├── db.php             ← Database connection
│   ├── session.php        ← Session + timeout logic
│   └── config.php         ← App constants
│
└── assets/
    ├── css/
    │   └── style.css
    └── js/
        └── main.js

// =========================================================================
// 10. TROUBLESHOOTING
// =========================================================================

Q: Session expires but I haven't been inactive for 30 minutes?
A: Check config/session.php - SESSION_TIMEOUT constant. Verify Apache
   hasn't restarted (clears sessions). Check session garbage collection.

Q: .htaccess rules not working?
A: Ensure mod_rewrite is enabled in Apache
   Edit C:\xampp\apache\conf\httpd.conf
   Uncomment: LoadModule rewrite_module modules/mod_rewrite.so
   Restart Apache

Q: Getting 404 errors on clean URLs?
A: .htaccess not applied. Either:
   1. Check AllowOverride is set to All in httpd.conf
   2. Restart Apache
   3. Test by accessing /flowform/login directly

Q: CSRF token errors?
A: Ensure generateCsrfToken() is called before form display
   Verify form includes: <input type="hidden" name="csrf_token" ...>

Q: Can access /app/controllers/UserModel.php directly?
A: .htaccess not working. Check Apache mod_rewrite settings

// =========================================================================
// 11. ENVIRONMENT SETUP (XAMPP on Windows)
// =========================================================================

1. Place flowform/ folder in C:\xampp\htdocs\

2. Enable mod_rewrite in Apache:
   - Edit C:\xampp\apache\conf\httpd.conf
   - Find: #LoadModule rewrite_module modules/mod_rewrite.so
   - Remove the # (uncomment)
   - Save file

3. Set AllowOverride:
   - Find: <Directory \"C:/xampp/htdocs\">
   - Ensure AllowOverride All (not None)
   - Save file

4. Restart Apache:
   - XAMPP Control Panel → Stop Apache → Start Apache

5. Access application:
   - http://localhost/flowform/login

// =========================================================================
// 12. IMPORTANT SECURITY REMINDERS
// =========================================================================

✓ Always use htmlspecialchars() when displaying user input
✓ Always call checkAccess() at start of protected controller methods
✓ Always include CSRF token in forms
✓ Always verify CSRF token before processing form data
✓ Use parameterized queries (prepared statements) in database queries
✓ Never trust $_GET, $_POST, or $_COOKIE directly
✓ Keep session timeout reasonable (30 min is good default)
✓ Regenerate session ID after login (done in BaseController)
✓ Use HTTPS in production (set secure cookie flag)
✓ Never display sensitive errors to users (APP_DEBUG = false)

// =========================================================================
*/
