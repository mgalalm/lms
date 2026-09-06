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

echo "Migrating legacy parent/guardian contacts..."
php "$ROOT_DIR/artisan" parent-contacts:migrate-legacy

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

for attempt in 1 2 3 4 5 6; do
  if php "$ROOT_DIR/artisan" horizon:status 2>&1 | grep -q "Horizon is running"; then
    php "$ROOT_DIR/artisan" horizon:status
    break
  fi

  if [ "$attempt" -eq 6 ]; then
    echo "ERROR: horizon-lms is active, but Horizon did not report as running."
    sudo systemctl status horizon-lms --no-pager
    php "$ROOT_DIR/artisan" horizon:status
    exit 1
  fi

  sleep 5
done

echo "Done."
