## Starter Guide (Windows, Symfony 7)

This guide documents everything we set up, including issues encountered and the exact commands used to fix them. All commands assume PowerShell on Windows.

### 1) Prerequisites

- PHP 8.2+ (with `pdo_mysql`, `pdo_sqlite`), Composer, Git
- Symfony CLI (downloaded `symfony.exe`)

Check versions:

```powershell
php -v
composer -V
git --version
```

Optional: add Symfony CLI folder to PATH so you can call `symfony` directly:

```powershell
setx PATH "$($env:Path);C:\Users\<you>\Downloads\symfony-cli_windows_amd64"
```

### 2) Create the project

From the folder containing `symfony.exe`:

```powershell
.# Show CLI
./symfony.exe version
./symfony.exe check:requirements

.# Create app
./symfony.exe new my_app --webapp
cd .\my_app
```

### 3) Serve the app locally

Initial attempt (detached) failed due to a locked log file:

```powershell
..\symfony.exe serve -d
```

Issue: “The process cannot access the file because it is being used by another process.” Fixed by stopping related processes, clearing logs, and starting again:

```powershell
Get-Process symfony,caddy,php -ErrorAction SilentlyContinue
Stop-Process -Name symfony,caddy,php -Force -ErrorAction SilentlyContinue
Remove-Item "$env:USERPROFILE\.symfony5\log\*.log" -Force -ErrorAction SilentlyContinue
..\symfony.exe serve -d
```

We then served in the foreground without TLS to see live output:

```powershell
..\symfony.exe serve --no-tls --port=8000
```

Open http://127.0.0.1:8000

### 4) Routing and Pages

Added controllers and pages:
- `HomeController` → `/`
- `DashboardController` → `/dashboard`
- Nav links in `templates/base.html.twig`

### 5) Security & Login

Configured `config/packages/security.yaml` to use a database user provider and form login with CSRF + logout. Also enabled login throttling safely:

- Early issue: after installing `symfony/rate-limiter`, cache clear failed with “security.listener.login_throttling.main depends on non-existent service 'login'”.
- Fix: replaced `login_throttling: { limiter: login }` with built-in throttle keys:

```yaml
security:
  firewalls:
    main:
      form_login:
        login_path: app_login
        check_path: app_login
        enable_csrf: true
      logout:
        path: app_logout
        target: app_home
      login_throttling:
        max_attempts: 5
        interval: '1 minute'
```

Added `SecurityController` and a simple login form template.

### 6) Domain Model & CRUD

Entities created:
- `User` (email identifier, roles JSON, hashed password)
- `Todo` (title, description, dueAt, isCompleted)
- `Category` (name unique) — many-to-many with `Todo`
- `Comment` (content, createdAt) — many-to-one to `Todo`

Repositories generated for each.

Forms:
- `TodoType`, `CategoryType`, `CommentType`

Controllers and views:
- `TodoCrudController` (`/todo`): list, create, edit, delete
- `CategoryCrudController` (`/category`): list, create, edit, delete
- `CommentController`: add/delete comments from the Todo edit page
- `TodoController` (`/todos/public`): public read-only listing

### 7) Database Setup and Migrations

We used both SQLite (for quick start) and then switched to MySQL. Final setup is MySQL.

SQLite (dev-only quick start):

```powershell
$env:DATABASE_URL = ('sqlite:///' + (Get-Location).Path.Replace('\\','/') + '/var/data.db')
php bin/console doctrine:database:create -n
php bin/console make:migration -n
php bin/console doctrine:migrations:migrate -n
```

Switched to MySQL (DB name: `sym`, user: `root`, password: empty):

```powershell
# Use MySQL 8 platform for proper SQL generation
$env:DATABASE_URL = 'mysql://root:@127.0.0.1:3306/sym?serverVersion=8.0.34&charset=utf8mb4'
php bin/console doctrine:database:create -n
php bin/console doctrine:migrations:migrate -n
```

Issue: Migration failed with MariaDB-specific SQL error.

Fix: Regenerate a fresh migration targeting MySQL, after dropping DB and removing the old migration:

```powershell
Remove-Item .\migrations\*.php -Force
php bin/console doctrine:database:drop --force -n
php bin/console doctrine:database:create -n
php bin/console make:migration -n
php bin/console doctrine:migrations:migrate -n
```

Persist MySQL configuration to `.env.local` (UTF-8 without BOM):

Issue: Writing `.env.local` with BOM caused Dotenv error “Loading files starting with a byte-order-mark (BOM) is not supported.”

Fix: Write without BOM using .NET APIs:

```powershell
$content = @'
DATABASE_URL="mysql://root:@127.0.0.1:3306/sym?serverVersion=8.0.34&charset=utf8mb4"
'@
[System.IO.File]::WriteAllText((Join-Path (Get-Location) '.env.local'), $content, (New-Object System.Text.UTF8Encoding($false)))
```

Verify connectivity:

```powershell
php bin/console doctrine:query:sql "SELECT 1"
```

### 8) Running, Stopping, Troubleshooting the Server

Start (foreground):

```powershell
..\symfony.exe serve --no-tls --port=8000
```

Start (detached):

```powershell
..\symfony.exe serve -d
```

Stop all local servers:

```powershell
..\symfony.exe server:stop --all
```

If logs are locked:

```powershell
Get-Process symfony,caddy,php -ErrorAction SilentlyContinue
Stop-Process -Name symfony,caddy,php -Force -ErrorAction SilentlyContinue
Remove-Item "$env:USERPROFILE\.symfony5\log\*.log" -Force -ErrorAction SilentlyContinue
```

### 9) Create a User

Hash a password:

```powershell
php bin/console security:hash-password
```

Insert into MySQL (example):

```sql
INSERT INTO users (email, roles, password)
VALUES ('you@example.com', '["ROLE_USER"]', '<paste hashed password>');
```

### 10) Useful URLs

- `/` — Home (public)
- `/login` — Login
- `/dashboard` — Dashboard (ROLE_USER)
- `/todo` — Manage todos (ROLE_USER)
- `/category` — Manage categories (ROLE_USER)
- `/todos/public` — Public readonly listing

### 11) Security Notes

- CSRF enabled on forms; logout and form_login configured
- Passwords are hashed using secure algorithms
- Login throttling: 5 attempts per minute
- For production: never use the Symfony local server; set `APP_ENV=prod`, `APP_DEBUG=0`, use HTTPS and secrets management

### 12) Common Maintenance Commands

```powershell
# Cache
php bin/console cache:clear

# Migrations
php bin/console make:migration -n
php bin/console doctrine:migrations:migrate -n

# DB debug
php bin/console doctrine:query:sql "SELECT 1"
```

---

This guide reflects the exact setup performed, the errors we hit, and the commands used to resolve them on Windows PowerShell.


