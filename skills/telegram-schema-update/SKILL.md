---
name: telegram-schema-update
description: Use when Telegram publishes a new TL schema layer and the teleframe package (or the teleclient mirror) must be upgraded to it — covers fetching new .tl sources, diffing layers, regenerating schemas/builders/skill files/RPC catalog/mirror migrations, version stamping, and the verification gates. Triggers on "schema update", "layer bump", "telegram api changed", "new layer", "upgrade schema", "schema upgrade".
---

# Telegram Schema Upgrade (layer bump)

**Binding decisions (from the 2026-09-07 unification spec):** upgrades are
MANUAL, developer-run, never automatic at runtime; every manifest declares the
layer it speaks; migrations are applied by the developer, never as a side
effect of data writes. Nothing in this skill ever runs `migrate` implicitly.

## Current state (v1 — two repos, pre-unification Phase 0)

| Piece | Where | Layer truth |
|---|---|---|
| Wire layer | teleframe `EncryptedConnection::LAYER` | may intentionally trail the schema artifact — do NOT "fix" one to match the other |
| Method schemas | teleframe `src/Schema/schema/methods-mtproto.json` + `methods-botapi.json` | `layer` field in each JSON |
| Mirror schema | teleclient `schema/sources/*.tl` + `generated/schema-manifest.json` | `layer` field in manifest |
| RPC catalog | teleframe `src/Core/Exceptions/Rpc/RpcErrorCatalog.php` | `LAYER` const |

## Procedure

### Step 0 — Identify the delta
1. Check https://core.telegram.org/api/schema for the new layer number and
   https://core.telegram.org/api/updates for changelog notes.
2. Read current stamps: teleframe method-schema JSONs + teleclient
   `generated/schema-manifest.json` + `RpcErrorCatalog::LAYER`.
3. Decide scope: wire layer bump (MTProto compat) is OPTIONAL and separate —
   schema artifacts may move ahead of the wire layer; they intentionally differ.

### Step 1 — Refresh sources
1. Download the new `.tl` file from the schema page.
2. teleframe: replace the vendored partial sources under
   `src/Schema/schema/sources/` that the generators read.
3. teleclient: replace `schema/sources/*.tl` (full mirror, one file per
   namespace).

### Step 2 — Regenerate (order matters; each generator is idempotent)
Run from the teleframe repo root:
```bash
php bin/generate-method-schema.php     # methods-mtproto.json
php bin/generate-botapi-schema.php     # methods-botapi.json
php bin/generate-method-builders.php   # curated builders (config: curated-methods dial)
php bin/generate-skill-files.php       # src/Schema/skills/telegram-methods/*.md
php bin/generate-rpc-catalog.php       # re-fetches core.telegram.org/api/errors.json
php bin/generate-userscope-schema.php  # userscope artifacts
```
Run from the teleclient repo root:
```bash
php bin/regenerate            # metamodel → generated/ (±30% sanity gate)
php bin/regenerate --ship     # ALSO copies the curated dial into migrations/
```
`--force` bypasses the ±30% gate ONLY for huge layers; investigate first if it trips.

### Step 3 — Curate the dial
If new constructors/methods matter to the apps: add to teleframe
`src/Schema/config/curated-methods.json` (builder groups) and/or widen
teleclient `ship_namespaces` (config or `TELECLIENT_SHIP_NAMESPACES`) before
re-running the builders / `--ship` copy.

### Step 4 — Version stamps
1. Confirm `layer` fields advanced in ALL artifacts (Step 0 list).
2. Record the layer in composer `extra.telegram-layer` (Phase 1 will add;
   until then note it in CHANGELOG unreleased section).
3. If the wire layer is ALSO bumped: update `EncryptedConnection::LAYER` and
   re-run the full live gate — never batch a wire bump with a schema-only change.

### Step 5 — Apply migrations (developer decision — never automatic)
- Laravel host: `php artisan migrate` (dial) — new generated migrations are
  additive per-type tables; existing data is untouched.
- Plain PHP host: run the explicit migration command (Phase 2 delivers
  `Teleframe::migrate()`; until then, drive the illuminate migrator manually
  over `UpdateIngestor::migrationPaths()`).
- Apps using a slice of the API may skip a layer entirely (D4).

### Step 6 — Verify
```bash
# teleframe
composer verify
# teleclient
composer test && composer analyse
# live (opt-in, real credentials; REQUIRED if wire layer changed)
TELEPROTO_LIVE=true ./bin/teleframe test-e2e
```

### Step 7 — Commit shape
- teleframe: one commit per artifact family (schemas / builders+skills / rpc
  catalog) — they regenerate independently.
- teleclient: one commit for sources + one for regenerated output; NEVER
  hand-edit anything under `generated/` or `migrations/` (regenerate instead).

## Failure modes
- ±30% gate trips → a source file is malformed or a namespace went missing;
  diff old/new `.tl` file counts before reaching for `--force`.
- phpstan fails after regen → hand-written code referenced a generated symbol
  that the new layer renamed; fix the hand-written reference, not the generator.
- teleclient tests fail after regen → canned fixtures pin constructor shapes
  (e.g. user#31774388 payload); update fixtures to the new layer's shapes.

## Post-unification (Phase 1, v2 of this skill)
All of the above collapses into `teleframe:schema-update` (diff + regenerate +
stamp, still NO implicit migrate) with `Teleframe::schemaLayer(): int` as the
runtime query surface. This section activates when Phase 1 lands.
