#!/usr/bin/env bash
# Build a distribution ZIP of the plugin, ready to upload to WordPress.org
# or install via WP Admin. Explicitly excludes every dot-file / dot-directory
# (.git, .github, .gitignore, .distignore, .wordpress-org, .DS_Store, ...)
# so the automated wordpress.org plugin scanner does not flag hidden files.
#
# Usage:  bin/build-zip.sh          -> writes ../<slug>.zip
#         bin/build-zip.sh /out/dir -> writes /out/dir/<slug>.zip

set -euo pipefail

SLUG="navest-toolkit-for-woocommerce"
SRC_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
DEST_DIR="${1:-$(dirname "$SRC_DIR")}"
ZIP_PATH="$DEST_DIR/$SLUG.zip"

# Build in a temp staging directory so we can copy only what should ship
# and never risk zip-glob edge cases missing a hidden file.
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

# rsync copies everything EXCEPT the excluded patterns. Explicit list keeps
# the intent obvious to future readers.
rsync -a \
	--exclude '.git' \
	--exclude '.github' \
	--exclude '.wordpress-org' \
	--exclude '.gitignore' \
	--exclude '.gitattributes' \
	--exclude '.distignore' \
	--exclude '.editorconfig' \
	--exclude '.DS_Store' \
	--exclude '.idea' \
	--exclude '.vscode' \
	--exclude 'node_modules' \
	--exclude 'vendor' \
	--exclude 'bin' \
	--exclude '*.log' \
	--exclude '*.zip' \
	--exclude 'README.md' \
	--exclude 'CHANGELOG.md' \
	--exclude 'CONTRIBUTING.md' \
	"$SRC_DIR/" "$STAGE/$SLUG/"

rm -f "$ZIP_PATH"
( cd "$STAGE" && zip -qr "$ZIP_PATH" "$SLUG" )

echo "Built: $ZIP_PATH"
echo "Size:  $(du -h "$ZIP_PATH" | cut -f1)"
echo "Sanity check — hidden files in ZIP:"
if unzip -l "$ZIP_PATH" | grep -E '(^|/)\.' >/dev/null; then
	unzip -l "$ZIP_PATH" | grep -E '(^|/)\.' >&2
	echo "ERROR: ZIP contains hidden files!" >&2
	exit 1
fi
echo "  (none — clean)"
