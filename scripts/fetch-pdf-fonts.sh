#!/bin/sh
# fetches the simplified chinese pdf font into resources/pdf/fonts. the files are too large to keep in
# git (about 8 MB each), so the docker build and ci run this instead. each file is checked against
# its published sha-256 and refused when it differs. typst embeds only the characters a pdf uses
set -eu

dir="$(cd "$(dirname "$0")/.." && pwd)/resources/pdf/fonts"
base="https://raw.githubusercontent.com/notofonts/noto-cjk/Sans2.004/Sans/SubsetOTF/SC"

fetch() {
    name="$1"
    sum="$2"
    if [ -f "$dir/$name" ] && echo "$sum  $dir/$name" | sha256sum -c - >/dev/null 2>&1; then
        return 0
    fi
    curl -sSfL --retry 5 --retry-delay 5 --retry-all-errors -o "$dir/$name.part" "$base/$name"
    echo "$sum  $dir/$name.part" | sha256sum -c - >/dev/null
    mv "$dir/$name.part" "$dir/$name"
}

fetch NotoSansSC-Regular.otf faa6c9df652116dde789d351359f3d7e5d2285a2b2a1f04a2d7244df706d5ea9
fetch NotoSansSC-Bold.otf c6cb5a93abaa9edc8ee7463b7ebb7f42d618d40e6ed2f7a5371c97b0b64767c0
echo "pdf fonts ready in $dir"
