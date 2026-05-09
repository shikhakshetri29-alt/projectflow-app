# ProjectFlow — Mini Project Management App

A clean, modern PHP + MySQL project management application similar to a simplified Trello/Asana.

---

## Features

- **Authentication** — Register/Login with PHP sessions + bcrypt passwords
- **Role-Based Access** — Admin (full control) vs Member (view & update assigned tasks)
- **Projects** — Create projects, assign team members
- **Tasks** — Create tasks under projects, assign to members, track status
- **Dashboard** — Live stats: total/completed/pending tasks and project count

---

## Tech Stack

| Layer      | Technology                   |
|------------|------------------------------|
| Backend    | PHP 8.x (no frameworks)      |
| Database   | MySQL 8                      |
| Frontend   | Bootstrap 5.3 + Bootstrap Icons |
| Auth       | PHP Sessions + bcrypt         |

---

## Folder Structure

```
php-pmapp/
├── config/
│   ├── db.php          # Database connection
│   └── session.php     # Session helpers + auth guards
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── dashboard/
│   └── index.php       # Stats dashboard
├── projects/
│   ├── index.php       # All projects
│   ├── create.php      # Create project (admin)
│   ├── view.php        # Single project + tasks
│   └── members.php     # Manage project members (admin)
├── tasks/
│   ├── index.php       # All tasks with filters
│   ├── create.php      # Create task (admin)
│   └── update_status.php  # Toggle task status
├── includes/
│   └── navbar.php      # Shared sidebar navigation
├── assets/
│   └── css/style.css   # Custom styles
├── sql/
│   └── schema.sql      # Full database schema + seed data
└── index.php           # Entry point (redirects)
```

---

## Setup Instructions

### 1. Requirements

- PHP 8.0+ with `mysqli` extension
- MySQL 8.x
- A web server (Apache/Nginx) OR PHP built-in server

### 2. Database Setup

```bash
# Log into MySQL
mysql -u root -p

# Import the schema
SOURCE /path/to/php-pmapp/sql/schema.sql;
```

This creates the `pmapp` database and all tables. Three seed accounts are inserted automatically.

### 3. Configure Database Connection

Edit `config/db.php` and update if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // your MySQL password
define('DB_NAME', 'pmapp');
```

### 4. Run the Application

**Option A — PHP Built-in Server (quickest for development):**

```bash
cd /path/to/php-pmapp
php -S localhost:8080
# Open http://localhost:8080
```

**Option B — Apache (XAMPP/WAMP/LAMP):**

Place the `php-pmapp` folder inside `htdocs/` (XAMPP) or `www/` (WAMP).
Open `http://localhost/php-pmapp`

**Option C — Nginx** — Configure a vhost pointing to the folder.

---

## Demo Accounts

| Role   | Email             | Password   |
|--------|-------------------|------------|
| Admin  | admin@demo.com    | password   |
| Member | alice@demo.com    | password   |
| Member | bob@demo.com      | password   |

---

## Database Schema Overview

```
users              — id, name, email, password, role, created_at
projects           — id, title, description, created_by, created_at
project_members    — id, project_id, user_id, joined_at
tasks              — id, project_id, assigned_to, title, description, status, created_by, created_at
```

All foreign keys use `ON DELETE CASCADE` so deleting a project removes its tasks automatically.

---

## Role Permissions

| Feature                        | Admin | Member |
|--------------------------------|-------|--------|
| View dashboard                 | ✅    | ✅     |
| Create projects                | ✅    | ❌     |
| View all projects              | ✅    | own only |
| Manage project members         | ✅    | ❌     |
| Create tasks                   | ✅    | ❌     |
| Assign tasks                   | ✅    | ❌     |
| View all tasks                 | ✅    | assigned only |
| Update task status             | ✅    | assigned only |
| Register new users             | ✅    | ❌     |

---

## Security Notes

- Passwords hashed with `password_hash()` (bcrypt)
- All SQL queries use prepared statements (no SQL injection)
- `htmlspecialchars()` used on all output (no XSS)
- Role checks enforced server-side on every page
