#!/bin/bash

# Exit if any command fails.
set -e

# StoreCrafted Docker setup — seed profile: demo+
# Extras: Plugin Check (PCP) + Query Monitor (local/dev only; not in plugin ZIP).

WP_CONTAINER=${1:-store-maintenance-checklist-wordpress}
SITE_URL=${WP_URL:-"localhost:8888"}
GEN_DATE_START=${GEN_DATE_START:-2023-01-01}
GEN_DATE_END=${GEN_DATE_END:-2026-04-22}
# Extra aged batch (older than 3 years) for orders.aged_* checks / archive demos.
AGED_GEN_START=${AGED_GEN_START:-2019-01-01}
AGED_GEN_END=${AGED_GEN_END:-2022-09-01}
AGED_GEN_COUNT=${AGED_GEN_COUNT:-40}

redirect_output() {
    if [[ -z "$DEBUG" ]]; then
        local tmp
        tmp=$(mktemp)
        if ! "$@" >"$tmp" 2>&1; then
            cat "$tmp"
            rm -f "$tmp"
            return 1
        fi
        rm -f "$tmp"
    else
        "$@"
    fi
}

# --user 33:33 matches the wordpress image www-data UID/GID.
# See: https://hub.docker.com/_/wordpress#running-as-an-arbitrary-user
cli() {
    redirect_output docker run -i --rm \
        --volumes-from "$WP_CONTAINER" \
        --network store-maintenance-checklist_default \
        --user 33:33 \
        -e WORDPRESS_DB_HOST=mysql \
        -e WORDPRESS_DB_USER=root \
        -e WORDPRESS_DB_PASSWORD=password \
        -e WORDPRESS_DB_NAME=wordpress \
        wordpress:cli "$@"
}

echo "Waiting for database to be ready..."
until docker exec store-maintenance-checklist-mysql mysqladmin ping -h localhost -u root -ppassword --silent 2>/dev/null; do
    echo "  Not ready yet, retrying in 3s..."
    sleep 3
done
echo "Database is ready."

echo "Waiting for WordPress to be ready..."
until docker exec "$WP_CONTAINER" test -f /var/www/html/wp-includes/version.php 2>/dev/null; do
    echo "  Not ready yet, retrying in 3s..."
    sleep 3
done
echo "WordPress is ready."

if cli wp plugin is-active store-maintenance-checklist --path=/var/www/html; then
    echo
    echo "Environment is already set up."
    echo "WordPress:   http://${SITE_URL}/wp-admin/  (admin / password)"
    echo "Mailpit:     http://localhost:${MAILPIT_UI_PORT:-8025}/"
    echo "phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-8080}/  (root / password)"
    echo "Plugin Check: Tools → Plugin Check, or: wp plugin check store-maintenance-checklist"
    echo "Query Monitor: toolbar when logged in as admin (dev profiling only)"
    exit 0
fi

echo
echo "Setting up WordPress..."
echo

cli wp core install \
    --path=/var/www/html \
    --url="$SITE_URL" \
    --title="Store Maintenance Checklist Dev" \
    --admin_name=admin \
    --admin_password=password \
    --admin_email=admin@example.com \
    --skip-email

echo "Installing WooCommerce..."
cli wp plugin install woocommerce --activate --path=/var/www/html

echo "Enabling HPOS..."
cli wp option update woocommerce_feature_custom_order_tables_enabled yes --path=/var/www/html
cli wp option update woocommerce_custom_orders_table_enabled yes --path=/var/www/html

echo "Installing wc-smooth-generator..."
cli wp plugin install \
    https://github.com/woocommerce/wc-smooth-generator/releases/download/1.3.0/wc-smooth-generator.zip \
    --activate --path=/var/www/html

echo "Installing Plugin Check (dev tool — not shipped in plugin ZIP)..."
cli wp plugin install plugin-check --activate --path=/var/www/html

echo "Installing Query Monitor (dev tool — not shipped in plugin ZIP)..."
cli wp plugin install query-monitor --activate --path=/var/www/html

echo "Activating store-maintenance-checklist..."
cli wp plugin activate store-maintenance-checklist --path=/var/www/html

echo "Configuring WordPress location settings..."
cli wp option update timezone_string "America/New_York" --path=/var/www/html
cli wp option update woocommerce_default_country "US" --path=/var/www/html
cli wp option update woocommerce_currency "USD" --path=/var/www/html

echo "Generating test products..."
cli wp wc generate products 20 --path=/var/www/html

echo "Generating test orders (${GEN_DATE_START}–${GEN_DATE_END})..."
cli wp wc generate orders 100 \
    --date-start="${GEN_DATE_START}" \
    --date-end="${GEN_DATE_END}" \
    --path=/var/www/html

echo "Generating aged orders (${AGED_GEN_START}–${AGED_GEN_END}, ${AGED_GEN_COUNT})..."
# Prefer CRUD over wc-smooth-generator here: Faker can fatal without PHP intl.
cli wp eval-file /var/www/html/wp-content/plugins/store-maintenance-checklist/bin/seed-aged-orders.php \
    "${AGED_GEN_COUNT}" "${AGED_GEN_START}" "${AGED_GEN_END}" \
    --path=/var/www/html

echo
echo "Done!"
echo "WordPress:   http://${SITE_URL}/wp-admin/  (admin / password)"
echo "Mailpit:     http://localhost:${MAILPIT_UI_PORT:-8025}/  (catch all wp_mail)"
echo "phpMyAdmin:  http://localhost:${PHPMYADMIN_PORT:-8080}/  (root / password)"
echo
echo "Dev tools (local only — never ship in the plugin ZIP):"
echo "  Plugin Check:  Tools → Plugin Check"
echo "                 or: wp plugin check store-maintenance-checklist"
echo "  Query Monitor: admin toolbar when logged in"
