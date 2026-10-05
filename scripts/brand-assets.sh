#!/bin/sh
# renders every icon and share image in public/ from the svg sources in resources/brand.
# needs rsvg-convert (librsvg) and python3. run it after changing a source svg, then commit
# the results: the app serves these files as they are and never builds them itself.
set -eu

cd "$(dirname "$0")/.."
src=resources/brand
out=public
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT

command -v rsvg-convert >/dev/null || { echo "rsvg-convert is required (brew install librsvg)" >&2; exit 1; }

png() { rsvg-convert -w "$2" -h "$2" "$src/$1" -o "$3"; }

png icon-tile.svg 192 "$out/icon-192.png"
png icon-tile.svg 512 "$out/icon-512.png"
png icon-maskable.svg 512 "$out/icon-maskable-512.png"
# apple adds its own rounded corners, so this one is full bleed
png icon-square.svg 180 "$out/apple-touch-icon.png"
rsvg-convert -w 1200 -h 630 "$src/og-default.svg" -o "$out/og-default.png"
rsvg-convert -w 1280 -h 640 "$src/social-preview.svg" -o "$src/social-preview.png"

for size in 16 32 48; do png icon-tile.svg "$size" "$tmp/$size.png"; done
cp "$src/icon-tile.svg" "$out/favicon.svg"

# an ico file is a small directory of png images; every current browser reads the png form
python3 - "$tmp" "$out/favicon.ico" <<'PY'
import struct, sys
folder, target = sys.argv[1], sys.argv[2]
images = [(s, open(f"{folder}/{s}.png", "rb").read()) for s in (16, 32, 48)]
offset = 6 + 16 * len(images)
head = struct.pack("<HHH", 0, 1, len(images))
entries = b""
for size, data in images:
    entries += struct.pack("<BBBBHHII", size, size, 0, 0, 1, 32, len(data), offset)
    offset += len(data)
open(target, "wb").write(head + entries + b"".join(d for _, d in images))
PY

echo "brand assets written to $out and $src/social-preview.png"
