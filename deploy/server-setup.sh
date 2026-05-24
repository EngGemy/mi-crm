#!/usr/bin/env bash
# ==============================================================
# server-setup.sh — One-time server initialisation for cPanel
#
# Run once via SSH after the first git push:
#   ssh -p 22 sanadnat1@65.181.116.2 'bash -s' < deploy/server-setup.sh
#
# Prerequisites:
#   - The first rsync (from GitHub Actions) has already populated
#     $DEPLOY_PATH with the application code.
#   - PHP 8.3 is available at the path below.
# ==============================================================

set -euo pipefail

# ----------------------------------------------------------
# Configuration — adjust if cPanel uses a versioned php path
# e.g. /opt/cpanel/ea-php83/root/usr/bin/php
# ----------------------------------------------------------
PHP="${PHP_BIN:-/usr/local/bin/php}"
DEPLOY_PATH="${DEPLOY_PATH:-/home/sanadnat1/laravel_app}"

echo "==> Deployment path : $DEPLOY_PATH"
echo "==> PHP binary       : $PHP ($($PHP -r 'echo PHP_VERSION;'))"
echo ""

cd "$DEPLOY_PATH"

# ----------------------------------------------------------
# 1. Create .env from example if it doesn't exist
# ----------------------------------------------------------
if [ ! -f .env ]; then
  cp .env.example .env
  echo "==> .env created from .env.example"
  echo ""
  echo "  *** ACTION REQUIRED ***"
  echo "  Edit $DEPLOY_PATH/.env and set:"
  echo "    APP_ENV=production"
  echo "    APP_DEBUG=false"
  echo "    APP_URL=https://yourdomain.com"
  echo "    DB_HOST=localhost"
  echo "    DB_DATABASE=<cpanel_db_name>"
  echo "    DB_USERNAME=<cpanel_db_user>"
  echo "    DB_PASSWORD=<cpanel_db_password>"
  echo "    MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD"
  echo ""
  echo "  After editing, re-run this script or run the"
  echo "  artisan commands manually."
  echo ""
  read -rp "  Press ENTER once .env is configured, or Ctrl+C to abort..." _
fi

# ----------------------------------------------------------
# 2. Ensure storage & bootstrap/cache directories are writable
# ----------------------------------------------------------
echo "==> Setting permissions..."
chmod -R 775 storage bootstrap/cache

# Create runtime directories if missing
mkdir -p storage/logs \
         storage/framework/{cache/data,sessions,views} \
         storage/app/{public,livewire-tmp,mpdf,temp,quotations,lead-imports}

# ----------------------------------------------------------
# 3. Generate application key (idempotent — skips if key set)
# ----------------------------------------------------------
echo "==> Generating application key..."
$PHP artisan key:generate --no-interaction

# ----------------------------------------------------------
# 4. Run migrations + seeders
# ----------------------------------------------------------
echo "==> Running migrations..."
$PHP artisan migrate --seed --force

# ----------------------------------------------------------
# 5. Create storage symlink
# ----------------------------------------------------------
echo "==> Creating storage symlink..."
$PHP artisan storage:link || echo "  (symlink may already exist — OK)"

# ----------------------------------------------------------
# 6. Cache configuration for production
# ----------------------------------------------------------
echo "==> Caching config / routes / views..."
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo ""
echo "==> Server setup complete!"
echo "    Next: configure the domain docroot in cPanel (see DEPLOYMENT.md)."
