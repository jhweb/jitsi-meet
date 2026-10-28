#!/bin/sh
# Ensure named-volume config/uploads and runtime/assets are writable by nginx/php-fpm.
# Fresh named volumes or root-executed CLI runs often leave files as root:root,
# which trips HumHub's is_writable() check or AssetManager copy().
set -eu
chown -R nginx:nginx \
  /var/www/localhost/htdocs/protected/config \
  /var/www/localhost/htdocs/uploads \
  /var/www/localhost/htdocs/assets \
  /var/www/localhost/htdocs/protected/runtime \
  2>/dev/null || true
chmod -R u+rwX,g+rwX \
  /var/www/localhost/htdocs/protected/config \
  /var/www/localhost/htdocs/uploads \
  /var/www/localhost/htdocs/assets \
  /var/www/localhost/htdocs/protected/runtime \
  2>/dev/null || true

