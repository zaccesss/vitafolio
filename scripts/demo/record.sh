#!/bin/sh
# records the README clips and screenshots from a throwaway local copy of the site with made-up people.
# nothing here touches the live site or its database: everything runs on sqlite in
# storage/app/demo.sqlite, which is rebuilt on every run. pass build, share, compile or shots to
# record just one part, then run build-assets.sh to turn the results into the README files.
# needs php 8.4, node, ffmpeg and webp (brew install php@8.4 ffmpeg webp)
set -eu

cd "$(dirname "$0")/../.."
demo=scripts/demo
db=storage/app/demo.sqlite
port=${DEMO_PORT:-8123}

# homebrew keeps php 8.4 out of the path while a newer php is the default
[ -d /opt/homebrew/opt/php@8.4/bin ] && PATH=/opt/homebrew/opt/php@8.4/bin:$PATH

# real environment variables win over .env, so these keep the run local whatever .env holds. sign-up
# checks, error reporting, search pings and media uploads are switched off. The cache lives in memory
# so the login rate limit never carries over from an earlier run
export DB_CONNECTION=sqlite DB_DATABASE="$PWD/$db" APP_ENV=local APP_DEBUG=false
export APP_URL=http://vitafolio.isaacadjei.me SITE_OWNER_NAME="Isaac Adjei" SITE_OWNER_URL=https://isaacadjei.me
export CACHE_STORE=array SESSION_DRIVER=file QUEUE_CONNECTION=sync MAIL_MAILER=log
export TURNSTILE_SITE_KEY='' TURNSTILE_SECRET='' SENTRY_LARAVEL_DSN='' INDEXNOW_KEY='' CLOUDINARY_URL='' CLOUDINARY_CLOUD_NAME=''
export LATEX_ASSETS_URL=https://zaccesss.github.io/vitafolio-latex/
export DEMO_PORT=$port

rm -f "$db"
touch "$db"
php artisan config:clear -q
php artisan migrate --force -q
php artisan tinker --execute="require '$demo/seed.php';"
npm run build --silent

php artisan serve --host=127.0.0.1 --port="$port" >/dev/null 2>&1 &
server=$!
trap 'kill "$server" 2>/dev/null' EXIT
until curl -s -o /dev/null "http://127.0.0.1:$port/"; do sleep 1; done

cd "$demo"
npm ci --silent --no-audit --no-fund
npx playwright install chromium --no-shell
node capture.mjs "$@"
echo "recorded into $demo/out, now run $demo/build-assets.sh"
