#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

SLUG="teksttv-wp-extensions"
MAIN_FILE="${SLUG}.php"
OUTPUT_DIR="${OUTPUT_DIR:-${ROOT_DIR}/dist}"

VERSION="$(awk '
	/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*/ {
		sub(/^[[:space:]]*\*?[[:space:]]*Version:[[:space:]]*/, "")
		sub(/[[:space:]]+$/, "")
		print
		exit
	}
' "$MAIN_FILE")"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-(alpha|beta|rc)\.[0-9]+)?$ ]]; then
	echo "Invalid Version header in ${MAIN_FILE}: '${VERSION}'" >&2
	exit 1
fi

STAGING_DIR="${OUTPUT_DIR}/${SLUG}"
ZIP_PATH="${OUTPUT_DIR}/${SLUG}-${VERSION}.zip"
CHECKSUM_PATH="${ZIP_PATH}.sha256"

rm -rf "$STAGING_DIR"
mkdir -p "$STAGING_DIR"

runtime_paths=(
	"$MAIN_FILE"
	"README.md"
	"src"
)

rsync -a "${runtime_paths[@]}" "$STAGING_DIR/"
find "$STAGING_DIR" -name '.DS_Store' -delete

rm -f "$ZIP_PATH" "$CHECKSUM_PATH"
(cd "$OUTPUT_DIR" && zip -qr "$(basename "$ZIP_PATH")" "$SLUG")
rm -rf "$STAGING_DIR"

if command -v sha256sum >/dev/null 2>&1; then
	(cd "$OUTPUT_DIR" && sha256sum "$(basename "$ZIP_PATH")" > "$(basename "$CHECKSUM_PATH")")
else
	(cd "$OUTPUT_DIR" && shasum -a 256 "$(basename "$ZIP_PATH")" > "$(basename "$CHECKSUM_PATH")")
fi

echo "Built ${ZIP_PATH}"
echo "Checksum ${CHECKSUM_PATH}"
