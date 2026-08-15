#!/bin/sh
set -e

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "Pulling latest changes..."
git -C "$ROOT_DIR" pull --rebase

echo "Installing PHP dependencies..."
composer install --working-dir="$ROOT_DIR" \
  --no-interaction --prefer-dist --optimize-autoloader --no-dev

echo "Installing Node dependencies..."
npm ci --prefix "$ROOT_DIR"

echo "Building assets..."
npm run build --prefix "$ROOT_DIR"

echo "Running migrations..."
php "$ROOT_DIR/artisan" migrate --force

echo "Caching config / routes / views..."
php "$ROOT_DIR/artisan" config:cache
php "$ROOT_DIR/artisan" route:cache
php "$ROOT_DIR/artisan" view:cache

echo "Reloading php-fpm..."
sudo systemctl reload php8.3-fpm

echo "Restarting Horizon..."
sudo systemctl restart horizon-lms

echo "Verifying Horizon is running..."
if ! sudo systemctl is-active --quiet horizon-lms; then
  echo "ERROR: horizon-lms service is not running."
  sudo systemctl status horizon-lms --no-pager
  exit 1
fi
php "$ROOT_DIR/artisan" horizon:status

echo "Done."
