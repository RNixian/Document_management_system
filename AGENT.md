# AGENT.md - PTNI4 Document Management System

## Architecture
- **Framework**: Pure PHP with MySQL database
- **Structure**: Traditional PHP web application with separate admin/superadmin panels
- **Database**: MySQL (ptni4) with PDO, configured in `config/database.php`
- **Main directories**: `admin/`, `superadmin/`, `api/`, `includes/`, `assets/`, `uploads/`
- **Key files**: `index.php` (dashboard), `dashboard.php`, authentication via `login.php`

## No Build Tools
- No composer, npm, or build tools detected
- Direct PHP execution via XAMPP/Apache
- Static assets: CSS in `assets/css/`, JS in `assets/js/`

## Database Schema
- Core tables: `users`, `documents`, `categories`, `document_views`, `settings`
- Setup: Import `sql.sql` to create database structure
- Connection: `config/database.php` (localhost, root user, no password)

## Code Style
- **Classes**: Referenced but not found in standard location (Database, Document, User classes)
- **Security**: Uses `sanitize()` function, PDO prepared statements, session management
- **Functions**: Global functions in `includes/functions.php` (requireLogin, isAdmin, etc.)
- **Naming**: snake_case for files/functions, camelCase for JavaScript
- **Error handling**: Try-catch blocks, PDO error mode set to exception

## Testing
- No testing framework detected
- Manual testing through browser interface required
