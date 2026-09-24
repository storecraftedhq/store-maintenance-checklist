#!/usr/bin/env bash
set -euo pipefail

# Free WP.org plugin: runtime uses SPL autoload — no Composer vendor in the zip.

PLUGIN_SLUG="store-maintenance-checklist"
PLUGIN_FILE="store-maintenance-checklist.php"
DIST_DIR="dist"

VERSION=$(grep -m1 "Version:" "$PLUGIN_FILE" | awk '{print $NF}')

if [ -z "$VERSION" ]; then
	echo "Error: could not read version from $PLUGIN_FILE" >&2
	exit 1
fi

if [ ! -f "build/index.js" ] || [ ! -f "build/index.asset.php" ]; then
	echo "Error: missing build/ assets. Run: npm run build" >&2
	exit 1
fi

BUILD_DIR=$(mktemp -d)
PLUGIN_BUILD_DIR="$BUILD_DIR/$PLUGIN_SLUG"

cleanup() {
	rm -rf "$BUILD_DIR"
}
trap cleanup EXIT

echo "Building $PLUGIN_SLUG v$VERSION..."

mkdir -p "$PLUGIN_BUILD_DIR"

rsync -a --exclude-from=".distignore" . "$PLUGIN_BUILD_DIR/"

if [ -d "$PLUGIN_BUILD_DIR/vendor" ]; then
	echo "Error: vendor/ must not be packaged (runtime uses SPL autoload)" >&2
	exit 1
fi

mkdir -p "$DIST_DIR"
ZIP_PATH="$DIST_DIR/$PLUGIN_SLUG-$VERSION.zip"
rm -f "$ZIP_PATH"

echo "Creating $ZIP_PATH..."
(cd "$BUILD_DIR" && zip -rq - "$PLUGIN_SLUG/") > "$ZIP_PATH"

SIZE=$(du -sh "$ZIP_PATH" | cut -f1)
echo "Done: $ZIP_PATH ($SIZE)"
