# FlowForm — PHP MVC Form Management System

A full-stack PHP application I built to manage dynamic forms with role-based access control, a drag-and-drop form builder, and a security-first architecture. Built without a framework to deepen my understanding of how routing, session management, and MVC structure work under the hood.

**Live demo:** [flowform.free.nf](http://flowform.free.nf) — demo credentials available on request.

---

## What it does

- Visual drag-and-drop form builder — create multi-field forms without writing HTML
- Three-tier RBAC (Admin / Manager / Employee) with granular permission checks at the controller level
- Multi-step form sequences with conditional logic
- AJAX-powered submission tracking and analytics dashboard
- Session management with 30-minute inactivity timeout, IP + User-Agent verification, and session fixation prevention
- Full CSRF protection on all forms and AJAX requests
- Clean URL routing through a single entry point (`index.php`) — no file paths exposed in the browser

---

## Why I built it without a framework

I deliberately avoided Laravel or Symfony for this project because I wanted to implement the MVC pattern, custom router, session handling, and security layer myself. Understanding what frameworks abstract away made me a significantly better developer when I did start working with them.

---

## Tech stack

- **Backend:** PHP 8.0+, custom MVC architecture
- **Database:** MySQL with prepared statements throughout (no raw query concatenation)
- **Frontend:** HTML/CSS/JavaScript with AJAX for real-time updates
- **Server:** Apache with mod_rewrite, `.htaccess` access control
- **Auth:** Custom session-based authentication with role enforcement
- **Tooling:** Composer for autoloading, Git for version control

---

## Project structure

```
flowform/
├── index.php                  # Single entry point — only publicly accessible PHP file
├── .htaccess                  # URL rewriting + blocks direct access to /app/ and /config/
├── composer.json
├── config/
│   ├── config.php             # Global constants
│   ├── db.php                 # Database connection
│   └── session.php            # Session configuration
├── app/
│   ├── controllers/           # AuthController, AdminController, FormController, EmployeeController
│   ├── models/                # Database models
│   └── views/
│       ├── layouts/           # Shared layout templates
│       ├── auth/
│       ├── admin/
│       └── employee/
├── assets/
│   ├── css/
│   └── js/
└── storage/
    └── sessions/              # File-based session storage
```

---

## Security implementation

This was the most deliberate part of the build. The checklist below is fully implemented, not aspirational:

- All requests route through `index.php` — direct access to `/app/` or `/config/` returns 403
- CSRF token generated per session, validated on every POST/PUT/DELETE request and AJAX call
- Session fixation prevention on login (session ID regenerated)
- Session hijacking detection via IP address and User-Agent verification on every request
- 30-minute inactivity timeout with redirect back to the originally requested URL after re-login
- SQL injection prevention via PDO prepared statements on every query — zero raw interpolation
- Security headers set on every response: `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`
- `APP_DEBUG` flag in config — errors never surface to the user in production mode

The trickiest part was making CSRF work seamlessly with AJAX. The solution was attaching the token to a meta tag and reading it from a shared JS helper on every AJAX request, rather than embedding it per-form — which meant one consistent implementation path instead of manually adding it to every form and fetch call.

---

## URL routing

All browser URLs are clean — the actual file being executed is never visible:

```
GET /dashboard
  → .htaccess rewrites to index.php?route=dashboard
  → Router dispatches to AdminController::dashboard()
  → Renders app/views/admin/dashboard.php within layouts/main.php
```

Route map:

| URL | Controller | Method |
|-----|-----------|--------|
| /login | AuthController | login |
| /logout | AuthController | logout |
| /dashboard | AdminController | dashboard |
| /employees | AdminController | employees |
| /forms | AdminController | forms |
| /create-form | FormController | create |
| /fill-form | EmployeeController | fillForm |
| /employee-dashboard | EmployeeController | dashboard |
| /api | FormController | api (AJAX only) |

---

## Role-based access

Three roles with permission checks enforced at the controller layer, not just the view layer:

**Admin** — full system access, user management, form creation and deletion, submission analytics

**Manager** — assigned forms only, submission analytics for their team, limited user management

**Employee** — fill and submit assigned forms, view their own submission history

```php
// Enforced at the start of every controller method that requires a role
$this->checkAdminAccess();    // Redirects if not admin
$this->checkEmployeeAccess(); // Redirects if not employee
```

---

## Core session flow

```
Login
 → Session created, last_activity = time(), ID regenerated (fixation prevention)
 → Every request: IP + User-Agent verified, last_activity checked
 → 30 min inactivity: session destroyed, redirect to /login
 → After login: redirect back to originally requested URL
```

---

## REST API endpoints

```
POST   /api/login           Auth
GET    /api/logout          Auth
GET    /api/user            Current user info

GET    /api/forms           List forms
POST   /api/forms           Create form
GET    /api/forms/:id       Get form
PUT    /api/forms/:id       Update form
DELETE /api/forms/:id       Delete form

GET    /api/submissions     List submissions
POST   /api/submissions     Submit form
GET    /api/submissions/:id Get submission
```

All API routes are AJAX-only — direct browser access returns an error response.

---

## Local setup

```bash
git clone https://github.com/Avinaash076/Flowform.git
cd Flowform
composer install
cp .env.example .env
# Edit .env with your DB credentials
mysql -u root -p < database/schema.sql
```

Apache requirements: `mod_rewrite` enabled, `AllowOverride All` set for the project directory.

```
http://localhost/flowform/login
```

---

## What I'd build differently now

The custom router works but it's brittle — adding a new route means editing a central switch statement rather than declaring it declaratively. If I rebuilt this today I'd either use a micro-router package via Composer or migrate to Laravel, where route grouping and middleware registration are much cleaner. The permission system also lives in controller methods rather than a dedicated middleware layer, which creates some repetition I'd refactor out.

---

## Related projects

- **oAuth-RBAC** — same RBAC concepts implemented inside Laravel using Eloquent, Blade, and Laravel middleware rather than custom PHP
