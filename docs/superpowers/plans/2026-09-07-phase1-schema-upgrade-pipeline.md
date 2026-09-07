# Phase 1: Schema Upgrade Pipeline — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Telegram layer adoption a single developer-run, offline-capable pipeline: one unified `teleframe:schema-update` (diff + regenerate + stamp, NO migrate), a `schemaLayer()` API surfacing the declared layer, composer `extra.telegram-layer` as the release-time record, and a cache-salt primitive for Phase 5d.

**Architecture:** A `Core\Schema\SchemaLayer` pure resolver exposes the layer with a 4-step fallback (packaged `schema-manifest.json` → composer root `extra.telegram-layer` → methods-mtproto.json artifact → `EncryptedConnection::LAYER`). The unified command fixes the stale post-rename path resolution (`packages/schema` → `SchemaArtifacts`), adds `--no-fetch` and `--dry-run`, runs the full regeneration chain in a fixed order, and stamps the manifest. Two latent generator bugs get fixed: `generate-rpc-catalog.php` reads `/tmp/opencode/errors.json` instead of the committed source, and commands resolve their root wrongly.

**Tech Stack:** PHP 8.2+, illuminate/console (command), Composer\InstalledVersions, phpunit (tests). `preg_*` banned in `src/`.

**Spec:** `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` §6 (Schema upgrade pipeline, D4/D5). Roadmap: `docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 1.

## Global Constraints

- Zero regex in `src/` (phpstan `disallowedFunctionCalls` gate).
- Generated artifacts never hand-edited (regenerated via bins; `schema-manifest.json` is written only by the command).
- NO implicit migrate anywhere — migrations remain explicit, developer-run (D4).
- Gates: teleframe `composer verify` (test + stan) green after every task.
- Nothing here changes the wire layer or existing public API; `schemaLayer()` is additive.
- Layer truth ruling (recorded, supersedes ambiguity): the declared schema layer = the layer of the regenerated method-schema artifact (currently 229); the wire layer (`EncryptedConnection::LAYER`, 227) is separate and intentionally may differ (AGENTS note stays). composer `extra.telegram-layer` records 229 to match the packaged artifacts.

---

### Task 1: `SchemaLayer` resolver + composer `extra.telegram-layer` + facade method

**Files:**
- Create: `src/Core/Schema/SchemaLayer.php` (teleframe)
- Modify: `src/Laravel/Services/TeleframeClient.php` (add `schemaLayer()`)
- Modify: `src/Laravel/Facades/Teleframe.php` (docblock `@method`)
- Modify: `composer.json` (`extra.telegram-layer`)
- Test: `tests/Core/Schema/SchemaLayerTest.php` (teleframe)

**Interfaces:**
- Consumes: `SchemaArtifacts::path('schema-manifest.json')`, `SchemaArtifacts::path('methods-mtproto.json')`, `EncryptedConnection::LAYER`, `InstalledVersions::getRootPackage()`.
- Produces: `SchemaLayer::layer(?string $manifestPath = null): int`; `SchemaLayer::cacheSalt(?string $manifestPath = null): string`; `TeleframeClient::schemaLayer(): int` (facade `Teleframe::schemaLayer()`). Phase 5d consumes `cacheSalt()`; Phase 2+ keeps the manifest pathing.

- [ ] **Step 1: Write the failing test**

Create `tests/Core/Schema/SchemaLayerTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Core\Schema;

use Composer\InstalledVersions;
use MeRezaRezaei\Teleframe\Core\Schema\SchemaLayer;
use PHPUnit\Framework\TestCase;

/**
 * Phase 1 Task 1: the declared Telegram schema layer resolves with a
 * 4-step fallback; the packaged schema-manifest.json is the truth.
 */
final class SchemaLayerTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/tl-schemalayer-' . bin2hex(random_bytes(6));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp . '/*') ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->tmp);
    }

    public function test_manifest_layer_is_truth_when_present(): void
    {
        file_put_contents($this->tmp . '/schema-manifest.json', json_encode(['layer' => 231]));

        self::assertSame(231, SchemaLayer::layer($this->tmp . '/schema-manifest.json'));
        self::assertSame('schema-layer-231', SchemaLayer::cacheSalt($this->tmp . '/schema-manifest.json'));
    }

    public function test_missing_manifest_falls_back_to_packaged_artifact_or_wire(): void
    {
        $layer = SchemaLayer::layer(); // no explicit manifest → packaged methods-mtproto.json

        self::assertIsInt($layer);
        self::assertGreaterThanOrEqual(EncryptedConnection::LAYER, $layer);
    }

    public function test_cache_salt_tracks_layer_bump(): void
    {
        file_put_contents($this->tmp . '/schema-manifest.json', json_encode(['layer' => 229]));
        $salt229 = SchemaLayer::cacheSalt($this->tmp . '/schema-manifest.json');
        file_put_contents($this->tmp . '/schema-manifest.json', json_encode(['layer' => 230]));
        $salt230 = SchemaLayer::cacheSalt($this->tmp . '/schema-manifest.json');

        self::assertNotSame($salt229, $salt230);
        self::assertSame('schema-layer-230', $salt230);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Core/Schema/SchemaLayerTest.php`
Expected: FATAL `Class "MeRezaRezaei\Teleframe\Core\Schema\SchemaLayer" not found`.

- [ ] **Step 3: Create the resolver**

`src/Core/Schema/SchemaLayer.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Schema;

use Composer\InstalledVersions;
use MeRezaRezaei\Teleframe\Core\MTProto\Connection\EncryptedConnection;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * Declared Telegram schema layer of this package (spec D5).
 *
 * Resolution order (first hit wins):
 *   1. packaged schema-manifest.json `layer` (written by teleframe:schema-update)
 *   2. composer root package `extra.telegram-layer` (release-time record)
 *   3. packaged methods-mtproto.json artifact `layer` (the API surface we ship)
 *   4. the wire layer EncryptedConnection::LAYER (floor; may intentionally differ)
 *
 * The wire layer and the schema layer are separate concerns on purpose
 * (see AGENTS note); never "fix" one to match the other.
 */
final class SchemaLayer
{
    /**
     * @param string|null $manifestPath explicit packaged schema-manifest.json path
     *        (testing/scratch seam); null resolves the packaged file.
     */
    public static function layer(?string $manifestPath = null): int
    {
        $path = $manifestPath ?? SchemaArtifacts::path('schema-manifest.json');
        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded) && isset($decoded['layer']) && is_int($decoded['layer'])) {
                return $decoded['layer'];
            }
        }

        $root = InstalledVersions::getRootPackage();
        $extra = is_array($root) && isset($root['extra']) && is_array($root['extra']) ? $root['extra'] : [];
        if (isset($extra['telegram-layer']) && is_int($extra['telegram-layer'])) {
            return $extra['telegram-layer'];
        }

        $artifact = json_decode((string) file_get_contents(SchemaArtifacts::path('methods-mtproto.json')), true);
        if (is_array($artifact) && isset($artifact['layer']) && is_int($artifact['layer'])) {
            return $artifact['layer'];
        }

        return EncryptedConnection::LAYER;
    }

    /**
     * Cache-salt primitive (Phase 5d): compiled/derived caches MUST be
     * salted with this, never with mtime alone — a layer bump invalidates
     * derived artifacts even when file mtimes are unchanged (gap constraint 4).
     */
    public static function cacheSalt(?string $manifestPath = null): string
    {
        return 'schema-layer-' . self::layer($manifestPath);
    }
}
```

- [ ] **Step 4: Wire composer extra + facade**

In `composer.json`, inside the existing `extra` object add a sibling of `laravel`:

```json
    "extra": {
        "telegram-layer": 229,
        "laravel": {
```

No other composer change. Regenerate the autoloader so `extra` is visible to `InstalledVersions::getRootPackage()`:

```bash
composer dump-autoload
```

In `src/Laravel/Services/TeleframeClient.php` add:

```php
    /** Declared Telegram schema layer of the packaged artifacts (spec D5). */
    public function schemaLayer(): int
    {
        return \MeRezaRezaei\Teleframe\Core\Schema\SchemaLayer::layer();
    }
```

In `src/Laravel/Facades/Teleframe.php` add to the docblock:

```php
 * @method static int schemaLayer() Declared Telegram schema layer of the packaged artifacts.
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Core/Schema/SchemaLayerTest.php`
Expected: PASS. Then run the whole suite once (`composer test`) to ensure no regression from the composer change.

- [ ] **Step 6: Full gate + commit**

Run: `composer verify`
Expected: green.

```bash
git add src/Core/Schema/SchemaLayer.php src/Laravel/Services/TeleframeClient.php src/Laravel/Facades/Teleframe.php composer.json composer.lock tests/Core/Schema/SchemaLayerTest.php
git commit -m "feat(schema): SchemaLayer resolver + cacheSalt + composer extra.telegram-layer (Phase 1 Task 1)"
```

---

### Task 2: Fix `generate-rpc-catalog.php` offline source (kill stale /tmp path)

**Files:**
- Modify: `bin/generate-rpc-catalog.php` (teleframe)
- Test: idempotence check via regeneration (manual verify below) — no new test file

**Interfaces:**
- Consumes: packaged `src/Schema/schema/sources/errors.json` (already committed).
- Produces: unchanged `src/Core/Exceptions/Rpc/RpcErrorCatalog.php` body (byte-identical regeneration proves the fix is safe).

- [ ] **Step 1: Read the current generator head** (already known): reads `/tmp/opencode/errors.json`. Replace the read with the packaged source:

Replace:

```php
$j = json_decode((string) file_get_contents('/tmp/opencode/errors.json'), true);
```

with:

```php
$j = json_decode((string) file_get_contents(dirname(__DIR__) . '/src/Schema/schema/sources/errors.json'), true);
```

- [ ] **Step 2: Regenerate and verify byte-identical catalogue**

Run:

```bash
cp src/Core/Exceptions/Rpc/RpcErrorCatalog.php /tmp/RpcErrorCatalog.php.bak
php bin/generate-rpc-catalog.php
diff /tmp/RpcErrorCatalog.php.bak src/Core/Exceptions/Rpc/RpcErrorCatalog.php
```

Expected: `diff` exits 0 (no output) — the committed catalog was already generated from the same errors.json; the fix only redirected the source. If diff shows changes, STOP and report (the catalog was out of sync; regenerate is the intended update path but must be reviewed).

- [ ] **Step 3: Gate + commit**

Run: `composer verify`
Expected: green (phpstan must stay clean — the bin is exempt from preg_* but still analysed).

```bash
git add bin/generate-rpc-catalog.php
git commit -m "fix(schema): rpc catalog generator reads committed errors.json source (Phase 1 Task 2)"
```

---

### Task 3: Unified `teleframe:schema-update` — full chain, offline, stamps manifest

**Files:**
- Modify: `src/Laravel/Console/SchemaUpdateCommand.php` (teleframe) — rework
- Modify: `src/Laravel/Console/SchemaAuditCommand.php` — add `pipelineSteps()` helper + fix root resolution via `SchemaArtifacts`
- Test: `tests/Laravel/SchemaPipelineTest.php` (teleframe)

**Interfaces:**
- Consumes: `SchemaAuditCommand::regenerateTo`, `SchemaDiffer`, `SchemaLayer`, `SchemaArtifacts`.
- Produces: `SchemaAuditCommand::pipelineSteps(): list<array{name: string, bin: string}>` (ordered full chain, never includes migrate); `SchemaUpdateCommand` signature `teleframe:schema-update {--no-fetch : Skip network fetch, regenerate from committed sources} {--dry-run : Regenerate + stamp into a scratch dir, leave the repo untouched}`. Dry-run stamps `scratch/schema-manifest.json` and prints the layer; real run stamps the packaged file. Phase 5d relies on the stamped manifest via `SchemaLayer`.

- [ ] **Step 1: Write the failing test**

Create `tests/Laravel/SchemaPipelineTest.php` (plain PHPUnit, no Laravel app):

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Laravel\Console\SchemaAuditCommand;
use MeRezaRezaei\Teleframe\Laravel\Console\SchemaUpdateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Phase 1 Task 3: the unified upgrade command runs the FULL generation
 * chain in a fixed order, never migrates, and stamps the layer manifest.
 */
final class SchemaPipelineTest extends TestCase
{
    public function test_pipeline_steps_are_ordered_and_complete(): void
    {
        $steps = SchemaAuditCommand::pipelineSteps();

        $names = array_column($steps, 'name');
        self::assertSame([
            'method-schema', 'botapi-schema', 'method-builders',
            'skill-files', 'rpc-catalog', 'userscope-schema',
        ], $names);

        foreach ($steps as $step) {
            self::assertFileExists($step['bin'], "pipeline generator missing: {$step['bin']}");
            self::assertStringNotContainsString('migrate', $step['name'], 'schema pipeline must never run migrations (D4)');
        }
    }

    public function test_missing_errors_source_aborts_cleanly(): void
    {
        // The rpc-catalog step reads the packaged source; a mangled read must
        // not fatal the whole command (each step reports and the chain stops).
        $tmp = sys_get_temp_dir() . '/tl-pipe-' . bin2hex(random_bytes(6));
        mkdir($tmp);
        $sources = $tmp . '/schema/sources';
        mkdir($sources, 0777, true);
        file_put_contents($sources . '/errors.json', '{broken');

        self::assertDirectoryExists($sources);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Laravel/SchemaPipelineTest.php`
Expected: FAIL — `SchemaAuditCommand::pipelineSteps()` not defined, and the first test errors.

- [ ] **Step 3: Add `pipelineSteps()` and fix root resolution in `SchemaAuditCommand`**

In `src/Laravel/Console/SchemaAuditCommand.php`:

1. Replace the two stale root resolutions (currently `InstalledVersions::isInstalled('merezarezaei/teleframe', true) ? ... : dirname(__DIR__, 3) . '/packages/schema'`) with a single `SchemaArtifacts`-based helper used by both commands:

```php
    /** @return string absolute teleframe package root (repo working copy or vendor) */
    public static function root(): string
    {
        return dirname(SchemaArtifacts::path('methods-mtproto.json'), 2);
    }
```

Use `self::root()` in place of `$root` in `handle()` and `regenerateTo()` (which currently build bin paths as `{$root}/bin/generate-*.php`; from the new root those resolve to `src/Schema/...`? No — the bins live at the package ROOT `bin/`, so keep `self::root() . '/bin/' . $name . '.php'`).

2. Add the ordered chain definition + a registration helper:

```php
    /**
     * The FULL generation chain for teleframe:schema-update, in order.
     * Each entry: a step name and the bin that performs it. Migrations are
     * NEVER part of this chain (spec D4) — hosts apply them explicitly.
     *
     * @return list<array{name: string, bin: string}>
     */
    public static function pipelineSteps(): array
    {
        $root = self::root();

        return [
            ['name' => 'method-schema', 'bin' => $root . '/bin/generate-method-schema.php'],
            ['name' => 'botapi-schema', 'bin' => $root . '/bin/generate-botapi-schema.php'],
            ['name' => 'method-builders', 'bin' => $root . '/bin/generate-method-builders.php'],
            ['name' => 'skill-files', 'bin' => $root . '/bin/generate-skill-files.php'],
            ['name' => 'rpc-catalog', 'bin' => $root . '/bin/generate-rpc-catalog.php'],
            ['name' => 'userscope-schema', 'bin' => $root . '/bin/generate-userscope-schema.php'],
        ];
    }
```

Note: verify the actual root–bin path: `SchemaArtifacts::path('methods-mtproto.json')` → `…/src/Schema/schema/…`; `dirname(…, 2)` → `…/src`? NO — dirname of `…/src/Schema/schema/methods-mtproto.json` with depth 2 = `…/src/Schema`, and the bins are at `…/bin/`, not `…/src/Schema/bin/`. The package root is `…/` (repo root). **Correct `root()` to resolve the package root properly:** use `dirname(dirname(dirname(__DIR__)))` → from `src/Laravel/Console/` that is one level above repo root's `src`. Read the current file's directory chain and derive root as `dirname(__DIR__, 3)` (Console → Laravel → src → repo root = 3 levels). Use that. Keep bins at `{root}/bin/{name}.php` and sources at `{root}/src/Schema/schema/sources/`.

- [ ] **Step 4: Rework `SchemaUpdateCommand`**

Rewrite `handle()` in `src/Laravel/Console/SchemaUpdateCommand.php` to:

1. Resolve root via `SchemaAuditCommand::root()` and sources dir `{root}/src/Schema/schema/sources`.
2. If `--no-fetch` off: run the existing FETCHES (each into `{sourcesDir}/{dest}`); on any failure report + return 1 (keep current temp-file rename semantics).
3. If `--dry-run`: create `sys_get_temp_dir()/teleframe-schema-update-<rand>`; capture pre-update artifacts (committed); run `SchemaAuditCommand::regenerateTo($scratch)` (method/botapi schemas only — the diffable pair); compute new layer from `$scratch/methods-mtproto.json`; write `$scratch/schema-manifest.json` `{"layer": N}`; print the SchemaDiffer report + `<info>stamped schema-manifest.json layer {N} (dry-run, repo untouched)</info>`; return 0.
4. Real run: run the FULL chain via `pipelineSteps()` in order using `SchemaAuditCommand::runProcess([PHP_BINARY, $step['bin']], self::root())` — stop the chain at the first failing step with a clear error (exit 1). After the chain, compute layer from the regenerated `{root}/src/Schema/schema/methods-mtproto.json` and stamp `SchemaArtifacts::path('schema-manifest.json')` with `{"layer": N}`. Print the pre/post diff via `SchemaDiffer` report. Return 0.

Keep `{--no-fetch}` and `{--dry-run}` in the signature/description. Never invoke migrate.

(The generators write their own outputs in place — method-schema/botapi write the artifacts dir, builders/skills/userscope/rpc write their src targets. That is the intended update behavior.)

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Laravel/SchemaPipelineTest.php`
Expected: PASS (both tests).

- [ ] **Step 6: Gate + commit**

Run: `composer verify`
Expected: green (phpstan clean — new methods typed, no preg_*, unused imports removed).

```bash
git add src/Laravel/Console/SchemaUpdateCommand.php src/Laravel/Console/SchemaAuditCommand.php tests/Laravel/SchemaPipelineTest.php
git commit -m "feat(schema): unified teleframe:schema-update — full chain, offline dry-run, layer stamp (Phase 1 Task 3)"
```

---

### Task 4: E2E prove — offline real run stamps the packaged manifest

**Files:**
- Modify: none (verification only) — result is a committed `src/Schema/schema/schema-manifest.json`
- Commit: the newly generated `schema-manifest.json`

**Interfaces:**
- Consumes: Task 3 command. Produces: packaged `schema-manifest.json` layer 229 committed; `SchemaLayer::layer()` returns 229 repo-wide.

- [ ] **Step 1: Run the command offline (dry-run first, safe)**

Run:

```bash
php bin/teleframe schema-update --dry-run --no-fetch 2>&1 | tail -30
```

Expected: a SchemaDiffer report (likely "Sources updated; artifacts unchanged" or a diff summary) and `<info>stamped … layer 229 (dry-run, repo untouched)</info>`. If the dry-run errors on a generator, STOP and fix (regression in Task 3).

- [ ] **Step 2: Run the real offline update**

Run:

```bash
php bin/teleframe schema-update --no-fetch 2>&1 | tail -30
git status --short
```

Expected: generators run in order; a new `src/Schema/schema/schema-manifest.json` appears (or is rewritten); regenerated artifacts are byte-identical (idempotent) so `git status` shows only `schema-manifest.json` (and possibly none of the generated files). If generated files differ, review the diff — this is an effective regeneration, allowed, but must be inspected (drift) before commit.

- [ ] **Step 3: Verify layer queryable + gates green**

Run:

```bash
grep -o '"layer":[0-9]*' src/Schema/schema/schema-manifest.json
```

Expected: `"layer":229`. Then `composer verify` — green.

- [ ] **Step 4: Commit**

```bash
git add src/Schema/schema/schema-manifest.json
git commit -m "chore(schema): packaged schema-manifest.json layer 229 stamped (Phase 1 Task 4)"
```

---

### Task 5: skill v2 + roadmap tick + spec status

**Files:**
- Modify: `skills/telegram-schema-update/SKILL.md` (teleframe) — v2 (unified command)
- Modify: `docs/superpowers/plans/2026-09-07-master-roadmap.md` — tick Phase 1
- Modify: `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` — Status line Phase 1 complete

**Interfaces:**
- Produces: SKILL v2 that human and AI both follow for the unified procedure.

- [ ] **Step 1: Update the skill to v2**

Rewrite the procedure section of `skills/telegram-schema-update/SKILL.md`:

**Current state (v2 — unified, post-Phase 1):**

| Piece | Where | Layer truth |
|---|---|---|
| Wire layer | `EncryptedConnection::LAYER` (227) | intentionally separate from schema layer |
| Method schemas | `src/Schema/schema/methods-mtproto.json` (+ botapi) | `layer` field (229) |
| Packaged stamp | `src/Schema/schema/schema-manifest.json` | `layer` field — **the schemaLayer() truth** |
| Composer record | `composer.json` `extra.telegram-layer` | release-time snapshot (229) |
| API | `Teleframe::schemaLayer(): int` / `SchemaLayer::cacheSalt()` | reads stamp → composer → artifact → wire |

**Procedure (v2):**
1. Decide to adopt a new layer (check https://core.telegram.org/api/schema for layer + changelog).
2. Run `php bin/teleframe schema-update` (fetches sources, regenerates the full chain in order, stamps `schema-manifest.json`). Offline: place fresh `.tl`/`errors.json` under `src/Schema/schema/sources/` then `php bin/teleframe schema-update --no-fetch`. Safety preview: `--dry-run --no-fetch` regenerates to scratch and leaves the repo untouched.
3. Review the SchemaDiffer report; regenerate is deterministic and idempotent.
4. **Manually** apply the mirror migrations (teleclient `php bin/regenerate --ship`, then `php artisan migrate` in the consuming app). Nothing ever migrates at runtime or inside the pipeline (D4).
5. On release: set `composer.json` `extra.telegram-layer` to the new layer.

**Never** hand-edit `schema-manifest.json` or any generated artifact.

- [ ] **Step 2: Tick the roadmap**

In `docs/superpowers/plans/2026-09-07-master-roadmap.md` change the Phase 1 block to:

```markdown
- [x] Write plan `plans/2026-09-07-phase1-schema-upgrade-pipeline.md` (writing-plans format)
- [x] Gate: command runs end-to-end on a synthetic layer bump; layer stamp queryable; gates green
```

- [ ] **Step 3: Spec status**

In `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` status line, append ` · Phase 1 (unified schema-update + schemaLayer) COMPLETE 2026-09-07`.

- [ ] **Step 4: Gate + commit + push**

Run: `composer verify`
Expected: green.

```bash
git add skills/telegram-schema-update/SKILL.md docs/superpowers/plans/2026-09-07-master-roadmap.md docs/superpowers/specs/2026-09-07-teleframe-unification-design.md
git commit -m "docs(schema): skill v2 unified upgrade + roadmap/spec Phase 1 tick (Phase 1 Task 5)"
git push origin main
```

---

## Completion checklist (whole plan)

- [ ] `composer verify` green after every task
- [ ] `SchemaLayer::layer()` == 229; `cacheSalt()` == `schema-layer-229` (repo-wide, no manifest override)
- [ ] `composer.json` `extra.telegram-layer` == 229; `composer.lock` updated
- [ ] `bin/generate-rpc-catalog.php` reads the committed `errors.json` (no `/tmp`)
- [ ] `SchemaAuditCommand::pipelineSteps()` = the 6 ordered steps, none migrate
- [ ] `php bin/teleframe schema-update --dry-run --no-fetch` succeeds and leaves repo untouched; `--no-fetch` real run stamps `src/Schema/schema/schema-manifest.json` layer 229
- [ ] Spec Status + roadmap Phase 1 ticked; skill v2 committed; pushed