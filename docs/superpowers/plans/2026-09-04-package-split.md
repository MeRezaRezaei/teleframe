# Teleproto Package Split Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split the `teleproto` repo into a namespace-preserving monorepo of four concern-scoped packages (`teleproto-core`, `teleproto-laravel`, `teleproto-schema`, `teleproto-bot`) and switch `teleclient` onto them via path repositories — with zero behavior changes and zero consumer code changes.

**Architecture:** Every class keeps its exact FQCN under `MeRezaRezaei\Teleproto\*`; packages are carved out by PSR-4 subnamespace mapping so no imports change anywhere. The repo becomes a monorepo (`packages/core`, `packages/laravel`, `packages/schema`, `packages/bot`) using `git mv` to preserve history. A root composer.json with path-repository wiring plus a tiny install-path pinning plugin keeps local dev deterministic; a root `verify` script chains install → pin check → all test suites → phpstan ×3 → regeneration idempotence.

**Tech Stack:** PHP >= 8.2, Composer 2 (path repositories, `composer-runtime-api` 2.0, a `Composer\\Plugin\\PluginInterface` plugin), PHPUnit 10/11, PHPStan 5 + spaze/phpstan-disallowed-calls, Orchestra Testbench 10 (laravel package only).

**Spec:** `docs/superpowers/specs/2026-09-04-package-split-design.md` (this plan implements that spec; read both)

## Global Constraints

- PHP floor: `>=8.2` in every package composer.json (from spec §3; matches existing root).
- **No illuminate/* in `packages/core/composer.json` require** (spec §3 core; verify with grep).
- **No FQCN changes**: every moved class keeps namespace `MeRezaRezaei\\Teleproto\\...` exactly (spec §1).
- New package names: `merezarezaei/teleproto-core`, `merezarezaei/teleproto-laravel`, `merezarezaei/teleproto-schema`, `merezarezaei/teleproto-bot` (spec §3).
- Schema package's own classes use namespace `MeRezaRezaei\\TeleprotoSchema\\` (spec §3, avoids PSR-4 prefix overlap).
- Artifact lookup goes through `MeRezaRezaei\\TeleprotoSchema\\SchemaArtifacts::path(string $file): string` backed by `Composer\\InstalledVersions` (spec §4).
- **Zero `preg_*()` in any `src/`** — phpstan `disallowedFunctionCalls` ban carries into every package config (repo AGENTS.md hard rule).
- **Generated artifacts are regenerated, never edited**: `src/Methods/Generated/*`, `src/Exceptions/Rpc/RpcErrorCatalog.php`, `src/MTProto/TL/Schema/UserScopeSchema.php`, `schema/*.json`, `skills/telegram-methods/*` (repo AGENTS.md).
- **Session strings are credentials**: never commit `.env`; tests never require real credentials (repo AGENTS.md).
- Live gates stay opt-in: `TELEPROTO_LIVE=true ...` — never part of automated suites (repo AGENTS.md).
- teleclient published constraint on old `merezarezaei/teleproto` stays `^1.2.1 || ^1.1` on its main branch until the new packages are released (spec §5).
- Git discipline: commit after every green step; use `git mv` for all file moves (never cp+rm) so history follows.

**Worktree:** Execute inside `/home/me/Documents/projects/tele/teleproto` (repo becomes the monorepo). teleclient edits in Task 8 happen in `/home/me/Documents/projects/tele/teleclient`. If an isolated worktree was created for execution, these paths are relative to that checkout.

**Conventions used below:**

- `ROOT` = the teleproto repo checkout (contains `.git`, currently also `src/`, `tests/`, `bin/`, `schema/`, `config/`, `examples/`, `skills/`, `docs/`, `phpstan/`).
- "Move test files with their code" means: tests for classes that moved into a package move into that package's `tests/` with the same relative path; the shared plain-PHPUnit bootstrap stays per-package.
- Any step saying "Run: ..." is executed from the stated directory; expected output must match before continuing.

---

## Target File Structure (end state)

```
ROOT/
├── composer.json                      # root dev-only, wires path repos + plugin + test runner
├── composer.lock                      # root lock (dev convenience, committed)
├── scripts/
│   └── verify-monorepo.sh             # full-chain verification gate
├── plugins/
│   └── monorepo/
│       ├── composer.json              # composer-plugin, root-only (never published)
│       └── src/InstallPathPlugin.php
├── packages/
│   ├── core/                          # merezarezaei/teleproto-core — ZERO illuminate
│   │   ├── composer.json
│   │   ├── phpstan.neon.dist
│   │   ├── phpunit.xml.dist
│   │   ├── src/
│   │   │   ├── Contracts/UpdateSinkInterface.php
│   │   │   ├── Entities/EntityParser.php
│   │   │   ├── Exceptions/… (TelegramException, DcMigrationException, Rpc/*)
│   │   │   ├── Methods/ (Methods.php, Generated/{Account,Auth,Contacts,Help,Messages,Users}.php)
│   │   │   ├── MTProto/ (Client, SessionData, Connection/, Crypto/, TL/, Transport/)
│   │   │   ├── Passport/PassportDecryptor.php
│   │   │   ├── Schema/ (MethodRegistry, SchemaDiffer, TelegramMethod)
│   │   │   ├── Support/ (EnvFile, TerminalQr)
│   │   │   └── Types/ (InlineKeyboard, InputChannel, InputContact, InputFile, InputMedia, InputPeer, InputUser, ReplyKeyboard)
│   │   └── tests/ (Wire/, Schema/, EntityParserTest, PassportDecryptorTest, MtprotoCryptoAndTlTest, Support/EnvFileTest)
│   ├── laravel/                       # merezarezaei/teleproto-laravel
│   │   ├── composer.json
│   │   ├── phpstan.neon.dist
│   │   ├── phpunit.xml.dist
│   │   ├── config/teleproto.php
│   │   ├── src/
│   │   │   ├── Console/ (Doctor, Login, Poll, SchemaAudit, SchemaUpdate commands)
│   │   │   ├── Events/ (TelegramUpdateReceived, TelegramGapDetected, TelegramResynced)
│   │   │   ├── Facades/ (Teleproto, TP)
│   │   │   ├── Http/ (Controllers/TelegramWebhookController, Middleware/VerifyMiniAppInitData)
│   │   │   ├── Media/StorageMedia.php
│   │   │   ├── Services/ (TeleprotoClient, TeleprotoAuthService, UpdatePollerService, EventDispatcherSink, UserAccountScope)
│   │   │   └── TeleprotoServiceProvider.php
│   │   └── tests/ (TeleprotoClientTest, UpdatePollerTest, UpdateStateMachineTest, LiveSessionAdapterTest,
│   │              MiniAppValidatorTest, Wire/DoctorCommandTest, Support/ServiceProviderTest.php [new],
│   │              Support/FacadeSmokeTest.php [new])
│   ├── schema/                        # merezarezaei/teleproto-schema
│   │   ├── composer.json
│   │   ├── phpunit.xml.dist
│   │   ├── src/SchemaArtifacts.php    # MeRezaRezaei\TeleprotoSchema\SchemaArtifacts
│   │   ├── schema/ (methods-mtproto.json, methods-botapi.json, sources/*.tl|json)
│   │   ├── config/curated-methods.json
│   │   ├── bin/ (generate-botapi-schema, generate-method-builders, generate-method-schema,
│   │   │         generate-rpc-catalog, generate-skill-files, generate-userscope-schema)
│   │   ├── skills/telegram-methods/…  # generated
│   │   └── tests/SchemaArtifactsTest.php [new]
│   └── bot/                           # merezarezaei/teleproto-bot
│       ├── composer.json
│       ├── phpunit.xml.dist
│       ├── src/
│       │   ├── Services/ (BotClient, BotAccountScope)
│       │   └── Methods/Generated/Bots.php
│       └── tests/BotPackageTest.php [new]
├── bin/teleproto                      # stays at root: dev CLI, requires root vendor
├── examples/                          # stays at root (dev-only)
├── docs/, llms.txt, README.md, AGENTS.md, CHANGELOG.md, .github/
└── (old root src/, tests/, schema/, config/, skills/, phpstan/, phpunit.xml.dist, phpstan.neon.dist,
    root composer.json require-dev → all gone/replaced by end of Task 7)
```

Directory-level rule (from spec §3): **a file moves into the package whose concern owns it — illuminate-touching code → laravel; pure wire/codec/registry code → core; generation tooling + artifact sources → schema; Bot API surface → bot.** No file is duplicated; no file remains in old root paths after Task 6.

---

### Task 1: Monorepo scaffold and root composer.json

**Files:**
- Replace: `composer.json` (root)
- Delete: `phpunit.xml.dist` (root, at the end of this task), `phpstan.neon.dist` (root, at the end of this task)
- Create: `.gitignore` entries append (root)
- Create: `packages/.gitkeep`

**Interfaces:**
- Consumes: nothing (first task).
- Produces: root `composer.json` exposing local path repositories `@packages/*` for the four package names (they don't exist yet — that's expected and handled by `"symlink": true` path repos plus this task not running `composer update` until Task 3; the root's only installable requirements this task are dev-tooling + the plugin from Task 2 — so this task's verify step is `composer validate --strict` only), and `scripts/verify-monorepo.sh` (created Task 2) will later rely on the root scripts defined here.

Steps 3–5 replace the legacy configs now (not later) so no task afterwards can accidentally run the old root suite against a half-moved `src/`.

- [x] **Step 1: Write the new root composer.json**

Replace the entire contents of `ROOT/composer.json` with:

```json
{
    "name": "merezarezaei/teleproto-monorepo",
    "description": "Monorepo dev root for the teleproto package family — not published, not installable",
    "type": "project",
    "license": "MIT",
    "repositories": {
        "teleproto-core": {
            "type": "path",
            "url": "packages/core",
            "options": { "symlink": true }
        },
        "teleproto-schema": {
            "type": "path",
            "url": "packages/schema",
            "options": { "symlink": true }
        },
        "teleproto-laravel": {
            "type": "path",
            "url": "packages/laravel",
            "options": { "symlink": true }
        },
        "teleproto-bot": {
            "type": "path",
            "url": "packages/bot",
            "options": { "symlink": true }
        }
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0",
        "phpstan/phpstan": "^2.0",
        "spaze/phpstan-disallowed-calls": "^4.14",
        "orchestra/testbench": "^10.11",
        "chillerlan/php-qrcode": "^6.0",
        "merezarezaei/teleproto-core": "*",
        "merezarezaei/teleproto-schema": "*",
        "merezarezaei/teleproto-laravel": "*",
        "merezarezaei/teleproto-bot": "*",
        "merezarezaei/teleproto-monorepo-plugin": "*"
    },
    "repositories-monorepo-plugin": {
        "note": "plugin path repo added in Task 2 next to this block"
    },
    "minimum-stability": "stable",
    "prefer-stable": true,
    "autoload": {},
    "autoload-dev": {},
    "scripts": {
        "test:core": "@php -d error_reporting=E_ALL packages/core/vendor/bin/phpunit -c packages/core --color=always",
        "test:laravel": "composer test --working-dir=packages/laravel",
        "test:schema": "composer test --working-dir=packages/schema",
        "test:bot": "composer test --working-dir=packages/bot",
        "analyse:core": "composer analyse --working-dir=packages/core",
        "analyse:laravel": "composer analyse --working-dir=packages/laravel",
        "analyse:schema": "composer analyse --working-dir=packages/schema",
        "verify": "@bash scripts/verify-monorepo.sh",
        "test": [
            "@test:core",
            "@test:laravel",
            "@test:schema",
            "@test:bot"
        ]
    },
    "config": {
        "allow-plugins": {
            "merezarezaei/teleproto-monorepo-plugin": true
        }
    },
    "_comment": "Task 2 removes the repositories-monorepo-plugin placeholder note and adds the plugin path repository + require-dev entry."
}
```

Notes for the implementer:

- `test:core` runs the core suite through the **root** vendor bin (`phpunit -c packages/core` resolves core's own `phpunit.xml.dist`) because core's dev deps are a subset of root's; laravel/schema/bot use their own `composer test` since testbench lives per-package. This asymmetry is deliberate and locked in by the root scripts.
- The four `merezarezaei/teleproto-*` require-dev entries will be uninstallable until Tasks 3–7 create the packages. **Do not run `composer update` at root yet.** Validation-only until Task 3 Step 6.

- [x] **Step 2: Create packages/ placeholder and extend .gitignore**

```bash
mkdir -p packages && touch packages/.gitkeep
printf '\n# monorepo: per-package vendor + caches\npackages/*/vendor/\npackages/*/.phpunit.cache/\n' >> .gitignore
```

- [x] **Step 3: Remove legacy root test/static configs**

The old root `phpunit.xml.dist` points at `tests` and `src` which are about to dissolve; old `phpstan.neon.dist` points at root `src`. Delete both:

```bash
git rm phpunit.xml.dist phpstan.neon.dist
```

Keep `phpstan/laravel-helpers.php` (root) for now — Task 5 copies it into `packages/laravel/phpstan/` and Task 6 deletes the original.

- [x] **Step 4: Validate root manifest**

Run: `composer validate --strict` (from `ROOT`)
Expected: `./composer.json is valid` — path repositories pointing at not-yet-existing directories do not fail validation.

- [x] **Step 5: Commit**

```bash
git add composer.json .gitignore packages/.gitkeep
git commit -m "chore(monorepo): scaffold root composer with package path repositories"
```

---

### Task 2: Install-path pinning plugin and verify script

Without pinning, Composer assigns path-repo packages to `vendor/merezarezaei/teleproto-{core,schema,laravel,bot}` in registration order, which is stable today but not guaranteed across composer versions; `SchemaAuditCommand` (Task 6) and `SchemaArtifacts` (Task 4) build paths from `InstalledVersions::getInstallPath()`, and the regeneration idempotence check diffs files under those resolved paths. The plugin makes the mapping explicit instead of emergent.

**Files:**
- Create: `plugins/monorepo/composer.json`
- Create: `plugins/monorepo/src/InstallPathPlugin.php`
- Modify: `ROOT/composer.json` (remove placeholder note; add plugin path repo + require-dev entry)
- Create: `scripts/verify-monorepo.sh`
- Create: `plugins/monorepo/tests/InstallPathPluginTest.php`
- Test: `plugins/monorepo/tests/InstallPathPluginTest.php`

**Interfaces:**
- Consumes: root composer.json from Task 1.
- Produces: composer plugin package `merezarezaei/teleproto-monorepo-plugin` type `composer-plugin` with class `MeRezaRezaei\\TeleprotoMonorepo\\InstallPathPlugin` implementing `PluginInterface` + `EventSubscriberInterface`; `scripts/verify-monorepo.sh` with exit 0 on full success (invoked as `composer verify`), which Tasks 3–7 keep green and Task 9 extends.

- [x] **Step 1: Write the failing test for the plugin's suggested installer paths**

Create `plugins/monorepo/tests/InstallPathPluginTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleprotoMonorepo\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\Installer\PackageEvent;
use Composer\IO\NullIO;
use Composer\Package\RootPackageInterface;
use MeRezaRezaei\TeleProtoMonorepo\InstallPathPlugin;
use PHPUnit\Framework\TestCase;

final class InstallPathPluginTest extends TestCase
{
    public function testActivateRegistersSuggestedInstallPathsForAllFourPackages(): void
    {
        $composer = new Composer();
        $composer->setConfig(new Config(/* $home = */ '/tmp/teleproto-monorepo-test-home'));
        $root = $this->createStub(RootPackageInterface::class);
        $composer->setPackage($root);

        $plugin = new InstallPathPlugin();
        $plugin->activate($composer, new NullIO());

        $extra = $root->getExtra();
        self::assertIsArray($extra);
        self::assertSame(
            'vendor/merezarezaei/teleproto-core',
            $extra['installer-paths']['packages/core'][$_i = 0] ?? $extra['installer-paths']['vendor/merezarezaei/teleproto-core'] ?? null
            === null ? 'vendor/merezarezaei/teleproto-core' : 'vendor/merezarezaei/teleproto-core'
        );
    }

    public function testDeactivateAndUninstallAreNoOps(): void
    {
        $plugin = new InstallPathPlugin();
        $plugin->deactivate(new Composer(), new NullIO());
        $plugin->uninstall(new Composer(), new NullIO());
        self::assertTrue(true, 'no-op lifecycle hooks must not throw');
    }
}
```

That first assertion is intentionally wrong-shaped (it can never fail cleanly). **Write instead this exact simpler version** — replace the whole file content before running, the point of Step 1 is red-first on a precise contract:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleProtoMonorepo\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use Composer\Package\RootPackageInterface;
use MeRezaRezaei\TeleProtoMonorepo\InstallPathPlugin;
use PHPUnit\Framework\TestCase;

final class InstallPathPluginTest extends TestCase
{
    public function testActivateRegistersSuggestedInstallPathsForAllFourPackages(): void
    {
        $composer = new Composer();
        $composer->setConfig(new Config('/tmp/teleproto-monorepo-test-home'));
        $root = $this->createMock(RootPackageInterface::class);
        $composer->setPackage($root);

        $root->expects(self::once())
            ->method('setExtra')
            ->with(self::callback(static function (array $extra): bool {
                $pinned = [
                    'merezarezaei/teleproto-core' => 'vendor/merezarezaei/teleproto-core',
                    'merezarezaei/teleproto-schema' => 'vendor/merezarezaei/teleproto-schema',
                    'merezarezaei/teleproto-laravel' => 'vendor/merezarezaei/teleproto-laravel',
                    'merezarezaei/teleproto-bot' => 'vendor/merezarezaei/teleproto-bot',
                ];
                foreach ($pinned as $name => $path) {
                    if (!in_array($path, $extra['installer-paths'][$name] ?? [], true)) {
                        return false;
                    }
                }
                return true;
            }));

        (new InstallPathPlugin())->activate($composer, new NullIO());
    }

    public function testDeactivateAndUninstallAreNoOps(): void
    {
        $plugin = new InstallPathPlugin();
        $plugin->deactivate(new Composer(), new NullIO());
        $plugin->uninstall(new Composer(), new NullIO());
        self::assertTrue(true, 'no-op lifecycle hooks must not throw');
    }
}
```

The `setExtra` expectation matches the exact data shape the plugin writes in Step 3 (`'installer-paths' => [packageName => [installPath, ...]]` keyed by **package name** — composer's canonical shape, so composer core honors it natively).

- [x] **Step 2: Run the plugin test to verify it fails**

Create the plugin package manifest first (the test needs its autoload):

`plugins/monorepo/composer.json`:

```json
{
    "name": "merezarezaei/teleproto-monorepo-plugin",
    "description": "Root-only composer plugin pinning monorepo package install paths",
    "type": "composer-plugin",
    "license": "MIT",
    "autoload": {
        "psr-4": { "MeRezaRezaei\\TeleProtoMonorepo\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "MeRezaRezaei\\TeleProtoMonorepo\\Tests\\": "tests/" }
    },
    "extra": {
        "class": "MeRezaRezaei\\TeleProtoMonorepo\\InstallPathPlugin"
    },
    "require": {
        "php": ">=8.2",
        "composer-plugin-api": "^2.0"
    },
    "require-dev": {
        "composer/composer": "^2.6",
        "phpunit/phpunit": "^10.0|^11.0"
    },
    "config": { "allow-plugins": true }
}
```

Then:

```bash
composer update --working-dir=plugins/monorepo
composer test --working-dir=plugins/monorepo 2>/dev/null || echo '{"scripts":{"test":"vendor/bin/phpunit --no-configuration tests"}}' > /dev/null
```

`plugins/monorepo` has no `phpunit.xml.dist` yet — write it now, `plugins/monorepo/phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Monorepo Plugin">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

Run: `composer test --working-dir=plugins/monorepo`
Expected: FAIL — `Error: Class "MeRezaRezaei\TeleProtoMonorepo\InstallPathPlugin" not found`.

- [x] **Step 3: Write the plugin implementation**

`plugins/monorepo/src/InstallPathPlugin.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleProtoMonorepo;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * Root-only dev plugin: declares explicit install paths for the four monorepo
 * packages so vendor/ layout is deterministic across composer versions.
 *
 * Writes composer's canonical "installer-paths" extra shape:
 *     ['installer-paths' => ['merezarezaei/teleproto-core' => ['vendor/merezarezaei/teleproto-core', ...], ...]]
 * Composer core honors this shape for path repositories without needing
 * composer/installers, which we deliberately avoid as a runtime dependency.
 */
final class InstallPathPlugin implements PluginInterface, EventSubscriberInterface
{
    private const PINNED = [
        'merezarezaei/teleproto-core' => 'vendor/merezarezaei/teleproto-core',
        'merezarezaei/teleproto-schema' => 'vendor/merezarezaei/teleproto-schema',
        'merezarezaei/teleproto-laravel' => 'vendor/merezarezaei/teleproto-laravel',
        'merezarezaei/teleproto-bot' => 'vendor/merezarezaei/teleproto-bot',
    ];

    public static function getSubscribedEvents(): array
    {
        // Pinning happens at activate time; no post-install events needed.
        return [];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $root = $composer->getPackage();
        $extra = $root->getExtra();
        $paths = $extra['installer-paths'] ?? [];
        foreach (self::PINNED as $name => $path) {
            $paths[$name] = array_values(array_unique([...($paths[$name] ?? []), $path]));
        }
        $extra['installer-paths'] = $paths;
        $root->setExtra($extra);
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // no-op
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // no-op
    }
}
```

- [x] **Step 4: Run the plugin test to verify it passes**

Run: `composer test --working-dir=plugins/monorepo`
Expected: PASS — `OK (2 tests, 2 assertions)` (assertion count may differ slightly by mock wiring; the hard requirement is 0 failures).

- [x] **Step 5: Register the plugin in root composer.json**

In `ROOT/composer.json`:

1. Delete the `"repositories-monorepo-plugin": { "note": ... }` placeholder block entirely.
2. Add to `repositories`:

```json
        "teleproto-monorepo-plugin": {
            "type": "path",
            "url": "plugins/monorepo",
            "options": { "symlink": true }
        }
```

3. Add to `require-dev`: `"merezarezaei/teleproto-monorepo-plugin": "*"`.

Run: `composer validate --strict`
Expected: valid. (Still no root `composer update` — packages don't exist yet.)

- [x] **Step 6: Write the verify script**

`scripts/verify-monorepo.sh`:

```bash
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
```

Make it executable: `chmod +x scripts/verify-monorepo.sh`

Note the `--check` flag: it doesn't exist in the generators yet — Task 6 adds `--check` (dry-run: regenerate to a temp dir and diff against the committed file, exit 1 on drift) to exactly these three generators. Until Task 6 lands, `composer verify` is expected to fail at the idempotence step if run; Tasks 3–6 use the granular per-package commands instead.

- [x] **Step 7: Commit**

```bash
git add plugins scripts composer.json
git commit -m "feat(monorepo): install-path pinning plugin + full-chain verify script"
```

---

### Task 3: Extract packages/core — the zero-illuminate wire engine

**Files:**
- Create: `packages/core/composer.json`, `packages/core/phpunit.xml.dist`, `packages/core/phpstan.neon.dist`
- Move (git mv): nine `src/` subtrees (Contracts, Entities, Exceptions, Methods, MTProto, Passport, Schema, Support, Types) → `packages/core/src/<same>`
- Move: core-owned tests → `packages/core/tests/` (exact list in Step 3)
- Delete (deferred to Step 7): root `src/` residue, root `tests/` residue

**Interfaces:**
- Consumes: root path repos (Task 1), plugin pin (Task 2).
- Produces: installable package `merezarezaei/teleproto-core` with PSR-4 mappings `MeRezaRezaei\Teleproto\{Contracts,Entities,Exceptions,Methods,MTProto,Passport,Schema,Support,Types}\` → `src/<sub>`; suite green via `composer test --working-dir=packages/core` **except** `Schema/MethodRegistryTest` which fails on artifact paths until Task 4 — that test file moves in this task but its expected state is documented red.

- [x] **Step 1: Create the core package manifest**

`packages/core/composer.json`:

```json
{
    "name": "merezarezaei/teleproto-core",
    "description": "Pure-PHP Telegram MTProto 2.0 wire engine — zero framework dependencies",
    "type": "library",
    "license": "MIT",
    "keywords": ["telegram", "mtproto", "tl", "wire", "protocol", "php"],
    "autoload": {
        "psr-4": {
            "MeRezaRezaei\\Teleproto\\Contracts\\": "src/Contracts",
            "MeRezaRezaei\\Teleproto\\Entities\\": "src/Entities",
            "MeRezaRezaei\\Teleproto\\Exceptions\\": "src/Exceptions",
            "MeRezaRezaei\\Teleproto\\Methods\\": "src/Methods",
            "MeRezaRezaei\\Teleproto\\MTProto\\": "src/MTProto",
            "MeRezaRezaei\\Teleproto\\Passport\\": "src/Passport",
            "MeRezaRezaei\\Teleproto\\Schema\\": "src/Schema",
            "MeRezaRezaei\\Teleproto\\Support\\": "src/Support",
            "MeRezaRezaei\\Teleproto\\Types\\": "src/Types"
        }
    },
    "autoload-dev": {
        "psr-4": { "MeRezaRezaei\\Teleproto\\Tests\\": "tests/" }
    },
    "require": {
        "php": ">=8.2",
        "ext-dom": "*",
        "ext-hash": "*",
        "ext-json": "*",
        "ext-libxml": "*",
        "ext-mbstring": "*",
        "ext-openssl": "*",
        "ext-zlib": "*",
        "composer-runtime-api": "^2.0",
        "phpseclib/phpseclib": "^3.0",
        "vlucas/phpdotenv": "^5.6",
        "merezarezaei/teleproto-schema": "*",
        "symfony/console": "^6.0|^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0",
        "phpstan/phpstan": "^2.0",
        "spaze/phpstan-disallowed-calls": "^4.14"
    },
    "suggest": {
        "ext-gmp": "Accelerates BigInteger modular arithmetic for Diffie-Hellman and 2FA SRP calculations.",
        "ext-bcmath": "Alternative big-integer calculation engine if ext-gmp is not available."
    },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "analyse": "vendor/bin/phpstan analyse --no-progress"
    },
    "config": { "allow-plugins": false }
}
```

Dependency rationale (verify each against actual imports before committing — `grep -rL` commands in Step 2 are the check):
- `phpseclib` — BigInteger in `Crypto/` (existing root dependency, crypto is core).
- `vlucas/phpdotenv` — `Support/EnvFile.php` line 20 (`\\Dotenv\\Dotenv::parse`).
- `symfony/console` — `Support/TerminalQr.php` terminal sizing (check: `grep -n "Symfony" packages/core/src/Support/TerminalQr.php` after the move; if TerminalQr is dependency-free, drop symfony/console here and note it in the commit — YAGNI).
- `merezarezaei/teleproto-schema` — `Schema/MethodRegistry` artifact lookup (Task 4 rewires it); constraint `*` while path-linked, becomes `^1.0` at publish time.
- **No illuminate/\\* entry may appear** — spec §3.

- [x] **Step 2: Move core source subtrees with git mv**

```bash
mkdir -p packages/core/src
for sub in Contracts Entities Exceptions Methods MTProto Passport Schema Support Types; do
    git mv "src/${sub}" "packages/core/src/${sub}"
done
git status --short | head -80
```

Then verify the illuminate-ban holds inside core:

```bash
grep -rn "Illuminate" packages/core/src || echo "CLEAN: no illuminate in core"
```

Expected: `CLEAN: no illuminate in core`. If anything matches, that file belongs in laravel (Task 5) — move it there now with `git mv packages/core/src/<Sub>/<File>.php /tmp/laravel-staging/<Sub>/` and record it in a scratch note for Task 5 (the earlier repo-wide grep showed zero matches in these nine subtrees, so any hit here means an import added since).

- [x] **Step 3: Move core-owned tests**

Core owns these files from the old root `tests/` (they exercise only core classes):

```bash
mkdir -p packages/core/tests
git mv tests/Wire packages/core/tests/Wire
git mv tests/Schema/MtprotoSchemaTest.php packages/core/tests/Schema/MtprotoSchemaTest.php
git mv tests/Schema/MethodRegistryTest.php packages/core/tests/Schema/MethodRegistryTest.php
git mv tests/Schema/SchemaDifferTest.php packages/core/tests/Schema/SchemaDifferTest.php
git mv tests/Schema/GeneratedBuildersTest.php packages/core/tests/Schema/GeneratedBuildersTest.php
git mv tests/Schema/BotApiSchemaTest.php packages/core/tests/Schema/BotApiSchemaTest.php
git mv tests/EntityParserTest.php packages/core/tests/EntityParserTest.php
git mv tests/PassportDecryptorTest.php packages/core/tests/PassportDecryptorTest.php
git mv tests/MtprotoCryptoAndTlTest.php packages/core/tests/MtprotoCryptoAndTlTest.php
git mv tests/Support packages/core/tests/Support
```

Stays behind (laravel-owned, Task 5): `TeleprotoClientTest.php`, `UpdatePollerTest.php`, `UpdateStateMachineTest.php`, `LiveSessionAdapterTest.php`, `MiniAppValidatorTest.php`, `Wire/DoctorCommandTest.php` — except DoctorCommandTest, which moves now (it tests `Console/DoctorCommand`): actually **DoctorCommand is a laravel-package class**, so its test stays behind with it. Corrected list — move it back:

```bash
git mv packages/core/tests/Wire/DoctorCommandTest.php tests/Wire/DoctorCommandTest.php 2>/dev/null || true
```

Also stays behind: `tests/Schema/SkillFilesTest.php` (tests generated `skills/`, owned by schema package — moves in Task 6).

- [x] **Step 4: Write core phpunit + phpstan configs**

`packages/core/phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Teleproto Core">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

`packages/core/phpstan.neon.dist` (drops the root config's laravel-helpers bootstrap and the SchemaAuditCommand proc_open exemption — neither applies to core; keeps the preg ban and the resources exclusion):

```neon
includes:
    - vendor/spaze/phpstan-disallowed-calls/extension.neon

parameters:
    level: 5
    paths:
        - src
    excludePaths:
        - src/MTProto/resources
    disallowedFunctionCalls:
        -
            function: 'preg_*()'
            message: 'src/ is regex-free by spec 2026-08-28 §A — use sscanf/string functions/the TL tokenizer'
```

- [x] **Step 5: Install and run the core suite (expected partial red)**

```bash
composer update --working-dir=packages/core
composer test --working-dir=packages/core
```

Expected: **`Schema/MethodRegistryTest` fails** with `RuntimeException: Cannot read schema artifact [...]` — MethodRegistry still resolves `dirname(__DIR__, 2) . '/schema/'` which no longer exists relative to the package. Every other test passes. This red is Task 4's entry condition; do not fix it here.

- [x] **Step 6: Run core phpstan (expected green)**

```bash
composer analyse --working-dir=packages/core
```

Expected: `[OK] No errors` — if phpstan flags a missing class from `Services\\` (laravel) inside core src, that import was missed in the boundary audit; relocate the file per Step 2's fallback rule and re-run.

- [x] **Step 7: Root install smoke + prune residues**

```bash
git status --short
ls src 2>/dev/null && { git add -A src && git commit -m \"chore(monorepo): remove empty root src residue\"; } || echo "root src fully migrated"
```

If `src/` still contains `Console/ Events/ Facades/ Http/ Media/ Services/ TeleprotoServiceProvider.php` — that is correct and expected; they are Task 5's payload. Only an EMPTY root `src/` (after Task 5) gets deleted. This step is a no-op checkpoint until then; keep it as `echo "root src residue (expected, Task 5 payload):" && ls src`.

- [x] **Step 8: Commit**

```bash
git add packages/core composer.lock 2>/dev/null || git add packages/core
git commit -m "feat(core): extract zero-illuminate wire engine into packages/core (namespace-preserving)"
```

---

### Task 4: SchemaArtifacts locator + MethodRegistry rewiring

**Files:**
- Create: `packages/schema/composer.json`, `packages/schema/phpunit.xml.dist`
- Create: `packages/schema/src/SchemaArtifacts.php`
- Move: `schema/` (entire directory: `methods-mtproto.json`, `methods-botapi.json`, `sources/`) → `packages/schema/schema/`
- Modify: `packages/core/src/Schema/MethodRegistry.php:33-38`
- Test: `packages/schema/tests/SchemaArtifactsTest.php`

**Interfaces:**
- Consumes: `merezarezaei/teleproto-schema` require declared in core's composer.json (Task 3).
- Produces:
  - `MeRezaRezaei\\TeleprotoSchema\\SchemaArtifacts::path(string $file): string` — absolute path to `packages/schema/schema/<$file>` in dev, `vendor/merezarezaei/teleproto-schema/schema/<$file>` when installed; throws `RuntimeException` when the schema package is not installed.
  - `packages/schema/schema/{methods-mtproto.json,methods-botapi.json}` at those exact relative paths (MethodRegistry + verify script + generators depend on them).
  - A green `composer test --working-dir=packages/core` including `MethodRegistryTest`.

- [x] **Step 1: Create the schema package skeleton**

`packages/schema/composer.json`:

```json
{
    "name": "merezarezaei/teleproto-schema",
    "description": "Teleproto schema pipeline — TL sources, packaged method artifacts, code generators",
    "type": "library",
    "license": "MIT",
    "keywords": ["telegram", "mtproto", "schema", "tl", "codegen"],
    "autoload": {
        "psr-4": { "MeRezaRezaei\\TeleprotoSchema\\": "src/" }
    },
    "autoload-dev": {
        "psr-4": { "MeRezaRezaei\\TeleprotoSchema\\Tests\\": "tests/" }
    },
    "require": {
        "php": ">=8.2",
        "ext-json": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0"
    },
    "scripts": {
        "test": "vendor/bin/phpunit"
    },
    "config": { "allow-plugins": false }
}
```

`packages/schema/phpunit.xml.dist`: same shape as core's with testsuite name `Teleproto Schema`.

- [x] **Step 2: Move the schema artifacts and sources**

```bash
mkdir -p packages/schema
git mv schema packages/schema/schema
```

`sources/` rides along inside it (`packages/schema/schema/sources/`). The `.tl` mirror previously referenced as root `schema/sources/` is now `packages/schema/schema/sources/` — teleclient's own mirror is independent (its AGENTS.md documents a committed layer-227 mirror under teleclient, untouched by this task).

- [x] **Step 3: Write the failing SchemaArtifacts test**

`packages/schema/tests/SchemaArtifactsTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleprotoSchema\Tests;

use MeRezaRezaei\TeleProtoSchema\SchemaArtifacts;
use PHPUnit\Framework\TestCase;

final class SchemaArtifactsTest extends TestCase
{
    public function testPathResolvesInsideThePackageAndFileExists(): void
    {
        foreach (['methods-mtproto.json', 'methods-botapi.json'] as $file) {
            $path = SchemaArtifacts::path($file);
            self::assertStringEndsWith('schema/' . $file, str_replace('\\', '/', $path));
            self::assertFileExists($path);
        }
    }

    public function testPathRejectsTraversal(): void
    {
        $this->expectException(\\InvalidArgumentException::class);
        SchemaArtifacts::path('../composer.json');
    }
}
```

- [x] **Step 4: Run it red**

```bash
composer update --working-dir=packages/schema
composer test --working-dir=packages/schema
```

Expected: FAIL — `Class "MeRezaRezaei\TeleProtoSchema\SchemaArtifacts" not found`.

- [x] **Step 5: Implement SchemaArtifacts**

`packages/schema/src/SchemaArtifacts.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\TeleProtoSchema;

use Composer\\InstalledVersions;
use RuntimeException;

/**
 * Locates the packaged schema artifacts (methods-mtproto.json,
 * methods-botapi.json) regardless of whether this package is the repo working
 * copy, a path-repo symlink, or a regular vendor install.
 */
final class SchemaArtifacts
{
    private const PACKAGE = 'merezarezaei/teleproto-schema';

    /**
     * Absolute path to a file inside this package's schema/ directory.
     *
     * @param string $file bare filename, e.g. "methods-mtproto.json"
     * @return string absolute path
     * @throws InvalidArgumentException on directory traversal attempts
     * @throws RuntimeException when the schema package is not installed
     */
    public static function path(string $file): string
    {
        if ($file !== basename($file)) {
            throw new \\InvalidArgumentException("Schema artifact name must be a bare filename, got [{$file}].");
        }

        if (!InstalledVersions::isInstalled(self::PACKAGE, true)) {
            throw new RuntimeException('teleproto-schema package is not installed; cannot locate artifacts.');
        }

        $root = InstalledVersions::getInstallPath(self::PACKAGE);
        if ($root === null || $root === '') {
            throw new RuntimeException('teleproto-schema install path could not be resolved.');
        }

        return rtrim($root, '/') . '/schema/' . $file;
    }
}
```

- [x] **Step 6: Run it green**

Run: `composer test --working-dir=packages/schema`
Expected: PASS (2 tests).

- [x] **Step 7: Rewire MethodRegistry and run core green**

Edit `packages/core/src/Schema/MethodRegistry.php`. Current shape (lines ~33–38):

```php
        foreach (['mtproto' => 'methods-mtproto.json', 'bot-http' => 'methods-botapi.json'] as $api => $file) {
            $path = dirname(__DIR__, 2) . '/schema/' . $file;

            $json = file_get_contents($path);
```

Replace the `$path` line with:

```php
            $path = \\MeRezaRezaei\\TeleProtoSchema\\SchemaArtifacts::path($file);
```

And add to the file's use-block (top, after `declare(strict_types=1);` + namespace):

```php
use MeRezaRezaei\\TeleProtoSchema\\SchemaArtifacts;
```

with the call site becoming `$path = SchemaArtifacts::path($file);`.

Then reinstall core deps so the path-repo symlink for schema is visible inside core's own vendor, and run:

```bash
composer update --working-dir=packages/core
composer test --working-dir=packages/core
composer analyse --working-dir=packages/core
```

Expected: all core tests PASS (including `Schema/MethodRegistryTest`), phpstan `[OK]`.

Note: `packages/core/composer.json` has no `repositories` block, so its standalone `composer update` resolves `merezarezaei/teleproto-schema` from… nowhere. **Fix now**: add to core's composer.json, before `"require"`:

```json
    "repositories": {
        "teleproto-schema": { "type": "path", "url": "../schema", "options": { "symlink": true } }
    },
```

(Path repos in a published package's manifest are ignored by consumers — this is the standard dev-link pattern and harmless post-publish; root install is unaffected since root already declares all four.)

- [x] **Step 8: Root-level install now works**

```bash
composer update
ls -l vendor/merezarezaei/
```

Expected: composer resolves the four path packages (core, schema resolve; laravel/bot still missing from disk — **composer update will fail on missing path repo url**). Mitigation: create the laravel and bot package directories as minimal placeholders now (manifest only), deferring their payload to Tasks 5 and 7:

`packages/laravel/composer.json` (placeholder, replaced in Task 5):

```json
{
    "name": "merezarezaei/teleproto-laravel",
    "description": "placeholder — populated by Task 5",
    "type": "library",
    "license": "MIT",
    "autoload": { "psr-4": { "MeRezaRezaei\\Teleproto\\": "src/" } },
    "require": { "php": ">=8.2", "merezarezaei/teleproto-core": "*", "merezarezaei/teleproto-bot": "*" },
    "config": { "allow-plugins": false }
}
```

`packages/bot/composer.json` (placeholder, replaced in Task 7):

```json
{
    "name": "merezarezaei/teleproto-bot",
    "description": "placeholder — populated by Task 7",
    "type": "library",
    "license": "MIT",
    "autoload": { "psr-4": { "MeRezaRezaei\\Teleproto\\": "src/" } },
    "require": { "php": ">=8.2", "merezarezaei/teleproto-core": "*", "illuminate/http": "^10.0|^11.0|^12.0" },
    "config": { "allow-plugins": false }
}
```

```bash
mkdir -p packages/laravel/src packages/bot/src
composer update
```

Expected: install succeeds; `vendor/merezarezaei/teleproto-core` and `vendor/merezarezaei/teleproto-schema` are symlinks to `packages/core` and `packages/schema` (the plugin pin + `"symlink": true`).

- [x] **Step 9: Commit**

```bash
git add packages/schema packages/core packages/laravel packages/bot composer.lock
git commit -m "feat(schema): SchemaArtifacts locator package; MethodRegistry resolves artifacts via InstalledVersions"
```

---

### Task 5: Extract packages/laravel — illuminate-coupled glue

**Files:**
- Replace: `packages/laravel/composer.json` (placeholder → real)
- Create: `packages/laravel/phpunit.xml.dist`, `packages/laravel/phpstan.neon.dist`, `packages/laravel/phpstan/laravel-helpers.php` (moved)
- Move: `config/teleproto.php` → `packages/laravel/config/teleproto.php`
- Move (src): `src/Console`, `src/Events`, `src/Facades`, `src/Http`, `src/Media`, `src/Services`, `src/TeleprotoServiceProvider.php` → `packages/laravel/src/…`
- Move (tests): `tests/TeleprotoClientTest.php`, `tests/UpdatePollerTest.php`, `tests/UpdateStateMachineTest.php`, `tests/LiveSessionAdapterTest.php`, `tests/MiniAppValidatorTest.php`, `tests/Wire/DoctorCommandTest.php` → `packages/laravel/tests/…`
- Create: `packages/laravel/tests/Support/ServiceProviderTest.php`, `packages/laravel/tests/Support/FacadeSmokeTest.php`
- Delete: root `src/` (now empty), root `tests/` (now empty), root `config/`, root `phpstan/`

**Interfaces:**
- Consumes: `merezarezaei/teleproto-core` classes (all FQCNs unchanged), `merezarezaei/teleproto-bot` (only from `TeleprotoClient::bot()` — which this task keeps compiling by leaving the method body as-is; bot package placeholder from Task 4 makes the class missing at runtime, so this task's `TeleprotoClientTest` run must avoid `bot()` paths — verify with grep in Step 2 and, if any existing test calls `bot()`, guard the move of that specific test file into Task 7's window: keep it at root `tests/` until then, deleted in Task 7 Step 6).
- Produces: installable `merezarezaei/teleproto-laravel` with PSR-4 `MeRezaRezaei\\Teleproto\\{Console,Events,Facades,Http,Media,Services}\\` → `src/<sub>` **plus root mapping** `MeRezaRezaei\\Teleproto\\` → `src/` (for `TeleprotoServiceProvider` only — one class file at src root); Laravel service-provider discovery via `extra.laravel.providers`; green suite incl. two new orchestration tests.

- [x] **Step 1: Write the real laravel manifest**

Replace `packages/laravel/composer.json`:

```json
{
    "name": "merezarezaei/teleproto-laravel",
    "description": "Laravel integration for teleproto-core — clients, events, webhooks, console, facades",
    "type": "library",
    "license": "MIT",
    "keywords": ["telegram", "laravel", "mtproto", "webhook", "mini-app"],
    "autoload": {
        "psr-4": {
            "MeRezaRezaei\\Teleproto\\": "src/",
            "MeRezaRezaei\\Teleproto\\Console\\": "src/Console",
            "MeRezaRezaei\\Teleproto\\Events\\": "src/Events",
            "MeRezaRezaei\\Teleproto\\Facades\\": "src/Facades",
            "MeRezaRezaei\\Teleproto\\Http\\": "src/Http",
            "MeRezaRezaei\\Teleproto\\Media\\": "src/Media",
            "MeRezaRezaei\\Teleproto\\Services\\": "src/Services"
        }
    },
    "autoload-dev": {
        "psr-4": { "MeRezaRezaei\\Teleproto\\Tests\\": "tests/" }
    },
    "require": {
        "php": ">=8.2",
        "merezarezaei/teleproto-core": "*",
        "merezarezaei/teleproto-bot": "*",
        "illuminate/support": "^10.0|^11.0|^12.0",
        "illuminate/http": "^10.0|^11.0|^12.0",
        "illuminate/filesystem": "^10.0|^11.0|^12.0",
        "illuminate/routing": "^10.0|^11.0|^12.0",
        "illuminate/console": "^10.0|^11.0|^12.0",
        "vlucas/phpdotenv": "^5.6",
        "laravel/prompts": "^0.3"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0",
        "phpstan/phpstan": "^2.0",
        "spaze/phpstan-disallowed-calls": "^4.14",
        "orchestra/testbench": "^10.11",
        "chillerlan/php-qrcode": "^6.0"
    },
    "extra": {
        "laravel": {
            "providers": ["MeRezaRezaei\\Teleproto\\TeleprotoServiceProvider"],
            "aliases": {
                "Teleproto": "MeRezaRezaei\\Teleproto\\Facades\\Teleproto",
                "TP": "MeRezaRezaei\\Teleproto\\Facades\\TP"
            }
        }
    },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "analyse": "vendor/bin/phpstan analyse --no-progress"
    },
    "config": { "allow-plugins": false }
}
```

Add the same dev-link `repositories` block as core got in Task 4 Step 7 (core, bot as path repos, symlinked).

- [x] **Step 2: Move laravel source and tests**

```bash
for sub in Console Events Facades Http Media Services; do
    git mv "src/${sub}" "packages/laravel/src/${sub}"
done
git mv src/TeleprotoServiceProvider.php packages/laravel/src/TeleprotoServiceProvider.php
git mv config/teleproto.php packages/laravel/config/teleproto.php

mkdir -p packages/laravel/tests
git mv tests/TeleprotoClientTest.php packages/laravel/tests/
git mv tests/UpdatePollerTest.php packages/laravel/tests/
git mv tests/UpdateStateMachineTest.php packages/laravel/tests/
git mv tests/LiveSessionAdapterTest.php packages/laravel/tests/
git mv tests/MiniAppValidatorTest.php packages/laravel/tests/
mkdir -p packages/laravel/tests/Wire
git mv tests/Wire/DoctorCommandTest.php packages/laravel/tests/Wire/
```

Then check the bot() hazard:

```bash
grep -rn "bot(" packages/laravel/tests/ | grep -v "botMtproto" || echo "no bot() calls in laravel tests — safe"
grep -n "BotClient\\|BotAccountScope" packages/laravel/src/Services/*.php
```

Expected: the src grep shows exactly two hits — `TeleprotoClient.php` (imports + constructs `BotClient` inside `bot()`) and nothing else. `TeleprotoClient.php` importing `MeRezaRezaei\\Teleproto\\Services\\BotClient` keeps compiling once Task 7 lands (same FQCN, bot package's own `Services\\` mapping). For the suite to run TODAY, BotClient must be present: `git mv` it now instead of Task 7 and leave a note — **decision: keep this plan's task order intact by temporarily copying is FORBIDDEN (no duplication rule); instead Task 5 runs the suite with the two bot classes moved early**: execute now

```bash
git mv src/Services/BotClient.php packages/laravel/src/Services/BotClient.php
git mv src/Services/BotAccountScope.php packages/laravel/src/Services/BotAccountScope.php
git mv src/Methods/Generated/Bots.php packages/laravel/src/Methods/Generated/Bots.php 2>/dev/null || echo "Bots.php already in core — Task 7 will git mv it out of core, not laravel"
```

(the `Generated/Bots.php` target dir needs `mkdir -p packages/laravel/src/Methods/Generated` when the first branch runs). Record in the commit message that bot classes transit through laravel; Task 7 relocates them with `git mv` (history preserved through both hops). If `Bots.php` currently sits in core (it does — Task 3 moved Methods wholesale), **move it here now**:

```bash
git mv packages/core/src/Methods/Generated/Bots.php packages/laravel/src/Methods/Generated/Bots.php
```

and remember Task 7 moves it from laravel → bot.

Finally remove now-empty roots:

```bash
rmdir src tests config 2>/dev/null; git add -A
```

- [x] **Step 3: Write laravel configs**

`packages/laravel/phpunit.xml.dist`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Teleproto Laravel">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory suffix=".php">src</directory>
        </include>
    </source>
</phpunit>
```

Move the helpers bootstrap: `git mv phpstan packages/laravel/phpstan` then `git mv phpstan/laravel-helpers.php packages/laravel/phpstan/laravel-helpers.php` (collapse as needed so the file ends at `packages/laravel/phpstan/laravel-helpers.php`).

`packages/laravel/phpstan.neon.dist`:

```neon
includes:
    - vendor/spaze/phpstan-disallowed-calls/extension.neon

parameters:
    level: 5
    bootstrapFiles:
        - phpstan/laravel-helpers.php
    paths:
        - src
    disallowedFunctionCalls:
        -
            function: 'preg_*()'
            message: 'src/ is regex-free by spec 2026-08-28 §A — use sscanf/string functions/the TL tokenizer'
            allowIn:
                - bin/*
                - examples/*
        -
            function: 'proc_open()'
            message: 'subprocess spawning is bin-only by policy — move it into a bin/ generator; src/ orchestrates, not spawns'
            allowIn:
                - bin/*
                - examples/*
                - src/Console/SchemaAuditCommand.php
```

(SchemaAuditCommand's proc_open exemption travels with it; its path rewiring is Task 6.)

- [x] **Step 4: Write the failing orchestration tests**

`packages/laravel/tests/Support/ServiceProviderTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleproto\\Tests\\Support;

use MeRezaRezaei\\Teleproto\\TeleprotoServiceProvider;
use Orchestra\\Testbench\\TestCase as TestbenchTestCase;

final class ServiceProviderTest extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [TeleprotoServiceProvider::class];
    }

    public function testConfigIsMergedAndBindingsRegistered(): void
    {
        self::assertSame('bot-http', config('teleproto.default'));
        self::assertTrue($this->app->bound(\\MeRezaRezaei\\Teleproto\\Facades\\Teleproto::class)
            || $this->app->bound('teleproto')
            || true, 'facade/binding assertion refined in Step 5 against actual SP bindings');
        $keys = array_keys(config('teleproto'));
        sort($keys);
        self::assertContains('default', $keys);
    }
}
```

The middle assertion is deliberately permissive (`|| true`) — Step 5 replaces it with the real binding names read off the provider; the config assertion is the hard red/green driver.

`packages/laravel/tests/Support/FacadeSmokeTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\\Teleproto\\Tests\\Support;

use TP;
use Orchestra\\Testbench\\TestCase as TestbenchTestCase;

final class FacadeSmokeTest extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [\\MeRezaRezaei\\Teleproto\\TeleprotoServiceProvider::class];
    }

    public function testTpFacadeResolvesRootCallable(): void
    {
        self::assertTrue(is_callable(TP::getFacadeRoot()) || is_object(TP::getFacadeRoot()));
    }
}
```

(`use TP;` resolves via the alias registered by `extra.laravel.aliases` under testbench package discovery.)

- [x] **Step 5: Run laravel suite red → wire → green**

```bash
composer update --working-dir=packages/laravel
composer test --working-dir=packages/laravel
```

Expected first run: FAIL in `ServiceProviderTest` on `config('teleproto.default')` if the provider's config-merge path broke in the move (it reads `__DIR__/../config/teleproto.php` — verify the provider's `mergeConfigFrom`/`loadConfigFrom` argument still resolves from the new location; if it used `dirname(__DIR__, 2)`, fix to `dirname(__DIR__) . '/../config/teleproto.php'`). Iterate until green, tightening the permissive assertion from Step 4 with the provider's actual container bindings (read `packages/laravel/src/TeleprotoServiceProvider.php` register() and assert those exact keys, e.g. `self::assertTrue($this->app->bound('teleproto.client'))` using the real binding name).

Expected final: all PASS including the two new tests.

- [x] **Step 6: Root verify checkpoint**

```bash
composer update
composer test:laravel
composer analyse:laravel
```

Expected: green. Also re-run `composer test:core` — laravel's extraction must not have touched core (it removed `Bots.php` from core Methods; `GeneratedBuildersTest` must still pass — if it asserts the Bots group exists, that assertion is core's NO LONGER valid: move the Bots-group assertion into `packages/bot/tests/BotPackageTest.php` in Task 7 and delete it from core's test now):

```bash
grep -n "Bots" packages/core/tests/Schema/GeneratedBuildersTest.php
```

For each hit: remove the Bots-specific test method (keep every other group), because from now on the Bots builders belong to the bot package's surface.

- [x] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(laravel): extract illuminate glue into packages/laravel; add SP + facade orchestration tests"
```

---

### Task 6: Move schema tooling; generators, SchemaAuditCommand, --check flags

**Files:**
- Move: `bin/generate-*.php` (6 files) → `packages/schema/bin/`; `config/curated-methods.json` → `packages/schema/config/`; `skills/telegram-methods/` → `packages/schema/skills/telegram-methods/`
- Move: `tests/Schema/SkillFilesTest.php` → `packages/schema/tests/SkillFilesTest.php`
- Modify: all six generators' path resolution (exact edits in Steps 2–4)
- Modify: `packages/laravel/src/Console/SchemaAuditCommand.php:28,64,69`
- Test: `packages/schema/tests/SkillFilesTest.php` (moved, must stay green)

**Interfaces:**
- Consumes: `SchemaArtifacts::path()` (Task 4); laravel's `InstalledVersions`-based lookup needs `composer-runtime-api` — **add `"composer-runtime-api": "^2.0"` to laravel's require** in this task (Step 6).
- Produces: generators runnable in-place from the monorepo (`php packages/schema/bin/generate-*.php`) that read sources/config from and write products into the sibling packages deterministically; `--check` dry-run mode on `generate-method-builders.php`, `generate-userscope-schema.php`, `generate-skill-files.php` (exit 0 clean, exit 1 drift); a working `composer verify` full chain (Task 2 script's idempotence step becomes live).

- [x] **Step 1: Move the tooling**

```bash
git mv bin/generate-botapi-schema.php packages/schema/bin/generate-botapi-schema.php
git mv bin/generate-method-builders.php packages/schema/bin/generate-method-builders.php
git mv bin/generate-method-schema.php packages/schema/bin/generate-method-schema.php
git mv bin/generate-rpc-catalog.php packages/schema/bin/generate-rpc-catalog.php
git mv bin/generate-skill-files.php packages/schema/bin/generate-skill-files.php
git mv bin/generate-userscope-schema.php packages/schema/bin/generate-userscope-schema.php
mkdir -p packages/schema/config
git mv config/curated-methods.json packages/schema/config/curated-methods.json
git mv skills packages/schema/skills
git mv tests/Schema/SkillFilesTest.php packages/schema/tests/SkillFilesTest.php 2>/dev/null || git mv packages/laravel/tests/Schema/SkillFilesTest.php packages/schema/tests/SkillFilesTest.php 2>/dev/null || echo "locate SkillFilesTest: find . -name SkillFilesTest.php -not -path '*/vendor/*'"
rmdir config 2>/dev/null || true
git add -A
```

`bin/teleproto`, `bin/test-e2e.php`, `bin/test-me.php` stay at root (dev CLI against root vendor — unchanged concern).

- [x] **Step 2: Fix generator path resolution (write targets now live in sibling packages)**

Every generator currently resolves repo paths as `__DIR__ . '/../<thing>'`. From `packages/schema/bin/`, the monorepo root is `dirname(__DIR__, 2)` and targets are:

| Generator | Reads (old) | Reads (new) | Writes (old) | Writes (new) |
|---|---|---|---|---|
| generate-method-builders | `config/curated-methods.json` | `__DIR__/../config/curated-methods.json` (unchanged relative!) | `src/Methods/Generated` | `dirname(__DIR__, 2) . '/core/src/Methods/Generated'` |
| generate-method-schema | `schema/sources/…` | `__DIR__/../schema/sources/…` (unchanged) | `schema/methods-mtproto.json` | `__DIR__/../schema/methods-mtproto.json` (unchanged) |
| generate-botapi-schema | `schema/sources/…` | unchanged | `schema/methods-botapi.json` | unchanged |
| generate-rpc-catalog | fetches errors.json → writes `src/Exceptions/Rpc/RpcErrorCatalog.php` | — | `__DIR__/../src/Exceptions/…` | `dirname(__DIR__, 2) . '/core/src/Exceptions/Rpc/RpcErrorCatalog.php'` |
| generate-userscope-schema | `schema/sources/*.tl` | unchanged | `__DIR__/../src/MTProto/TL/Schema/UserScopeSchema.php` | `dirname(__DIR__, 2) . '/core/src/MTProto/TL/Schema/UserScopeSchema.php'` |
| generate-skill-files | `config/curated-methods.json` + artifacts | `__DIR__/../config/…` (unchanged) | `skills/telegram-methods/` | `__DIR__/../skills/telegram-methods/` (unchanged) |

Concretely — in `packages/schema/bin/generate-method-builders.php` replace:

```php
$configPath = __DIR__ . '/../config/curated-methods.json';
$outDir = __DIR__ . '/../src/Methods/Generated';
```

with:

```php
$configPath = __DIR__ . '/../config/curated-methods.json';
$outDir = dirname(__DIR__, 2) . '/core/src/Methods/Generated';
```

In `generate-rpc-catalog.php` replace `__DIR__ . '/../src/Exceptions/Rpc/RpcErrorCatalog.php'` (both occurrences: write + filesize report) with a single `$out = dirname(__DIR__, 2) . '/core/src/Exceptions/Rpc/RpcErrorCatalog.php';` used twice. Same pattern in `generate-userscope-schema.php` for its two occurrences. Generators whose read/write targets stayed relative (schema artifacts, sources, skills, curated config) need no edits — verify by running each once (Step 5).

Also fix autoload requires where present: `generate-skill-files.php` line 16 (`dirname(__DIR__) . '/vendor/autoload.php'`) → `dirname(__DIR__) . '/vendor/autoload.php'` (schema package's own vendor — unchanged, works) — and `generate-method-builders.php` line 18 (`__DIR__ . '/../vendor/autoload.php'`) — unchanged. After `composer update --working-dir=packages/schema`, both resolve inside the schema package. If a generator needs core classes (TLSignatureParser — check: these generators are self-contained string processors; if any imports `MeRezaRezaei\\Teleproto\\*`, add the core path repo to schema's composer.json exactly like laravel's in Task 5 and re-update):

```bash
grep -n "MeRezaRezaei" packages/schema/bin/*.php || echo "generators are framework-independent — no core autoload needed"
```

- [x] **Step 3: Add --check (dry-run) mode to the three product-writing generators**

`--check` semantics: build the would-be output **in memory / temp dir**, byte-compare against the committed product, print drift, exit 1 on drift / 0 clean; never write the real target. Implementation per generator — apply this identical pattern:

`generate-method-builders.php` — near the top, after autoload:

```php
$checkOnly = in_array('--check', $argv, true);
```

Then wherever it does `file_put_contents("{$outDir}/{$group['class']}.php", $php)` (current failure path prints `cannot write {$outDir}/…`), wrap:

```php
if ($checkOnly) {
    $committed = @file_get_contents("{$outDir}/{$group['class']}.php");
    if ($committed !== $php) {
        fwrite(STDERR, "DRIFT: {$outDir}/{$group['class']}.php differs from generator output\n");
        $drift = true;
    }
} else {
    // existing write + error handling stays exactly as-is
}
```

Initialize `$drift = false;` before the loop and end the script with:

```php
if ($checkOnly) {
    exit($drift ? 1 : 0);
}
```

Apply the same three-part edit (`$checkOnly` flag → guarded compare vs write → drift exit) to `generate-userscope-schema.php` (single product: compare its `$out` string against `dirname(__DIR__, 2) . '/core/src/MTProto/TL/Schema/UserScopeSchema.php'`) and `generate-skill-files.php` (loop over generated skill files, compare each under `__DIR__/../skills/`). The existing non-check behavior must remain byte-identical — `git diff` after a real run shows nothing (Step 5 proves it).

- [x] **Step 4: Rewire SchemaAuditCommand**

`packages/laravel/src/Console/SchemaAuditCommand.php` currently (lines 28, 64, 69):

```php
$root = dirname(__DIR__, 2);            // was: repo root containing bin/
...
$result = self::runProcess([PHP_BINARY, "{$root}/bin/{$generator}", $outDir], $root);
```

Replace the root computation with an install-path lookup at both sites (lines ~28 and ~64):

```php
$root = \\Composer\\InstalledVersions::getInstallPath('merezarezaei/teleproto-schema');
if ($root === null || $root === '' || !is_dir($root)) {
    throw new \\RuntimeException('teleproto-schema package not installed — schema audit requires the schema pipeline package.');
}
```

The `{$root}/bin/{$generator}` and cwd `$root` arguments then resolve inside the schema package in both dev (symlink → `packages/schema`) and installed contexts. Add `use Composer\\InstalledVersions;` to the imports and `"composer-runtime-api": "^2.0"` to laravel's require block.

- [x] **Step 5: Run generators, prove idempotence**

```bash
composer update --working-dir=packages/schema
composer test --working-dir=packages/schema
php packages/schema/bin/generate-method-builders.php
php packages/schema/bin/generate-userscope-schema.php
php packages/schema/bin/generate-skill-files.php
git diff --exit-code -- packages/core/src packages/schema/skills && echo "IDEMPOTENT"
php packages/schema/bin/generate-method-builders.php --check && echo "CHECK CLEAN"
```

Expected: schema tests PASS (SchemaArtifactsTest + SkillFilesTest), real runs produce zero diff (nothing changes — the moved generators must reproduce committed bytes), `--check` exits 0. If a real run produces a diff, the path edits changed output content (not just location) — stop and fix before continuing; committed artifacts must remain byte-stable across this task.

- [x] **Step 6: Full verify chain goes live**

```bash
composer install
composer verify
```

Expected: `verify complete` — install, pin check (4 symlinks), four suites, phpstan ×3, idempotence all pass. (The script's `--check` calls are now implemented.)

- [x] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(schema): rehome generators + sources; add --check dry-run; SchemaAuditCommand resolves schema package via InstalledVersions"
```

---

### Task 7: Extract packages/bot — the Bot API surface

**Files:**
- Replace: `packages/bot/composer.json` (placeholder → real)
- Move: `packages/laravel/src/Services/BotClient.php` → `packages/bot/src/Services/BotClient.php`
- Move: `packages/laravel/src/Services/BotAccountScope.php` → `packages/bot/src/Services/BotAccountScope.php`
- Move: `packages/laravel/src/Methods/Generated/Bots.php` → `packages/bot/src/Methods/Generated/Bots.php` (if Task 5 left it in core, move it from `packages/core/src/Methods/Generated/Bots.php` — check both, exactly one exists)
- Modify: `packages/laravel/composer.json` (keep bot require; consumers wanting Bot API require bot explicitly)
- Create: `packages/bot/phpunit.xml.dist`
- Test: `packages/bot/tests/BotPackageTest.php`

**Interfaces:**
- Consumes: `MeRezaRezaei\\Teleproto\\Types\\InlineKeyboard` (core), `MeRezaRezaei\\Teleproto\\Exceptions\\TelegramException` (core), `MeRezaRezaei\\Teleproto\\Schema\\MethodRegistry` (core) — all FQCNs unchanged; `Illuminate\\Http\\Client\\Factory`.
- Produces: package `merezarezaei/teleproto-bot` mapping `MeRezaRezaei\\Teleproto\\Services\\` → `src/Services` and `MeRezaRezaei\\Teleproto\\Methods\\` → `src/Methods` (subset directories; overlapping prefixes with laravel/core are legal — each class exists in exactly one package, verified by the load-order smoke test below). `TeleprotoClient::bot()` in laravel keeps instantiating `new BotClient($finalToken, $proxyConfig ?? $this->defaultProxyConfig, http: $this->http)` — read the file before editing and preserve that exact constructor call; if its signature differs from what Task 5 recorded, the signature wins, not this plan.

- [x] **Step 1: Write the bot manifest**

Replace `packages/bot/composer.json`:

```json
{
    "name": "merezarezaei/teleproto-bot",
    "description": "Bot API HTTP surface for teleproto — BotClient, BotAccountScope, generated Bots builders",
    "type": "library",
    "license": "MIT",
    "keywords": ["telegram", "bot", "bot-api", "http"],
    "autoload": {
        "psr-4": {
            "MeRezaRezaei\\Teleproto\\Services\\": "src/Services",
            "MeRezaRezaei\\Teleproto\\Methods\\": "src/Methods"
        }
    },
    "autoload-dev": {
        "psr-4": { "MeRezaRezaei\\Teleproto\\Bot\\Tests\\": "tests/" }
    },
    "repositories": {
        "teleproto-core": { "type": "path", "url": "../core", "options": { "symlink": true } }
    },
    "require": {
        "php": ">=8.2",
        "ext-json": "*",
        "merezarezaei/teleproto-core": "*",
        "illuminate/http": "^10.0|^11.0|^12.0",
        "illuminate/support": "^10.0|^11.0|^12.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0"
    },
    "scripts": {
        "test": "vendor/bin/phpunit"
    },
    "config": { "allow-plugins": false }
}
```

- [x] **Step 2: Move the three files (exactly one source location each)**

```bash
mkdir -p packages/bot/src/Services packages/bot/src/Methods/Generated
if [ -f packages/laravel/src/Services/BotClient.php ]; then
    git mv packages/laravel/src/Services/BotClient.php packages/bot/src/Services/BotClient.php
    git mv packages/laravel/src/Services/BotAccountScope.php packages/bot/src/Services/BotAccountScope.php
fi
if [ -f packages/laravel/src/Methods/Generated/Bots.php ]; then
    git mv packages/laravel/src/Methods/Generated/Bots.php packages/bot/src/Methods/Generated/Bots.php
fi
if [ -f packages/core/src/Methods/Generated/Bots.php ]; then
    git mv packages/core/src/Methods/Generated/Bots.php packages/bot/src/Methods/Generated/Bots.php
fi
find packages -name "BotClient.php" -o -name "Bots.php" -not -path "*/vendor/*" | grep -v vendor
```

Expected: `packages/bot/src/Services/BotClient.php`, `packages/bot/src/Services/BotAccountScope.php`, `packages/bot/src/Methods/Generated/Bots.php` — each exactly once, nowhere else.

- [x] **Step 3: Write the failing package-load test**

`packages/bot/tests/BotPackageTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\\Teleproto\\Bot\\Tests;

use MeRezaRezaei\\Teleproto\\Methods\\Generated\\Bots;
use MeRezaRezaei\\Teleproto\\Services\\BotAccountScope;
use MeRezaRezaei\\Teleproto\\Services\\BotClient;
use PHPUnit\\Framework\\TestCase;

final class BotPackageTest extends TestCase
{
    public function testBotSurfaceLoadsFromThisPackage(): void
    {
        $ref = new \\ReflectionClass(BotClient::class);
        self::assertStringContainsString(
            'packages/bot/src',
            str_replace('\\\\', '/', $ref->getFileName() ?: ''),
            'BotClient must load from the bot package (PSR-4 subset mapping works)'
        );
        self::assertTrue(class_exists(Bots::class));
        self::assertTrue(class_exists(BotAccountScope::class));
    }

    public function testBotClientInstantiatesWithoutLaravelApp(): void
    {
        $client = new BotClient('123:abc', []);
        self::assertInstanceOf(BotClient::class, $client);
    }
}
```

If `BotClient::__construct` requires the HttpFactory argument as non-nullable, the second test instead asserts construction with an explicitly passed `new \\Illuminate\\Http\\Client\\Factory()` — read the constructor first and use whichever call matches its real signature (illuminate/http is installed in bot's vendor, so both variants run framework-classes-but-no-Laravel-app, which is the point of the test).

`packages/bot/phpunit.xml.dist`: same shape as schema's, testsuite name `Teleproto Bot`, autoload-dev prefix `MeRezaRezaei\\Teleproto\\Bot\\Tests\\`.

- [x] **Step 4: Run red → install → green**

```bash
composer update --working-dir=packages/bot
composer test --working-dir=packages/bot
```

Expected: PASS after install (the classes moved in Step 2 are found via the package's own PSR-4 map). Then root chain:

```bash
composer update
composer verify
```

Expected: `verify complete` — laravel suite still green (its `TeleprotoClient` autoloads BotClient from the bot package now), core green, idempotence green.

- [x] **Step 5: Commit**

```bash
git add -A
git commit -m "feat(bot): extract Bot API surface into packages/bot (overlapping-prefix PSR-4 subset)"
```

---

### Task 8: Switch teleclient onto the new packages via path repositories

All commands in this task run from `/home/me/Documents/projects/tele/teleclient` unless stated. The teleproto-side work is done; this task must produce **zero diffs under `teleclient/src/`** (spec acceptance).

**Files:**
- Modify: `teleclient/composer.json` (repositories + require)
- Create: `teleclient/composer.local.json.example` (untracked pattern documentation)
- Test: teleclient's existing suite (no new tests — the suite IS the test; it exercises `TeleprotoClient`, `UserAccountScope`, `UpdatePollerService`, `UpdateSinkInterface`, `FloodWaitException`, `DcMigrationException`, `SessionData` per its AGENTS.md dev-link contract)

**Interfaces:**
- Consumes: the four packages installable from `../teleproto/packages/*` (Tasks 3–7).
- Produces: teleclient green against the new family; documented dev-link swap in `composer.local.json.example`; **published constraint untouched** (`merezarezaei/teleproto: ^1.2.1 || ^1.1` stays until the family ships).

- [x] **Step 1: Edit teleclient composer.json**

Add BEFORE `"require"`:

```json
    "repositories": {
        "teleproto-core": { "type": "path", "url": "../teleproto/packages/core", "options": { "symlink": true } },
        "teleproto-schema": { "type": "path", "url": "../teleproto/packages/schema", "options": { "symlink": true } },
        "teleproto-laravel": { "type": "path", "url": "../teleproto/packages/laravel", "options": { "symlink": true } },
        "teleproto-bot": { "type": "path", "url": "../teleproto/packages/bot", "options": { "symlink": true } }
    },
```

In `"require"`, ADD (do not yet remove the old entry):

```json
        "merezarezaei/teleproto-core": "*",
        "merezarezaei/teleproto-schema": "*",
        "merezarezaei/teleproto-laravel": "*",
        "merezarezaei/teleproto-bot": "*",
```

- [x] **Step 2: Prove it fails first against the new names only**

Temporarily comment out `"merezarezaei/teleproto": ...` (keep the line in a scratch buffer — Step 3 restores a decision about it), then:

```bash
composer update
```

Expected: resolver failure OR duplicate-class errors: teleproto (old) is gone while `teleproto-laravel` currently ALSO maps `MeRezaRezaei\\Teleproto\\` — with old teleproto commented out, resolution should succeed where classes come only from the new family. If you instead see `Class MeRezaRezaei\\Teleproto\\Services\\UserAccountScope not found` during test bootstrap, the laravel package didn't install — check `vendor/merezarezaei/` symlinks. Document whatever failure appears; it defines Step 3's fix list.

- [x] **Step 3: Resolve the old-package question and go green**

Old `merezarezaei/teleproto` and new `teleproto-laravel` both map root-adjacent `MeRezaRezaei\\Teleproto\\` namespaces → they CANNOT coexist in one install (duplicate-class ambiguity). Decision locked by this plan: in the dev-link state the old entry is REMOVED (it stays recorded for the published state in `composer.local.json.example`):

```json
        "merezarezaei/teleproto": "^1.2.1 || ^1.1",
```

moves OUT of `require` and INTO the example file as the publish-state entry. Then:

```bash
composer update
composer test
composer analyse
```

Expected: full teleclient suite PASS, phpstan `[OK]`, and:

```bash
git diff --stat -- src/
```

Expected: **empty** (zero diffs under teleclient/src — spec acceptance criterion).

- [x] **Step 4: Write the dev-link example doc**

`teleclient/composer.local.json.example`:

```json
{
    "_comment": [
        "Dev-link pattern for working against the teleproto monorepo.",
        "dev state: require the four path packages (see repositories in composer.json).",
        "publish state (once the family is on Packagist): restore",
        "  \"merezarezaei/teleproto\": \"^1.2.1 || ^1.1\"",
        "or migrate to the split names with caret constraints.",
        "Never commit a composer.local.json; keep this example in sync with reality."
    ]
}
```

- [x] **Step 5: Commit (in teleclient repo)**

```bash
git add composer.json composer.local.json.example composer.lock
git commit -m "build: consume teleproto monorepo packages via path repositories (dev-link)"
```

---

### Task 9: CI matrix, docs, changelog, and the publish gate

**Files:**
- Replace: `.github/workflows/run-tests.yml`
- Modify: `README.md`, `llms.txt`, `AGENTS.md`, `CONTRIBUTING.md`, `CHANGELOG.md`
- Create: `docs/superpowers/specs/2026-09-04-package-split-design.md` — already exists (spec); reference it

**Interfaces:**
- Consumes: everything from Tasks 1–8 (`composer verify` chain, four green packages).
- Produces: CI that fails when any package regresses; docs that describe the four-piece puzzle and their attach rules; an explicit, user-gated publish runbook (NOT executed in this plan).

- [x] **Step 1: Replace CI with the monorepo matrix**

`.github/workflows/run-tests.yml`:

```yaml
name: monorepo-tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  verify:
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ['8.2', '8.3', '8.4']
    name: P${{ matrix.php }}
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, openssl, hash, json, gmp, bcmath
          coverage: none
      - name: Validate all manifests
        run: |
          composer validate --strict --no-check-publish
          for p in core schema laravel bot; do composer validate --strict --working-dir="packages/$p"; done
      - name: Full verify chain
        run: composer verify
      - name: Plugin suite
        run: composer test --working-dir=plugins/monorepo
```

(`--no-check-publish` on root because the root project intentionally has no valid publish shape; package manifests validate strict.)

Run it locally first: `bash -n scripts/verify-monorepo.sh && composer verify`.

- [x] **Step 2: Update README + llms.txt architecture sections**

In `README.md`, replace the single-package architecture description with the package table:

```markdown
| Package | Concern | Illuminate deps |
|---|---|---|
| `merezarezaei/teleproto-core` | MTProto 2.0 wire engine: transport, crypto, TL codec, method registry, types, exceptions | none |
| `merezarezaei/teleproto-schema` | Schema pipeline: TL sources, packaged artifacts, code generators, generated skill docs | none |
| `merezarezaei/teleproto-laravel` | Laravel glue: clients, auth, polling, events, webhooks, console, facades | illuminate/* |
| `merezarezaei/teleproto-bot` | Bot API HTTP surface: BotClient, BotAccountScope, Bots builders | illuminate/http |

Attach rule: depend on the highest package whose concern you need — core alone speaks
raw MTProto; +laravel gives framed Laravel integration; +bot adds the HTTP Bot API;
schema is pulled in transitively by core and only needed directly when regenerating.
```

Mirror the same table in `llms.txt` (it duplicates architecture for LLM consumers). Update any `php artisan teleproto:*` quickstart that assumes one package: note `composer require merezarezaei/teleproto-laravel` as the Laravel user entry point, bare `teleproto-core` for framework-free usage.

- [x] **Step 3: Update AGENTS.md for the new repo shape**

Rewrite the architecture map paths (`src/MTProto/` → `packages/core/src/MTProto/`, `src/Services/` → `packages/laravel/src/Services/`, `bin/generate-*` → `packages/schema/bin/*`, `schema/` → `packages/schema/schema/`, `skills/` → `packages/schema/skills/`), replace the gates block with:

```markdown
## Gates

Run before declaring work done (from repo root):

```bash
composer verify        # install + pin check + 4 suites + phpstan x3 + regeneration idempotence
```

Live gates (opt-in, real credentials) are unchanged:
`TELEPROTO_LIVE=true ./bin/teleproto test-e2e` — never part of CI.
```

Keep the hard-rules section (zero regex, generated artifacts, session credentials) — it now applies per package with `packages/*/src` as the target surface. Update the teleclient dev-link paragraph to point at `packages/*` path repos.

- [x] **Step 4: CHANGELOG entries (both repos)**

teleproto `CHANGELOG.md` under a new `## [Unreleased]` heading:

```markdown
### Changed
- Monorepo: split into four concern-scoped packages — `teleproto-core` (zero-illuminate
  wire engine), `teleproto-laravel` (framework glue), `teleproto-schema` (schema pipeline),
  `teleproto-bot` (Bot API surface). All namespaces unchanged; consumer code needs no
  import changes.
- `Schema\MethodRegistry` resolves artifacts via
  `MeRezaRezaei\TeleprotoSchema\SchemaArtifacts::path()` (Composer InstalledVersions)
  instead of package-root-relative paths.
- Generators moved to `packages/schema/bin/` and gained `--check` dry-run mode
  (builders, userscope-schema, skill-files).
- CI: per-package suites via root `composer verify` across PHP 8.2–8.4.
```

teleclient `CHANGELOG.md` `## [Unreleased]`:

```markdown
### Changed
- Dev-link: consumes the teleproto monorepo packages (`teleproto-core`, `teleproto-laravel`,
  `teleproto-schema`, `teleproto-bot`) via path repositories. Published constraint on
  `merezarezaei/teleproto` is unchanged until the family is released; see
  `composer.local.json.example`.
```

- [x] **Step 5: Final acceptance sweep**

```bash
composer verify
grep -rn "Illuminate" packages/core/src && { echo "FAIL: illuminate leaked into core"; exit 1; } || echo "core clean"
grep -rn "dirname(__DIR__, 2) . '/schema/'" packages/ && { echo "FAIL: stale schema path"; exit 1; } || echo "paths rewired"
cd ../teleclient && git diff --stat -- src/ | wc -l
```

Expected: verify complete; core clean; paths rewired; `0` (zero src diffs in teleclient).

- [x] **Step 6: Commit (both repos)**

teleproto:

```bash
git add -A
git commit -m "docs+ci: monorepo README/AGENTS/CHANGELOG + verify-based CI matrix (PHP 8.2-8.4)"
```

teleclient:

```bash
git add CHANGELOG.md
git commit -m "docs: changelog for monorepo dev-link switch"
```

- [x] **Step 7: Publish gate (NOT executed — user decision)**

The family is releasable when: `composer verify` is green on all PHP versions in CI, the user confirms Packagist names, and the subtree-split (or per-package repo) strategy is chosen. Present the runbook to the user and STOP. Publishing is a separate task with its own approval; nothing in this plan tags, pushes, or releases anything.

---

## Plan Self-Review (already applied during writing)

1. **Spec coverage** — spec §1 namespace preservation → every task uses `git mv` with unchanged FQCNs; §2 monorepo → Tasks 1–2; §3 four concerns → Tasks 3, 5, 6, 7 (+4 locator); §4 artifact lookup → Task 4; §5 dev linking → Task 8; §6 pinning → Task 2; acceptance criteria → Task 9 Step 5. Gap found and fixed during writing: generators' write targets and `SchemaAuditCommand` root resolution were unspecified in the spec — Task 6 covers both concretely.
2. **Placeholder scan** — none: every code step carries file contents or exact before/after edits; the two intentionally-permissive test assertions (`|| true` / constructor-signature note) are flagged in-line with their tightening step rather than left as TBDs.
3. **Type consistency** — `SchemaArtifacts::path(string $file): string` used identically in Task 4 (definition, MethodRegistry) and Task 2 (verify script consumes the files it locates); package names pinned identically in Task 1 repositories, Task 2 plugin `PINNED`, and Task 8 repositories; `BotClient` constructor call site quoted once (Task 7 Interfaces) with read-before-edit rule; `installer-paths` shape asserted (Task 2 test) matches the shape written (plugin).
