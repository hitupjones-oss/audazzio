#!/usr/bin/env bash
# Local WordPress for building and previewing the site: PHP's own server, SQLite (no MySQL needed),
# Elementor from wordpress.org, and this repository's theme and plugin linked in live.
#   scripts/wp/dev.sh            set up if needed, then serve http://localhost:3031
#   scripts/wp/dev.sh reset      a fresh database: install, activate, import the pages again
# Needs php 8.1+ (with pdo_sqlite, gd, zip), curl, unzip. Sign in at /wp-admin as rspkt / rspkt-local.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WP="$ROOT/.wp"
PORT="${AZ_PORT:-3031}"
URL="http://localhost:$PORT"
mkdir -p "$WP"
cd "$WP"

if [ ! -f wordpress/wp-load.php ]; then
  echo "downloading WordPress, SQLite integration and Elementor"
  curl -sSL -o wp.zip https://wordpress.org/latest.zip && unzip -q wp.zip && rm wp.zip
  (cd wordpress/wp-content/plugins \
    && curl -sSL -o s.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.latest-stable.zip && unzip -q s.zip && rm s.zip \
    && curl -sSL -o e.zip https://downloads.wordpress.org/plugin/elementor.latest-stable.zip && unzip -q e.zip && rm e.zip)
  curl -sSL -o wp-cli.phar https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi
wpcli() { php "$WP/wp-cli.phar" --allow-root --path="$WP/wordpress" "$@" 2>&1 | grep -v "^PHP Warning\|already defined" || true; }

if [ ! -f wordpress/wp-content/db.php ]; then
  sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$WP/wordpress/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
    wordpress/wp-content/plugins/sqlite-database-integration/db.copy > wordpress/wp-content/db.php
fi
if [ ! -f wordpress/wp-config.php ]; then
  sed "s/database_name_here/wp/; s/username_here/wp/; s/password_here/wp/; /define( 'WP_DEBUG', false );/d" wordpress/wp-config-sample.php > wordpress/wp-config.php
  sed -i "s#/\* Add any custom values between this line and the \"stop editing\" line. \*/#define('DB_DIR', '$WP/db/'); define('DB_FILE', 'audazzio.sqlite'); define('WP_DEBUG', true); define('WP_DEBUG_LOG', '$WP/debug.log'); define('WP_DEBUG_DISPLAY', false); define('DISABLE_WP_CRON', true); define('WP_ENVIRONMENT_TYPE', 'local'); define('AUTOMATIC_UPDATER_DISABLED', true);#" wordpress/wp-config.php
fi
mkdir -p db
ln -sfn "$ROOT/wp-content/themes/audazzio" wordpress/wp-content/themes/audazzio
ln -sfn "$ROOT/wp-content/plugins/audazzio-core" wordpress/wp-content/plugins/audazzio-core
cat > router.php <<'PHP'
<?php
// PHP's built-in server, routed like Apache with WordPress's pretty permalinks.
$root = __DIR__ . '/wordpress';
$path = urldecode( (string) parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
if ( '/' !== $path && is_file( $root . $path ) ) {
	return false;
}
if ( is_dir( $root . $path ) && is_file( rtrim( $root . $path, '/' ) . '/index.php' ) ) {
	$_SERVER['SCRIPT_NAME'] = rtrim( $path, '/' ) . '/index.php';
	chdir( $root . $path );
	require rtrim( $root . $path, '/' ) . '/index.php';
	return;
}
chdir( $root );
$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
PHP

if [ "${1:-}" = "reset" ] || [ ! -f db/audazzio.sqlite ]; then
  rm -f db/audazzio.sqlite
  wpcli core install --url="$URL" --title=Audazzio --admin_user=rspkt --admin_password=rspkt-local --admin_email=dev@rspkt.co --skip-email
  wpcli option update home "$URL"
  wpcli option update siteurl "$URL"
  wpcli plugin activate sqlite-database-integration elementor audazzio-core
  wpcli theme activate audazzio
  wpcli rewrite structure '/%postname%/'
  wpcli eval 'print_r( az_run_seed() );'
  wpcli rewrite flush
fi

if ! curl -s -o /dev/null "$URL/"; then
  echo "serving $URL"
  PHP_CLI_SERVER_WORKERS=4 nohup php -S "localhost:$PORT" -t "$WP/wordpress" "$WP/router.php" > "$WP/server.log" 2>&1 &
  sleep 1
fi
echo "ready: $URL  (admin: $URL/wp-admin  rspkt / rspkt-local)"
