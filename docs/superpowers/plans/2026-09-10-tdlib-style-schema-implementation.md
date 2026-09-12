# Plan: TDLib-Style Domain Table Schema Implementation

**Date:** 2026-09-10
**Spec:** `docs/superpowers/specs/2026-09-10-telegram-mirror-schema-design.md`
**Goal:** Replace 393 per-constructor tables with ~15 domain tables (tf_users, tf_messages, etc.)

## Task 1: Rewrite Naming.php

**Files:** `src/Schema/Generator/Naming.php`

Remove:
- `constructorTable()` — replaced by `domainTable()`
- `childTable()` — no more child tables
- `fit()` — no more hash suffixes
- `dedupeNamespace()` — not needed
- `ctorModel()` — one model per domain, not per constructor

Add:
- `domainTable(string $domain): string` — returns `"tf_{$domain}"`
- `domainFromType(string $tlType): ?string` — classifies TL type → domain name
- `domainModel(string $domain): string` — returns PascalCase model name

Keep:
- `snake()`, `pascal()`, `column()`, `dbType()`, `cast()`, `assertUnique()`
- `model()` — updated to work with domain names

## Task 2: Rewrite MigrationGenerator.php

**Files:** `src/Schema/Generator/MigrationGenerator.php`

Replace constructor-per-table logic with:
1. Classify all TL types into domains using `Naming::domainFromType()`
2. For each domain, emit one `Schema::create()` call with:
   - Correct PK strategy (Telegram native ID vs surrogate)
   - Extracted query columns
   - `tl_data JSONB` column
   - `constructor_id INT` column
   - `account_id BIGINT` column
   - Appropriate indexes and unique constraints
3. Emit route table migration (unchanged)
4. Remove FK migration logic (deferred FKs no longer needed for child tables)

## Task 3: Rewrite ModelGenerator.php

**Files:** `src/Schema/Generator/ModelGenerator.php`

Replace anchor/instance/child model pattern with:
1. One model class per domain table (e.g., `TlUser`, `TlMessage`)
2. Extend `DomainModel` (renamed from `TlAnchorModel`)
3. `$primaryKey`, `$keyType`, `$casts` per domain
4. `tl_data` cast to `array`
5. Peer resolution trait where applicable

## Task 4: Rewrite Eloquent base models

**Files:**
- `src/Schema/Eloquent/TlAnchorModel.php` → rename/refactor to `DomainModel.php`
- Delete `src/Schema/Eloquent/TlInstanceModel.php`
- Delete `src/Schema/Eloquent/HasTlChildren.php`
- Update `src/Schema/Eloquent/AccountScoped.php` (keep)
- Update `src/Schema/Eloquent/PeerResolution.php`

## Task 5: Update Ingest layer

**Files:**
- `src/Teleframe/Ingest/UpdateIngestor.php`
- `src/Teleframe/Ingest/EntityAggregator.php`

Change storage logic:
1. Classify TL type → domain table
2. Serialize full TL object → JSONB
3. Extract query columns from TL payload
4. Upsert into domain table (not per-constructor table)

## Task 6: Update tests

**Files:** All test files under `tests/Schema/`, `tests/Ingest/`, `tests/Standalone/`

## Task 7: Regenerate and verify

```bash
php artisan teleframe:regenerate --ship
composer verify
php bin/standalone-smoke.php
```

## Execution Strategy

Tasks 1-2 are sequential (Naming → MigrationGenerator).
Tasks 3-5 depend on Task 1 but are independent of each other.
Task 6 depends on Tasks 1-5.
Task 7 is final verification.

Execute: T1 → T2 → [T3, T4, T5 parallel] → T6 → T7
