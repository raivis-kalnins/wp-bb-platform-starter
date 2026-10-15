#!/usr/bin/env bash
set -euo pipefail

SELF_DIR="$(cd "$(dirname "$0")" && pwd)"
# Works when run from the project root OR from packages/wp-bb-platform/cpanel-setup.
if [ -f "$SELF_DIR/../../../composer.json" ]; then
  PROJECT="$(cd "$SELF_DIR/../../.." && pwd)"
elif [ -f "$SELF_DIR/composer.json" ]; then
  PROJECT="$SELF_DIR"
else
  echo "Could not find project composer.json."
  exit 2
fi
cd "$PROJECT"
SETUP_DIR="$PROJECT/packages/wp-bb-platform/cpanel-setup"

printf '\nWP BB Platform - reusable cPanel installer\nProject: %s\n\n' "$PROJECT"
PHP_BIN="${PHP_BIN:-php}"
"$PHP_BIN" -r 'if (version_compare(PHP_VERSION,"8.3","<")) {fwrite(STDERR,"PHP 8.3+ is required. Current: ".PHP_VERSION."\n"); exit(2);} echo "PHP ".PHP_VERSION." OK\n";'

if [ ! -f .env ]; then
  cp "$SETUP_DIR/.env.cpanel.example" .env
  chmod 600 .env || true
  echo "Created .env. Edit database/site values or use web/setup.php, then run again."
  exit 3
fi
if grep -q "CHANGE_ME\|https://example.com" .env; then
  echo "STOP: .env still contains placeholder values."
  exit 4
fi

COMPOSER=""
if command -v composer >/dev/null 2>&1; then
  COMPOSER="composer"
elif [ -f composer.phar ]; then
  COMPOSER="$PHP_BIN composer.phar"
else
  echo "Composer not found. Downloading verified Composer installer..."
  EXPECTED="$($PHP_BIN -r "copy('https://composer.github.io/installer.sig', 'php://stdout');")"
  "$PHP_BIN" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL="$($PHP_BIN -r "echo hash_file('sha384', 'composer-setup.php');")"
  if [ "$EXPECTED" != "$ACTUAL" ]; then rm -f composer-setup.php; echo "Composer installer signature mismatch."; exit 5; fi
  "$PHP_BIN" composer-setup.php --quiet --filename=composer.phar
  rm -f composer-setup.php
  COMPOSER="$PHP_BIN composer.phar"
fi

$COMPOSER install --no-dev --prefer-dist --optimize-autoloader --no-interaction
mkdir -p storage/framework/cache storage/framework/views storage/logs web/app/uploads web/app/updraft web/app/plugins
chmod -R u+rwX storage web/app/uploads web/app/updraft || true

if [ ! -f web/wp/wp-load.php ] || [ ! -f vendor/autoload.php ]; then
  echo "ERROR: Composer did not install WordPress/vendor correctly."
  exit 6
fi

# Install licensed ACF Pro when supplied privately.
ACF_ZIP="$SETUP_DIR/private/advanced-custom-fields-pro.zip"
if [ -f "$ACF_ZIP" ]; then
  echo "Installing supplied ACF Pro package..."
  rm -rf web/app/plugins/advanced-custom-fields-pro
  TMP_ACF="$(mktemp -d)"
  unzip -q "$ACF_ZIP" -d "$TMP_ACF"
  if [ -d "$TMP_ACF/advanced-custom-fields-pro" ]; then
    mv "$TMP_ACF/advanced-custom-fields-pro" web/app/plugins/
  else
    FIRST_DIR="$(find "$TMP_ACF" -mindepth 1 -maxdepth 1 -type d | head -n 1 || true)"
    if [ -n "$FIRST_DIR" ]; then mv "$FIRST_DIR" web/app/plugins/advanced-custom-fields-pro; fi
  fi
  rm -rf "$TMP_ACF"
fi

# Make Updraft database backup visible to the plugin without importing MySQL manually.
find "$SETUP_DIR/updraft-backup" -maxdepth 1 -type f \( -name '*-db.gz' -o -name '*-db.zip' \) -exec cp -f {} web/app/updraft/ \; 2>/dev/null || true

# Allow normal plugin/theme management in browser on cPanel while keeping file editor disabled.
APP_CONFIG="config/application.php"
if [ -f "$APP_CONFIG" ]; then
  if grep -q "DISALLOW_FILE_MODS" "$APP_CONFIG"; then
    sed -i.bak -E "s/Config::define\(['\"]DISALLOW_FILE_MODS['\"], *true\);/Config::define('DISALLOW_FILE_MODS', false);/" "$APP_CONFIG" || true
  fi
  if ! grep -q "DISALLOW_FILE_EDIT" "$APP_CONFIG"; then
    printf "\n// cPanel: allow installs/updates but keep PHP editor disabled.\nConfig::define('DISALLOW_FILE_EDIT', true);\n" >> "$APP_CONFIG"
  fi
fi

echo
echo "Platform files installed."
echo "Document root: $PROJECT/web"
echo "Next: open WordPress admin, activate ACF Pro if needed, open UpdraftPlus, Rescan local folder, restore DATABASE only."
