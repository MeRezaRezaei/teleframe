#!/bin/sh
#
# THE MUTATION GATE, IN THE ORDER THAT MAKES IT TRUSTWORTHY.
#
set -eu

ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$ROOT"

SCOPE="entity"
MIN_MSI="0"

# Argument parsing: first arg may be a scope name or an MSI threshold (legacy).
case "${1:-}" in
    ''|*[!0-9]*)  # non-numeric or empty -> scope name (or default)
        if [ -n "${1:-}" ]; then
            SCOPE="$1"
            MIN_MSI="${2:-0}"
        fi
        ;;
    *)
        MIN_MSI="$1"
        SCOPE="${2:-entity}"
        ;;
esac

# `all` runs every scope in sequence.
if [ "$SCOPE" = "all" ]; then
    for s in entity bus schema ingest; do
        "$0" "$s" "$MIN_MSI"
    done
    exit 0
fi

CONFIG="infection/${SCOPE}.json5"
# Per-area configs live in infection/ and write their logs RELATIVE TO THE CONFIG
# (measured: `logs.summary: "bus-infection-summary.json"` lands in infection/).
# Reading the summary from the repo root therefore found no artifact and refused a
# run that had actually completed — the assert never saw the result.
SUMMARY="infection/${SCOPE}-infection-summary.json"
# entity scope uses root infection.json5 + infection-summary.json
if [ "$SCOPE" = "entity" ]; then
    CONFIG="infection.json5"
    SUMMARY="infection-summary.json"
fi

echo "== mutation gate ($SCOPE): preflight =="
php bin/infection-preflight.php "$SCOPE"

echo "== mutation gate ($SCOPE): measuring (min Covered MSI ${MIN_MSI}) =="
vendor/bin/infection \
    -c "$CONFIG" \
    -j4 \
    --with-timeouts \
    --min-msi=0 \
    --min-covered-msi="$MIN_MSI" \
    --no-progress

echo "== mutation gate ($SCOPE): asserting the result =="
php bin/infection-result-assert.php "$SUMMARY" "$SCOPE"

echo "== mutation gate ($SCOPE): PASSED =="
