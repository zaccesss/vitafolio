#!/bin/sh
# turns what record.sh captured into the clips the site and the README share, the README's screenshots
# and the full-resolution originals kept for reuse elsewhere. needs ffmpeg and webp (brew install ffmpeg webp)
set -eu

cd "$(dirname "$0")/../.."
src=scripts/demo/out
demo=public/demo
originals=docs/assets/demo/originals
shots=docs/assets/screenshots
mkdir -p "$demo" "$originals" "$shots"

# animated webp is a third of the size of the same gif, so the gif is only a stepping stone
clip() {
  name=$1
  still_at=$2
  in="$src/video/$name.webm"
  [ -f "$in" ] || return 0
  tmp=$(mktemp -d)
  ffmpeg -v error -y -i "$in" -vf "fps=12,scale=960:-1:flags=lanczos,split[a][b];[a]palettegen=max_colors=128:stats_mode=diff[p];[b][p]paletteuse=dither=bayer:bayer_scale=4:diff_mode=rectangle" "$tmp/$name.gif"
  gif2webp -q 70 -m 4 -lossy -mt -quiet "$tmp/$name.gif" -o "$demo/$name.webp"
  ffmpeg -v error -y -i "$in" -c:v libx264 -pix_fmt yuv420p -crf 23 -preset slow -movflags +faststart -an "$demo/$name.mp4"
  # the still is what people with reduced motion see, so it is taken where the clip says the most
  ffmpeg -v error -y -ss "$still_at" -i "$in" -frames:v 1 -vf "scale=960:-1" "$tmp/$name.png"
  cwebp -q 85 -quiet "$tmp/$name.png" -o "$demo/$name-still.webp"
  cp "$in" "$originals/"
  rm -rf "$tmp"
}
clip build 19
clip share 10
clip compile 13
clip build-dark 19
clip share-dark 10
clip compile-dark 13

for png in "$src"/shots/*.png; do
  [ -f "$png" ] || continue
  name=$(basename "$png" .png)
  cwebp -q 85 -m 6 -resize 1600 0 -quiet "$png" -o "$shots/$name.webp"
  cp "$png" "$originals/"
done
echo "updated $demo, $shots and $originals"
