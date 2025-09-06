#!/bin/bash

# MySQL Setup Script for Migration from SQLite
echo "=== MySQL Database Setup Script ==="
echo "This script will help you set up MySQL and import your data"
echo ""

# Check if MySQL is installed
if ! command -v mysql &> /dev/null; then
    echo "MySQL is not installed. Please install MySQL first."
    exit 1
fi

echo "Please enter MySQL root password when prompted..."
echo ""

# Variables - update these as needed
DB_NAME="sweat93_db"
DB_USER="sweat93_user"
DB_PASS="SweatGym2025#Secure"  # Change this password!

# Create database and user
mysql -u root -p <<EOF
-- Create database
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user and grant privileges
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;

-- Show created database
SHOW DATABASES LIKE '${DB_NAME}';
SELECT User, Host FROM mysql.user WHERE User = '${DB_USER}';
EOF

echo ""
echo "=== Database created successfully ==="
echo "Database: ${DB_NAME}"
echo "User: ${DB_USER}"
echo ""
echo "Next steps:"
echo "1. Update .env file with MySQL credentials:"
echo "   DB_CONNECTION=mysql"
echo "   DB_HOST=127.0.0.1"
echo "   DB_PORT=3306"
echo "   DB_DATABASE=${DB_NAME}"
echo "   DB_USERNAME=${DB_USER}"
echo "   DB_PASSWORD=${DB_PASS}"
echo ""
echo "2. Run migrations: php artisan migrate"
echo "3. Import data: mysql -u ${DB_USER} -p ${DB_NAME} < database/mysql_data_export.sql"