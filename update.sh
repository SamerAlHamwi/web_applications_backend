#!/bin/bash

# ========= Project Variables =========
PROJECT_DIR="/var/www/web_application_admin"
GIT_REPO="https://github.com/SamerAlHamwi/web_applications_backend"
GIT_BRANCH="main"

echo "🔄 Starting update for web_application_admin..."

# Clone if project does not exist
if [ ! -d "$PROJECT_DIR/.git" ]; then
  echo "📁 Project directory not found. Cloning..."
  git clone $GIT_REPO $PROJECT_DIR || { echo "❌ Failed to clone."; exit 1; }
fi

cd $PROJECT_DIR || { echo "❌ Cannot access $PROJECT_DIR"; exit 1; }

# Protect .env from being overwritten
git update-index --assume-unchanged .env

# Ensure remote URL is correct
git remote set-url origin $GIT_REPO

echo "📥 Fetching updates..."
git fetch origin
git reset --hard origin/$GIT_BRANCH
git clean -fd

# Install composer dependencies
echo "📦 Installing dependencies..."
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --optimize-autoloader

# Laravel commands
echo "🔧 Running Laravel optimizations..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run DB migrations
echo "🔄 Running migrations..."
php artisan migrate --force || { echo "❌ Migration failed."; exit 1; }

echo "✅ Update completed successfully for web_application_admin!"
