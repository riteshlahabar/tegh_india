# MySQL Setup Guide - Tegh Cables CRM

This guide will help you set up the CRM with MySQL database instead of SQLite.

## Prerequisites

- MySQL 5.7+ or MariaDB 10.3+
- PHP 8.4+
- Composer

## Step-by-Step Setup

### 1. Create MySQL Database

Open MySQL command line or phpMyAdmin and run:

```sql
CREATE DATABASE tegh_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**For cPanel:**
- Go to MySQL Databases
- Create database: `tegh_crm`
- Create user (if needed) and assign to database with all privileges

### 2. Import Database Schema

**Method A: MySQL Command Line**

```bash
mysql -u root -p tegh_crm < database/mysql_schema.sql
```

Replace `root` with your MySQL username. You'll be prompted for password.

**Method B: phpMyAdmin (Recommended for cPanel)**

1. Select the `tegh_crm` database
2. Click on "Import" tab
3. Choose file: `database/mysql_schema.sql`
4. Click "Go" to import

**Method C: MySQL Workbench**

1. Open connection to your MySQL server
2. Select database `tegh_crm`
3. File → Run SQL Script
4. Select `database/mysql_schema.sql`

### 3. Update .env File

Your `.env` file has already been updated to use MySQL. Verify the settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tegh_crm
DB_USERNAME=root
DB_PASSWORD=
```

**Update these values based on your setup:**

- `DB_HOST`: Use `127.0.0.1` for local, `localhost` for cPanel
- `DB_DATABASE`: Your database name (default: `tegh_crm`)
- `DB_USERNAME`: Your MySQL username
- `DB_PASSWORD`: Your MySQL password

### 4. Test Database Connection

```bash
cd backend
php artisan db:show
```

This should display your database connection details without errors.

### 5. Seed Demo Data

```bash
php artisan db:seed
```

This will populate your database with demo data including:
- Admin user (username: `admin`, password: `1234`)
- 4 Telecaller users
- 28 Customer parties
- 150+ Invoices
- Call history, payment promises, etc.

### 6. Start Development Server

```bash
php artisan serve
```

Visit: http://127.0.0.1:8000

## Login Credentials

**Admin Account:**
- Username: `admin`
- Password: `1234`

**Telecaller Accounts:**
- Username: `rahul.sharma`, `priya.patel`, `amit.kumar`, `sneha.gupta`
- Password: `password`

## Verify Installation

After seeding, check if data was imported correctly:

```bash
php artisan tinker
```

Then run:
```php
\App\Models\User::count();    // Should be 5
\App\Models\Party::count();   // Should be 28
\App\Models\Invoice::count(); // Should be 150+
exit
```

## Tables Created

The schema creates 15 tables:

**Core Laravel Tables:**
- `users` - Admin and telecaller users
- `sessions` - User sessions
- `password_reset_tokens` - Password reset tokens
- `cache`, `cache_locks` - Cache management
- `jobs`, `job_batches`, `failed_jobs` - Queue management

**CRM Tables:**
- `parties` - Customer parties
- `invoices` - Invoice records
- `calls` - Call history
- `payment_promises` - Payment to pay (PTP) records
- `whatsapp_messages` - WhatsApp message logs
- `email_messages` - Email logs
- `app_settings` - Application settings

## Troubleshooting

### Connection Refused Error

```
SQLSTATE[HY000] [2002] Connection refused
```

**Solution:** Make sure MySQL is running
```bash
# macOS
brew services start mysql

# Linux
sudo systemctl start mysql

# Windows
net start MySQL
```

### Access Denied Error

```
SQLSTATE[HY000] [1045] Access denied for user
```

**Solution:** Check your username/password in `.env` file

### Unknown Database Error

```
SQLSTATE[HY000] [1049] Unknown database 'tegh_crm'
```

**Solution:** Create the database first (Step 1)

### Foreign Key Constraint Fails

If seeding fails with foreign key errors, drop all tables and re-import:

```sql
DROP DATABASE tegh_crm;
CREATE DATABASE tegh_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then re-import the schema file.

## Production Deployment (cPanel)

### 1. Upload Files

Upload all files to your cPanel hosting (usually `public_html` or a subdomain folder)

### 2. Configure Database

- Create database via cPanel → MySQL Databases
- Import `mysql_schema.sql` via phpMyAdmin
- Update `.env` with production database credentials

### 3. Configure .htaccess

The `.htaccess` file in `public/` is already configured for cPanel with PHP 8.4

### 4. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
```

### 5. Update Production .env

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_DATABASE=your_cpanel_db_name
DB_USERNAME=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password
```

### 6. Run Seeder (First Time Only)

```bash
php artisan db:seed
```

### 7. Configure Document Root

Point your domain's document root to the `public` folder.

## Additional Commands

**Clear cache:**
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Fresh database (WARNING: Deletes all data):**
```bash
# Re-import schema
mysql -u root -p tegh_crm < database/mysql_schema.sql

# Re-seed data
php artisan db:seed
```

## Support

For issues or questions, check:
- Laravel documentation: https://laravel.com/docs
- MySQL documentation: https://dev.mysql.com/doc/
