#!/usr/bin/env bash
# Full-chain monorepo verification (invoked as `composer verify`).
# Every phase runs to completion only if the previous one passed.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

step() { printf '\n=== %s ===\n' "$1"; }

step "root composer install"
composer install --no-interaction

step "pinned install paths present"
for pkg in core schema laravel bot; do
    test -e "vendor/merezarezaei/teleproto-${pkg}" \
        || { echo "FAIL: vendor/merezarezaei/teleproto-${pkg} missing (plugin pin not honored)"; exit 1; }
done
test -L vendor/merezarezaei/teleproto-core \
    || { echo "FAIL: teleproto-core is not symlinked to packages/core"; exit 1; }
echo "pins OK (symlinked path repos)"

step "package suites"
for pkg in core schema laravel bot; do
    composer test --working-dir="packages/${pkg}"
done

step "phpstan x3"
for pkg in core schema laravel; do
    composer analyse --working-dir="packages/${pkg}"
done

step "regeneration idempotence"
php packages/schema/bin/generate-method-builders.php --check
php packages/schema/bin/generate-userscope-schema.php --check
php packages/schema/bin/generate-skill-files.php --check
git diff --exit-code -- packages/core/src packages/laravel/src packages/schema/skills \
    || { echo "FAIL: generators produced a diff — committed generated artifacts are stale"; exit 1; }
echo "generators idempotent"

step "verify complete"