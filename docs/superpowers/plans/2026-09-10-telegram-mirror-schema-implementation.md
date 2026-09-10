# Telegram Mirror Schema Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the UUID/CTI database schema with Telegram-native ID structure -- single table per constructor, Telegram's own numeric IDs as primary keys, faithful mirror of TDLib's storage pattern.

**Architecture:** One table per TL constructor (TDLib pattern) instead of anchor/instance/child CTI layers. Global-ID types (User, Chat, Channel, Photo, Document) use Telegram's own ID as PK. Scoped-ID types (Message) use surrogate auto-increment PK + composite unique constraint. All tables carry `account_id` for tenant isolation.

**Tech Stack:** PHP 8.2+, Laravel Eloquent, PostgreSQL (production), SQLite (tests), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-09-10-telegram-mirror-schema-design.md`

## Global Constraints

- Zero regex in `src/Core/*` and `src/Teleframe/Handler/*` (AGENTS.md hard rule)
- Generated files carry `@generated` markers -- never hand-edit `generated/` output
- MySQL 64-char identifier limit -- content-addressed FK names via sha1
- Deferred FK constraints: `DEFERRABLE INITIALLY DEFERRED`, bucketed 512 per file
- `account_id` on every table (tenant isolation contract)
- PG identifier limit 63 bytes -- `Naming::fit()` handles truncation
- Tests use SQLite `:memory:` -- no external DB required for unit tests

---

## File Structure

| File | Action | Responsibility |
|------|--------|---------------|
| `src/Schema/Generator/Naming.php` | Modify | Remove CTI naming (`anchorTable`, `instanceTable`, `childTable`), add `constructorTable()`, update `dbType()` and `cast()` for ref to bigint |
| `src/Schema/Generator/MigrationGenerator.php` | Rewrite | Single-table-per-constructor DDL with Telegram-native PKs |
| `src/Schema/Generator/ModelGenerator.php` | Modify | Emit models with `$incrementing = true`, `$keyType = 'int'`, use `constructorTable()` |
| `src/Schema/Generator/FactoryGenerator.php` | Modify | Replace `UuidV7` with fake Telegram IDs in factories |
| `src/Schema/Eloquent/TlAnchorModel.php` | Rewrite | Base model: `$incrementing = true`, `$keyType = 'int'`, no UUID generation |
| `src/Schema/Eloquent/TlInstanceModel.php` | Remove | Merge into TlAnchorModel (no more anchor/instance split) |
| `src/Schema/Eloquent/PeerResolution.php` | Modify | Update `PEER_FQCN_MAP` to point to new model classes |
| `src/Teleframe/Ingest/UpdateIngestor.php` | Modify | `?string $anchorId` to `?int $anchorId`, use `constructorTable()`, remove anchor/instance distinction |
| `src/Teleframe/Ingest/EntityAggregator.php` | Modify | Update family table lookups to use `constructorTable()` |
| `src/Teleframe/Ingest/RouteIdempotency.php` | Modify | `storedId()` returns `?int`, `mark()` accepts `int` |
| `tests/Schema/MigrationGeneratorTest.php` | Rewrite | Expect new DDL output (single table, bigint PKs) |
| `tests/Ingest/UpdateIngestorTest.php` | Modify | Replace UUID assertions with int ID assertions |
| `tests/Ingest/EntityAggregatorTest.php` | Modify | Update ID type expectations |
| `tests/Ingest/IdentityLockTest.php` | Modify | Update lock key format if needed |

---

### Task 1: Naming.php -- Remove CTI, Add constructorTable, Update Types

- [x] Remove `anchorTable()`, `instanceTable()`, `childTable()` methods
- [x] Add `constructorTable(string $tlType, string $ctorName): string`
- [x] Update `dbType()` to return `'bigint'` for ref params
- [x] Update `cast()` to return `'int'` for ref params

### Task 2: MigrationGenerator.php -- Single-Table-Per-Constructor DDL

- [x] Rewrite `typeMigration()` to emit one `Schema::create` per constructor
- [x] Classify ID strategy: global (`id:long` as PK), scoped (`bigIncrements` + composite unique), none (`bigIncrements`)
- [x] Emit child tables for vector params (parent_id FK, idx ordering, value_id)
- [x] Deferred FK bucketing (512 per file, `DEFERRABLE INITIALLY DEFERRED`)
- [x] Content-addressed index names via `sha1(table:col)`

### Task 3: ModelGenerator + FactoryGenerator + TlAnchorModel

- [x] Models: `$incrementing = true`, `$keyType = 'int'`, constructor table name
- [x] Factories: fake Telegram integer IDs instead of `UuidV7`
- [x] `TlAnchorModel`: int PK base, `AccountScoped` global scope, `newFactory()` override

### Task 4: UpdateIngestor -- Integer PKs and constructorTable

- [x] `?string $anchorId` to `?int $anchorId`
- [x] Use `constructorTable()` for model class resolution
- [x] Anchor row goes into the anchor table (first ctor's table, ksort order)
- [x] Identity column check: `Schema::hasColumn()` before inserting identity on anchor row
- [x] `UpdateStored` event fires with committed root model

### Task 5: EntityAggregator + RouteIdempotency

- [x] Family table lookups use `constructorTable()`
- [x] `storedId()` returns `?int`, `mark()` accepts `int`
- [x] `currentInstance()` reads integer params correctly

### Task 6: Generated Artifacts Regeneration

- [x] All models, migrations, factories, data classes regenerated
- [x] Migration filenames: `_tables.php` suffix (merged constructors per type)
- [x] All param columns nullable (anchor table shared by constructors)

### Task 7: Test Fixes -- Nullable Params + Schema::hasColumn Guard

- [x] `MigrationGenerator::columnLines()` makes ALL param columns nullable
- [x] `UpdateIngestor` checks `Schema::hasColumn()` before adding identity column to anchor fill
- [x] `NestedIngestTest`: integer PK assertions, `sole()` filters by `constructor_name` where merged table holds multiple constructor variants
- [x] `NestedIngestTest`: peer row counts reflect per-tenant anchor duplication (4 rows = 2 accounts x 2 constructors)
- [x] `UpdateIngestorTest`: identity lock key format updated for merged schema
- [x] 44/44 ingest tests pass (222 assertions), IdentityLockTest excluded (pre-existing crash)

### Task 8: Remaining Work

- [ ] IdentityLockTest fix (pre-existing, not part of this migration)
- [ ] `composer verify` gate (phpunit + phpstan + regeneration idempotence)
- [ ] `bin/standalone-smoke.php` gate
- [ ] Postgres truth track: `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`

---

## Commit History (this branch)

| Hash | Message |
|------|---------|
| `ae8246a` | fix(ingest): nullable params + Schema::hasColumn guard for merged anchor tables |
| `255a874` | feat(schema): SchemaRegenerator uses constructorTable naming for ship logic |
| `1cd5da1` | fix(ingest): EntityAggregator currentInstance() int param + remove stale TlScheme import |
| `5a11709` | fix(ingest): UpdateStored event uses TlAnchorModel (not deleted TlInstanceModel) |
| `500d23b` | feat(ingest): EntityAggregator and RouteIdempotency use integer PKs |
| `9ef2232` | feat(ingest): UpdateIngestor uses integer PKs and constructorTable naming |
