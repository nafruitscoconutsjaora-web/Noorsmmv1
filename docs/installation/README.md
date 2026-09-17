# Installation & Deployment Guide

This guide covers the deployment of Apex SMM Panel on a standard Linux environment (Ubuntu 22.04/24.04 or Debian 11/12) with Nginx, PHP 8.1+, and MySQL/MariaDB.

---

## 1. System Requirements

- **PHP**: 8.1 or higher
- **Extensions**:
  - `pdo` and `pdo_mysql`
  - `curl`
  - `mbstring`
  - `bcmath` (critical for high-precision financial arithmetic)
  - `json`
- **Database**: MySQL 8.0+ or MariaDB 10.5+
- **Web Server**: Nginx or Apache with `mod_rewrite`

---

## 2. Quick Setup Steps

### Step 1: Clone Repository & Set Permissions
```bash
git clone https://github.com/your-org/apex-smm.git /var/www/apex-smm
cd /var/www/apex-smm

# Ensure storage and cache directories are writable by the web server
chmod -R 775 storage bootstrap
chown -R www-data:www-data storage bootstrap
```

### Step 2: Environment Configuration
Copy the example environment file and configure database and gateway credentials:
```bash
cp .env.example .env
nano .env
```

Ensure the following variables match your MySQL setup:
```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smm_panel
DB_USERNAME=smm_user
DB_PASSWORD=your_secure_password
```

### Step 3: Database Migration & Seeder
Import the database schema and starter data:
```bash
mysql -u smm_user -p smm_panel < database/schema.sql

# Seed initial admin and demo catalog
php -r '
require "vendor/autoload.php";
require "app/Helpers/helpers.php";
require "database/seeds/DatabaseSeeder.php";
$app = new App\Core\Application(__DIR__);
$db = App\Core\Database::getInstance()->getConnection();
Database\Seeds\DatabaseSeeder::run($db);
'
```

### Step 4: Web Server Configuration (Nginx)
Create an Nginx server block:

```nginx
server {
    listen 80;
    server_name smm.yourdomain.com;
    root /var/www/apex-smm/public;
    index index.php;

    charset utf-8;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable SSL via Let's Encrypt Certbot:
```bash
certbot --nginx -d smm.yourdomain.com
```

### Step 5: Background Cron Setup
```bash
(crontab -l 2>/dev/null; echo "* * * * * php /var/www/apex-smm/bin/cron.php all >> /var/www/apex-smm/storage/logs/cron.log 2>&1") | crontab -
```

---

## Default Credentials
- **Admin Portal**: `/admin/login`
  - **Username**: `admin`
  - **Password**: `admin123`
- **User Portal**: `/login`
  - **Username**: `demo`
  - **Password**: `demo123`
