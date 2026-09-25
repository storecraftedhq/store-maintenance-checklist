#!/usr/bin/env bash
# Pre-release verification: code standards, tests, zip build, and artefact checks.
#
# Usage: bash bin/verify-release.sh
#        npm run release:verify

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

PLUGIN_SLUG="store-maintenance-checklist-for-woocommerce"
PLUGIN_FILE="store-maintenance-checklist.php"

echo "==> PHPCS"
composer run phpcs

echo ""
echo "==> PHPUnit"
vendor/bin/phpunit --configuration phpunit.xml.dist

echo ""
echo "==> Ensure admin assets are built"
if [ ! -f "build/index.js" ] || [ ! -f "build/index.asset.php" ]; then
	npm run build
fi

echo ""
echo "==> Build distribution zip"
bash bin/build-zip.sh

VERSION="$(grep -m1 'Version:' "$PLUGIN_FILE" | awk '{print $NF}')"
ZIP_PATH="dist/${PLUGIN_SLUG}-${VERSION}.zip"

if [[ ! -f "$ZIP_PATH" ]]; then
	echo "Error: expected zip not found at ${ZIP_PATH}" >&2
	exit 1
fi

echo ""
echo "==> Verify zip contents"

ZIP_ENTRIES="$(unzip -Z1 "$ZIP_PATH")"

zip_contains() {
	local needle="${PLUGIN_SLUG}/$1"
	echo "$ZIP_ENTRIES" | grep -Fx "$needle" >/dev/null
}

zip_matches_forbidden() {
	local fragment="$1"
	echo "$ZIP_ENTRIES" | grep -F "$fragment" >/dev/null
}

FORBIDDEN_FRAGMENTS=(
	"${PLUGIN_SLUG}/tests/"
	"${PLUGIN_SLUG}/phpcs.xml"
	"${PLUGIN_SLUG}/phpunit.xml"
	"${PLUGIN_SLUG}/phpunit.xml.dist"
	"${PLUGIN_SLUG}/composer.json"
	"${PLUGIN_SLUG}/composer.lock"
	"${PLUGIN_SLUG}/vendor/"
	"${PLUGIN_SLUG}/node_modules/"
	"${PLUGIN_SLUG}/docker-compose"
	"${PLUGIN_SLUG}/sales-copy.md"
	"${PLUGIN_SLUG}/documentation/"
	"${PLUGIN_SLUG}/dev-docs/"
	"${PLUGIN_SLUG}/bin/"
	"${PLUGIN_SLUG}/src/"
	"${PLUGIN_SLUG}/.claude"
	"${PLUGIN_SLUG}/.cursor"
	"${PLUGIN_SLUG}/AGENTS.md"
	"${PLUGIN_SLUG}/CLAUDE.md"
	"${PLUGIN_SLUG}/package.json"
	"${PLUGIN_SLUG}/package-lock.json"
	"plugin-check"
	"query-monitor"
)

for fragment in "${FORBIDDEN_FRAGMENTS[@]}"; do
	if zip_matches_forbidden "$fragment"; then
		echo "Error: zip must not include ${fragment#${PLUGIN_SLUG}/}" >&2
		exit 1
	fi
done

REQUIRED_PATHS=(
	"${PLUGIN_FILE}"
	'uninstall.php'
	'includes/class-stmc-autoloader.php'
	'includes/class-stmc-plugin.php'
	'includes/Admin/class-stmc-admin.php'
	'build/index.js'
	'build/index.asset.php'
)

for path in "${REQUIRED_PATHS[@]}"; do
	if ! zip_contains "$path"; then
		echo "Error: zip is missing required file ${path}" >&2
		exit 1
	fi
done

ZIP_SIZE="$(du -sh "$ZIP_PATH" | cut -f1)"
echo ""
echo "Release verification passed."
echo "  Version: ${VERSION}"
echo "  Zip:     ${ZIP_PATH} (${ZIP_SIZE})"
echo ""
echo "Next steps:"
echo "  1. Smoke-test in Docker: npm run docker:up"
echo "  2. Plugin Check on Docker: wp plugin check store-maintenance-checklist-for-woocommerce"
echo "  3. Tag the release:       git tag ${VERSION}"
echo "  4. Push tag:              git push origin ${VERSION}"
