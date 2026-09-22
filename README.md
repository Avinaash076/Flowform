# FlowForm — PHP MVC Form Management System

A full-stack PHP application for managing dynamic forms with role-based access control, a drag-and-drop form builder, and a security-first architecture. It features AI-powered form generation and custom workflows.

---

## What it does

- **Visual Drag-and-Drop Form Builder**: Create multi-field forms without writing HTML.
- **AI Form Generation**: Generate complete form layouts, fields, and styling via DeepSeek AI simply by describing the form and uploading an optional style reference image.
- **Three-Tier RBAC**: Granular permission checks at the controller level for Admins, Managers, and Employees.
- **Multi-step Sequences**: Create form workflows with conditional logic (e.g., Requested By, Approved By).
- **Secure Architecture**: Session management with 30-minute inactivity timeout, IP + User-Agent verification, session fixation prevention, and full CSRF protection on all forms and AJAX requests.
- **Clean URL Routing**: Routing through a single entry point (`index.php`), avoiding exposed file paths via `.htaccess` redirection.

---

## Tech Stack

- **Backend:** PHP 8.0+, custom MVC architecture
- **Database:** MySQL with PDO prepared statements
- **Frontend:** HTML/CSS/JavaScript with AJAX
- **AI Integration:** Puter.js (DeepSeek Chat)
- **Server:** Apache with `mod_rewrite`, `.htaccess` access control

---

## Project Structure

```
flowform/
├── index.php                  # Single entry point
├── .htaccess                  # URL rewriting + access control
├── composer.json              # Dependency management (autoloading)
├── config/
│   ├── config.php             # Global constants
│   ├── db.php                 # Database connection
│   └── session.php            # Session configuration
├── app/
│   ├── controllers/           # Application controllers
│   ├── models/                # Database models
│   └── views/                 # View templates
├── assets/
│   ├── css/
│   └── js/                    # Core JS and AI integration (ai-builder.js)
└── storage/
    └── sessions/              # File-based session storage
```

---

## Redirection & Security (`.htaccess`)

All incoming requests are handled by `.htaccess` which routes them through `index.php`. This ensures:

1.  **Clean URLs**: Requests like `/dashboard` are rewritten to `index.php?route=dashboard`.
2.  **Protected Directories**: Direct access to `/app`, `/config`, and `/vendor` returns a 403 Forbidden.
3.  **Security Headers**: Adds standard security headers such as `X-Frame-Options` and `X-Content-Type-Options`.

### URL Routing Map

| URL | Controller | Method | Role |
|-----|-----------|--------|------|
| /login | AuthController | login | Public |
| /dashboard | AdminController | dashboard | Admin/Manager |
| /forms/builder | FormController | builder | Admin |
| /employee-dashboard | EmployeeController | dashboard | Employee |
| /api/* | Various | API Endpoints | Varies (AJAX only) |

---

## Setup & Deployment Instructions

### Prerequisites

- PHP 8.0 or higher
- MySQL Database
- Apache web server with `mod_rewrite` enabled and `AllowOverride All` configured.

### Local Development

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/Avinaash076/Flowform.git
    cd Flowform
    ```
2.  **Install dependencies**:
    ```bash
    composer install
    ```
3.  **Configuration**:
    Copy the example `.env` file or configure your database settings directly in `config/config.php` (if using constants).
4.  **Database Setup**:
    Import the database schema into your MySQL instance (e.g., `mysql -u root -p flowform < database/schema.sql`).
5.  **Access the application**:
    Navigate to `http://localhost/flowform` (or your configured virtual host).

### Production Deployment

When deploying to a shared host (like InfinityFree), simply upload the contents of the repository to your `htdocs` or `public_html` directory. The `.htaccess` file handles URL routing. Ensure the host allows `.htaccess` overrides.
