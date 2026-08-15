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

echo "Terminating Horizon so the process manager restarts it..."
php "$ROOT_DIR/artisan" horizon:terminate

echo "Verifying Horizon is running..."
sleep 5
if ! php "$ROOT_DIR/artisan" horizon:status | grep -q "Horizon is running"; then
  echo "ERROR: Horizon is not running. Configure Supervisor/systemd to run: php $ROOT_DIR/artisan horizon"
  exit 1
fi

echo "Done."
