---
name: telegram-schema-update
description: Use when Telegram publishes a new TL schema layer and the teleframe package (or the teleclient mirror) must be upgraded to it — covers fetching new .tl sources, diffing layers, regenerating schemas/builders/skill files/RPC catalog/mirror migrations, version stamping, and the verification gates. Triggers on "schema update", "layer bump", "telegram api changed", "new layer", "upgrade schema", "schema upgrade".
---

# Telegram Schema Upgrade (layer bump)

**Binding decisions (from the 2026-09-07 unification spec):** upgrades are
MANUAL, developer-run, never automatic at runtime; every manifest declares the
layer it speaks; migrations are applied by the developer, never as a side
effect of data writes. Nothing in this skill ever runs `migrate` implicitly.

## Current state (v2 — unified pipeline, post-Phase 1)

| Piece | Where | Layer truth |
|---|---|---|
| Wire layer | `EncryptedConnection::LAYER` (227) | intentionally separate from the schema layer — do NOT "fix" one to match the other |
| Method schemas | `src/Schema/schema/methods-mtproto.json` + `methods-botapi.json` | `layer` field in each JSON (229) |
| **Packaged stamp** | `src/Schema/schema/schema-manifest.json` | `layer` field — **the `schemaLayer()` truth** (229) |
| Composer record | `composer.json` `extra.telegram-layer` | release-time snapshot (229) |
| Runtime API | `Teleframe::schemaLayer(): int` / `SchemaLayer::cacheSalt()` | reads stamp → composer → artifact → wire floor |
| RPC catalog | `src/Core/Exceptions/Rpc/RpcErrorCatalog.php` | `LAYER` const (reads committed `sources/errors.json`) |
| Mirror schema | teleclient `schema/sources/*.tl` + `generated/schema-manifest.json` | `layer` field (227; merges into teleframe at Phase 2) |

## Procedure — one command, three modes

The whole pipeline lives in one Artisan command, also reachable standalone:

```bash
php bin/teleframe schema-update            # fetch + full chain + stamp
php bin/teleframe schema-update --no-fetch # fully offline, committed sources only
php bin/teleframe schema-update --dry-run --no-fetch  # scratch preview, repo untouched
```

The command runs the full generation chain **in this fixed order**
(`SchemaAuditCommand::pipelineSteps()`), stamps `schema-manifest.json`, and
reports the `SchemaDiffer` layer diff — **and never runs migrations**.

1. `method-schema`      → `schema/methods-mtproto.json`
2. `botapi-schema`      → `schema/methods-botapi.json`
3. `method-builders`    → `src/Core/Methods/Generated/*` + `src/Bot/Methods/Generated/*`
4. `skill-files`        → `src/Schema/skills/telegram-methods/*.md`
5. `rpc-catalog`        → `src/Core/Exceptions/Rpc/RpcErrorCatalog.php`
6. `userscope-schema`   → `src/Core/MTProto/TL/Schema/UserScopeSchema.php`

## Procedure (full walkthrough)

### Step 0 — Identify the delta
1. Check https://core.telegram.org/api/schema for the new layer number and
   https://core.telegram.org/api/updates for changelog notes.
2. Read the current stamp: `Teleframe::schemaLayer()` (or
   `src/Schema/schema/schema-manifest.json`).
3. Decide scope: wire layer bump (MTProto compat) is OPTIONAL and separate —
   schema artifacts may move ahead of the wire layer; they intentionally differ.
   A wire bump requires the full live gate and must never batch with a
   schema-only change.

### Step 1 — Refresh sources
1. Download the new `.tl` file from the schema page.
2. Replace the vendored partial sources under `src/Schema/schema/sources/`
   (the generators read `api.tl`, `mtproto.tl`, `errors.json`, `extracted.json`,
   `botapi-spec.json`).
3. For the teleclient mirror: replace `schema/sources/*.tl` (full mirror, one
   file per namespace) — merges into teleframe at Phase 2.

### Step 2 — Run the unified command
```bash
php bin/teleframe schema-update --no-fetch
```
Review the SchemaDiffer report. Success = bare regeneration is idempotent
(artifacts unchanged) **and** the manifest advances to the new layer. If the
report shows method-level differences, that is a real schema change — review
and commit it deliberately (never blindly).

Safety preview first when uncertain:
```bash
php bin/teleframe schema-update --dry-run --no-fetch   # leaves the repo untouched
```

### Step 3 — Curate the dial
If new constructors/methods matter to the apps: add to
`src/Schema/config/curated-methods.json` (builder groups) and/or widen the
teleclient `ship_namespaces` before re-running the applicable generator
(`--dry-run` mode previews the members; the real run regenerates them).

### Step 4 — Version stamps
1. Confirm the `layer` field advanced in `schema-manifest.json` AND the
   method-schema JSONs AND `RpcErrorCatalog::LAYER` (Step 0 list).
2. Record the layer in `composer.json` `extra.telegram-layer` at release time
   (`SchemaLayer` already prefers the packaged stamp at runtime).
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
composer verify                              # teleframe: test + stan
composer test && composer analyse            # teleclient (mirror consumer)
php bin/teleframe schema-audit               # offline idempotence check (drift → 1)
# live (opt-in, real credentials; REQUIRED if wire layer changed)
TELEPROTO_LIVE=true ./bin/teleframe test-e2e
```

### Step 7 — Commit shape
- One commit for the sources, one for regenerated output; the stamped
  `schema-manifest.json` goes with the regenerated output. NEVER hand-edit any
  generated artifact (`schema-manifest.json` included — regenerate instead).

## Failure modes
- `--dry-run` errors on a generator → regenerate the affected artifact family
  standalone (e.g. `php bin/generate-method-builders.php`) to isolate; fix the
  source or the curated dial, not the generator.
- phpstan fails after regen → hand-written code referenced a generated symbol
  that the new layer renamed; fix the hand-written reference, not the generator.
- `schema-audit` exits 1 → the committed artifacts drift from the sources;
  same fix path as above.
- teleclient tests fail after regen → canned fixtures pin constructor shapes
  (e.g. user#31774388 payload); update fixtures to the new layer's shapes.