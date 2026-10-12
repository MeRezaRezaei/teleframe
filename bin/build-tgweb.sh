#!/usr/bin/env bash
# One-command build of the embedded Telegram Web client (telegram-tt / Web A)
# into tgweb/dist, served by the package at /tg via Route::telegramWeb.
#
# The cloned source tree and every npm artifact live under tgweb/, which is
# gitignored (npm output is not portable) — this script is the tracked,
# reproducible build entry point. It clones the client on first use, installs
# dependencies, and compiles the bundle.
#
# REQUIRED: TELEGRAM_API_ID and TELEGRAM_API_HASH must match the credentials
# you want baked into the bundle (register them at my.telegram.org; the same
# pair the engine uses is fine — Telegram allows concurrent sessions).
#
# Usage:
#   TELEGRAM_API_ID=12345 TELEGRAM_API_HASH=abcdef ./bin/build-tgweb.sh
#
# Optional:
#   TGWEB_REPO   upstream clone URL            (default: https://github.com/Ajaxy/telegram-tt)
#   TGWEB_BRANCH branch/tag to build           (default: master)
#   TELEGRAM_WEB_LIVE_ENDPOINT  relay endpoint baked into dist/relay-bootstrap.js
#                               (same-origin file, referenced by <script src>
#                               before the module script — CSP 'self'-safe)
#                               so the client connects through the package's
#                               relay (e.g. wss://relay.example.com/apiws).
#   TG_DC_HOST                  bare-host alias for TELEGRAM_WEB_LIVE_ENDPOINT
#                               (e.g. relay.example.com → wss://relay:443/apiws).
#   TELEGRAM_WEB_RELAY_TICKET_URL  same-origin Laravel ticket-mint endpoint
#                               baked into relay-bootstrap.js (e.g.
#                               /teleframe/accounts/1/ticket) so the GramJS
#                               worker can present ?ticket= on every WS dial
#                               (relay W-02 one-shot ticket gate).
#   TELEGRAM_WEB_RELAY_BEARER   optional bearer token for the ticket-mint
#                               fetch (token-authenticated hosts / harnesses);
#                               production usually relies on the page's own
#                               host session cookie (same-origin fetch) and
#                               leaves this unset.
#   TELEGRAM_WEB_ENABLED=1  export into the environment after building so the
#                           Laravel config key teleframe.telegram_web.enabled
#                           turns the route on.
set -euo pipefail

cd "$(dirname "$0")/.."

REPO="${TGWEB_REPO:-https://github.com/Ajaxy/telegram-tt}"
BRANCH="${TGWEB_BRANCH:-master}"

: "${TELEGRAM_API_ID:?TELEGRAM_API_ID is required (my.telegram.org)}"
: "${TELEGRAM_API_HASH:?TELEGRAM_API_HASH is required (my.telegram.org)}"

if [ ! -d tgweb ]; then
    echo ">> cloning telegram-tt (${REPO}) into tgweb/"
    git clone --depth=1 --branch "${BRANCH}" "${REPO}" tgweb
else
    echo ">> tgweb/ already exists — keeping it (remove it for a fresh clone)"
fi

cd tgweb

echo ">> applying tgweb-patches/*.patch"
for patch in ../tgweb-patches/*.patch; do
    [ -e "${patch}" ] || continue
    echo "   ${patch}"
    git apply --check "${patch}"
    git apply "${patch}"
done

if [ ! -d node_modules ]; then
    echo ">> installing dependencies (npm ci)"
    npm ci
fi

echo ">> building the bundle (api_id=${TELEGRAM_API_ID})"
TELEGRAM_API_ID="${TELEGRAM_API_ID}" \
TELEGRAM_API_HASH="${TELEGRAM_API_HASH}" \
TG_DC_HOST="${TG_DC_HOST:-}" \
TELEGRAM_WEB_LIVE_ENDPOINT="${TELEGRAM_WEB_LIVE_ENDPOINT:-}" \
TELEGRAM_WEB_RELAY_SERVER_KEY_FP="${TELEGRAM_WEB_RELAY_SERVER_KEY_FP:-}" \
TELEGRAM_WEB_RELAY_SERVER_KEY_N="${TELEGRAM_WEB_RELAY_SERVER_KEY_N:-}" \
TELEGRAM_WEB_RELAY_SERVER_KEY_E="${TELEGRAM_WEB_RELAY_SERVER_KEY_E:-}" \
npm run build:production

echo ">> baking runtime relay globals into dist/relay-bootstrap.js"
# The stock CSP meta (script-src 'self' 'wasm-unsafe-eval' …) blocks inline
# scripts, so the relay globals live in a same-origin file referenced via
# <script src> instead of an inline <script>. Idempotent on re-run.
# W-38 robustness: the injection greps for its anchor first and aborts loudly
# (never a silent no-op) if a future tgweb build moves the module script tag;
# every baked value is emitted as a JSON-style double-quoted JS string literal
# (backslash and double-quote escaped), so an endpoint/key containing ' " \ or
# control chars can never break the emitted JS.
RELAY_BOOTSTRAP_ANCHOR='<script type="module" crossorigin'

relay_js_quote() {
  printf '"%s"' "$(printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g')"
}

RELAY_BOOTSTRAP=false
if [ -n "${TELEGRAM_WEB_LIVE_ENDPOINT:-}" ]; then
  printf 'globalThis.TELEGRAM_WEB_LIVE_ENDPOINT=%s;\n' "$(relay_js_quote "$TELEGRAM_WEB_LIVE_ENDPOINT")" > dist/relay-bootstrap.js
  RELAY_BOOTSTRAP=true
fi
if [ -n "${TELEGRAM_WEB_RELAY_SERVER_KEY_FP:-}" ] && [ -n "${TELEGRAM_WEB_RELAY_SERVER_KEY_N:-}" ]; then
  printf 'globalThis.TELEGRAM_WEB_RELAY_SERVER_KEY=%s;\n' "$(relay_js_quote "${TELEGRAM_WEB_RELAY_SERVER_KEY_FP}:${TELEGRAM_WEB_RELAY_SERVER_KEY_N}:${TELEGRAM_WEB_RELAY_SERVER_KEY_E:-65537}")" >> dist/relay-bootstrap.js
  RELAY_BOOTSTRAP=true
fi
if [ -n "${TELEGRAM_WEB_RELAY_TICKET_URL:-}" ]; then
  printf 'globalThis.TELEGRAM_WEB_RELAY_TICKET_URL=%s;\n' "$(relay_js_quote "$TELEGRAM_WEB_RELAY_TICKET_URL")" >> dist/relay-bootstrap.js
  RELAY_BOOTSTRAP=true
fi
if [ -n "${TELEGRAM_WEB_RELAY_BEARER:-}" ]; then
  printf 'globalThis.TELEGRAM_WEB_RELAY_BEARER=%s;\n' "$(relay_js_quote "$TELEGRAM_WEB_RELAY_BEARER")" >> dist/relay-bootstrap.js
  RELAY_BOOTSTRAP=true
fi

if [ "$RELAY_BOOTSTRAP" = true ] && ! grep -q 'relay-bootstrap.js' dist/index.html; then
  if ! grep -q "$RELAY_BOOTSTRAP_ANCHOR" dist/index.html; then
    echo "ERROR: dist/index.html has no '${RELAY_BOOTSTRAP_ANCHOR}' anchor (tgweb build layout changed)" >&2
    echo "       inject ./relay-bootstrap.js before the module script by hand, or fix this script" >&2
    exit 1
  fi
  sed -i "s|${RELAY_BOOTSTRAP_ANCHOR}|<script src=\"./relay-bootstrap.js\"></script>${RELAY_BOOTSTRAP_ANCHOR}|" dist/index.html
fi

echo
echo ">> built: $(pwd)/dist"
echo "   serve it under Laravel with:"
echo "     TELEGRAM_WEB_ENABLED=true php artisan route:list --path=tg"
echo "     Route::telegramWeb()  →  GET /tg/{path?} (SPA fallback)"