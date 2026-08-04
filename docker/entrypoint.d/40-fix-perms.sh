#!/bin/sh
# Ensure named-volume config/uploads are writable by nginx/php-fpm.
# Fresh named volumes often land as root:root, which trips HumHub's
# is_writable() check on config/dynamic.php.
set -eu
chown -R nginx:nginx \
  /var/www/localhost/htdocs/protected/config \
  /var/www/localhost/htdocs/uploads \
  2>/dev/null || true
chmod -R u+rwX,g+rwX \
  /var/www/localhost/htdocs/protected/config \
  /var/www/localhost/htdocs/uploads \
  2>/dev/null || true
