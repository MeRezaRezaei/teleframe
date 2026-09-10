# Telegram Mirror Schema Design — Replace UUID/CTI with Telegram-Native ID Structure

**Status:** Draft (awaiting user review)
**Date:** 2026-09-10
**Replaces:** The class-table-inheritance (CTI) + UUIDv7 PK pattern in `MigrationGenerator`, `Naming`, `TlAnchorModel`, `TlInstanceModel`, and all downstream consumers.

## 1. Goal

Replace the current UUID/CTI database schema with a structure that mirrors
Telegram's reverse-engineered database design. The database becomes a faithful
mirror of Telegram's internal data model, so that when Telegram changes their
structure, the diff between our schema and theirs is the signal for what needs
updating.

**Core principle (user ruling):** "The database should mirror the telegram
reverse engineered. If they change things, we can identify it. Any problem we
face, Telegram has already faced it when you mirror it."

## 2. Current Problems

### 2.1 UUID PKs are wrong

Telegram uses **numeric IDs** globally — not UUIDs. The current anchor table
pattern (`uuid('id')->primary()`) creates an artificial identity layer that
doesn't exist in Telegram:

- User IDs are globally unique positive integers (e.g., `1724372757`)
- Chat IDs are globally unique negative integers (e.g., `-1001234567890`)
- Channel IDs use the `-100` prefix (e.g., `-1001234567890`)
- Message IDs are **scoped** to their chat (e.g., message `12345` in chat
  `-1001234567890`)

UUIDs force `(string) $model->getKey()` casts everywhere, break natural lookups,
and make the schema diverge from Telegram's actual data model.

### 2.2 Class-Table Inheritance is over-engineered

The current CTI pattern creates **3 table layers** per constructor variant:
- Anchor table (`tl_users_users`): UUID PK, constructor_id, constructor_name
- Instance table (`tl_users_users_users`): UUID FK → anchor, per-constructor columns
- Child table (`tl_users_users_users__users`): UUID PK, parent_id FK → instance

TDLib (Telegram's official database library) uses **1 table per constructor**
with only that constructor's columns. No anchor table, no instance/child split.
This is simpler, more performant, and directly mirrors Telegram's storage.

### 2.3 Ref columns store UUIDs instead of Telegram IDs

Foreign key columns store UUIDs pointing to anchor tables, when they should
store Telegram's native numeric IDs. A `peer_user_id` column should hold
`1724372757` (the Telegram user ID), not a UUIDv7 string.

### 2.4 The schema can't detect Telegram changes

Because our schema is structurally different from Telegram's (UUIDs, CTI,
different table organization), we can't do a simple diff to detect when
Telegram adds a new field, changes a type, or restructures a constructor.
A faithful mirror makes change detection trivial.

## 3. Design Principles

1. **Mirror Telegram's actual database structure.** One table per TL
   constructor (or per type if constructors share a superset). Telegram's
   native IDs are primary keys where they exist globally; composite unique
   constraints where IDs are scoped.

2. **Telegram's ID namespace is the identity model.** Users = positive,
   chats = negative, channels = `-100xxx`, messages = scoped (chat_id,
   message_id). The `PeerIdTool` already encodes this correctly — extend
   the pattern to the schema layer.

3. **When Telegram changes, we detect and adapt.** The schema diff between
   our mirror and Telegram's next layer is the upgrade signal. No invented
   abstractions to maintain.

4. **Tenant-scoping via `account_id`.** Every table carries `account_id`
   for multi-tenant isolation. This is the one addition beyond Telegram's
   native structure.

## 4. New Schema Strategy

### 4.1 ID Categories (from Telegram's actual design)

| Category | Telegram Types | ID Scope | PK Strategy |
|----------|---------------|----------|-------------|
| **Global** | User, Chat, Channel, Photo, Document, etc. | Globally unique across all chats | `bigIncrements('id')` — Telegram's own ID |
| **Scoped** | Message, ForumTopic, StoryItem | Unique within a chat/conversation | `bigIncrements('id')` surrogate + `UNIQUE(chat_id, message_id)` |
| **Identity-less** | MessageMedia, MessageAction, Update entities | No natural ID | `bigIncrements('id')` surrogate |

### 4.2 Table Structure (per TL constructor)

**Single table per constructor** — TDLib's pattern, adapted for PostgreSQL:

```sql
CREATE TABLE tl_messages_message (
    id              BIGINT PRIMARY KEY,  -- Telegram message ID (scoped)
    chat_id         BIGINT NOT NULL,     -- Peer ID (Telegram native)
    from_id         BIGINT,              -- Peer ID (nullable, flagged)
    date            INT NOT NULL,        -- Unix timestamp
    message         TEXT,                -- Message text
    -- ... all messageMessage params as columns ...
    constructor_id  BIGINT NOT NULL,     -- CRC32 discriminator
    constructor_name VARCHAR(96) NOT NULL,
    account_id      BIGINT NOT NULL,     -- Tenant scope
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE(chat_id, id, account_id)      -- Scoped identity
);
```

For globally-identified types (User, Chat, Channel):

```sql
CREATE TABLE tl_users_user (
    id              BIGINT PRIMARY KEY,  -- Telegram user ID
    first_name      TEXT,
    last_name       TEXT,
    phone           TEXT,
    -- ... all userUser params ...
    constructor_id  BIGINT NOT NULL,
    constructor_name VARCHAR(96) NOT NULL,
    account_id      BIGINT NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
    -- No UNIQUE needed — Telegram ID IS the PK
);
```

### 4.3 Vector Handling (arrays)

Two approaches, chosen per TL schema:

**Approach A — Inline JSON column** (for short, rarely-queried vectors):
```sql
message_entities JSONB  -- stores the vector<MessageEntity> as JSON array
```

**Approach B — Separate child table** (for long, frequently-queried vectors):
```sql
CREATE TABLE tl_messages_message__entities (
    id          BIGINT PRIMARY KEY,
    parent_id   BIGINT NOT NULL REFERENCES tl_messages_message(id),
    idx         INT NOT NULL,
    -- element columns (if scalar) or value_id FK (if ref)
    account_id  BIGINT NOT NULL,
    UNIQUE(parent_id, idx)
);
```

The generator decides: if the vector element type has ≤3 scalar params,
use inline JSONB. If it has ref params or is frequently queried (messages,
chats, users lists), use a child table.

### 4.4 Peer Reference Columns

Peer references (to User, Chat, Channel) store **Telegram's native
numeric ID** (the `long` encoding from `PeerIdTool`), not UUIDs:

```php
// In MigrationGenerator:
$table->bigInteger('from_id');  // Stores PeerIdTool::userLong(12345)

// In UpdateIngestor:
$columns['from_id'] = PeerIdTool::userLong((int) $value['user_id']);
```

### 4.5 Cross-Type FK Constraints

Deferred FK constraints (DEFERRABLE INITIALLY DEFERRED) are preserved
for ref columns that point to global-ID anchor tables. The bucketing
strategy (512 per file) remains unchanged.

## 5. Table Classification

Each TL type is classified by its ID property:

| TL Type | ID Property | Table Strategy | PK |
|---------|------------|----------------|-----|
| `User` | Global (`id:long`) | Single table, Telegram ID as PK | `id` (Telegram user ID) |
| `Chat` | Global (`id:long`) | Single table, Telegram ID as PK | `id` (Telegram chat ID) |
| `Channel` | Global (`id:long`) | Single table, Telegram ID as PK | `id` (Telegram channel ID) |
| `Message` | Scoped (`id:int`) | Single table + composite unique | `id` (surrogate) + `UNIQUE(chat_id, id)` |
| `MessageMedia` | None | Single table, auto-increment PK | `id` (surrogate) |
| `Update` | None | Single table, auto-increment PK | `id` (surrogate) |
| `Photo` | Global (`id:long`) | Single table, Telegram ID as PK | `id` (Telegram photo ID) |
| `Document` | Global (`id:long`) | Single table, Telegram ID as PK | `id` (Telegram document ID) |
| `Dialog` | Composite | Single table + `UNIQUE(peer_id, account_id)` | `id` (surrogate) |

The generator reads the TL schema to determine:
1. Does this type have a param named `id`?
2. What is the type of that `id` param? (`long` = global, `int` = scoped)
3. Does this type have a Peer/InputPeer param that scopes the identity?
4. Does this type have any ID at all?

## 6. Files Changed

### 6.1 Core Generator (rewrite)

| File | Change |
|------|--------|
| `src/Schema/Generator/MigrationGenerator.php` | Complete rewrite: remove CTI (anchor/instance/child), emit single-table-per-constructor DDL with Telegram-native PKs |
| `src/Schema/Generator/Naming.php` | Remove `anchorTable()`, `instanceTable()`, `childTable()`. Add `constructorTable()` (one table per constructor). Update `dbType()` to emit `bigint` for ref columns instead of `uuid`. |
| `src/Schema/Generator/ModelGenerator.php` | Emit models with `$incrementing = true`, `$keyType = 'int'` for global-ID types; surrogate PK for scoped types |
| `src/Schema/Generator/FactoryGenerator.php` | Replace `UuidV7` with fake Telegram IDs in factories |

### 6.2 Eloquent Models (rewrite)

| File | Change |
|------|--------|
| `src/Schema/Eloquent/TlAnchorModel.php` | Remove UUIDv7 PK assignment. Base model with `$incrementing = true`, `$keyType = 'int'`. |
| `src/Schema/Eloquent/TlInstanceModel.php` | Merge into TlAnchorModel (no more anchor/instance split). |
| `src/Schema/Eloquent/PeerResolution.php` | Update `PEER_FQCN_MAP` to point to new model classes |
| `src/Schema/Eloquent/PeerIdTool.php` | Already correct — no change needed |

### 6.3 Ingest Layer (update)

| File | Change |
|------|--------|
| `src/Teleframe/Ingest/UpdateIngestor.php` | Change `?string $anchorId` → `?int $anchorId`. Remove anchor/instance distinction. Use `constructorTable()` naming. |
| `src/Teleframe/Ingest/EntityAggregator.php` | Update TL ID lookups (already uses `tl_id`, needs to use Telegram native IDs) |
| `src/Teleframe/Ingest/RouteIdempotency.php` | Update PK types from string to int |
| `src/Teleframe/Ingest/IdentityLock.php` | Update lock key format (currently uses string IDs) |

### 6.4 Downstream Consumers (update)

| File | Change |
|------|--------|
| `src/Teleframe/Ingest/PayloadWalker.php` | No change (works with raw arrays) |
| `src/Schema/Generator/SchemaRegenerator.php` | Update to emit new migration format |
| `src/Laravel/Services/TeleframeClient.php` | No change (works with method calls, not model PKs) |

### 6.5 Tests (update)

| File | Change |
|------|--------|
| `tests/Schema/MigrationGeneratorTest.php` | Rewrite to expect new DDL output |
| `tests/Ingest/UpdateIngestorTest.php` | Update PK expectations from UUID to int |
| `tests/Ingest/EntityAggregatorTest.php` | Update ID type expectations |
| All test files using `UuidV7` or UUID assertions | Replace with int ID assertions |

### 6.6 Generated Artifacts (regenerate)

All files under `generated/` are regenerated by `php artisan teleframe:regenerate`.
No manual edits — the generator produces the new format automatically.

## 7. Migration Strategy

### 7.1 Breaking Change

This is a **complete schema rewrite**. The existing 3678+ tables with UUID
PKs and CTI structure are incompatible with the new single-table design.

**Migration approach:** Drop and recreate. The existing data is from a
development/staging environment. Production deployments will start fresh
with the new schema.

### 7.2 Migration File Layout

New file layout (deterministic, ksort order):
- One file per TL type (not per constructor) — all constructors of a type
  share one migration file with one `Schema::create` call
- Vector child tables appended to the same file as their parent
- Route tables: one file with all `tl_route_*` tables
- FK constraint files: bucketed (512 per file), same as current

### 7.3 Rollback

No backward compatibility with the old schema. The generator's `down()`
methods drop the new tables. If rollback to the old schema is needed,
regenerate from the previous commit.

## 8. ID Assignment Rules

### 8.1 Global-ID Types (User, Chat, Photo, Document)

The Telegram ID IS the primary key. The ingest layer must:
1. Extract the `id` param from the TL payload
2. Use it as the model's PK directly
3. On conflict (re-ingest), update the existing row

```php
// Current (wrong):
$model->forceFill(['constructor_id' => ...]);
$model->save(); // UUIDv7 assigned by TlAnchorModel::booted

// New (correct):
$model->setAttribute('id', $telegramId); // Telegram's own ID
$model->save(); // No UUID generation
```

### 8.2 Scoped-ID Types (Message)

Use a surrogate auto-increment PK, with a composite unique constraint:

```sql
UNIQUE(chat_id, id, account_id)  -- chat_id + Telegram message ID + tenant
```

The ingest layer resolves:
1. Look up existing row by `(chat_id, message_id, account_id)`
2. If found → update; if not → insert with auto-generated surrogate PK

### 8.3 Identity-less Types

Auto-increment PK. Content-based deduplication (same as current) for
idempotent re-ingestion.

## 9. Change Detection (Future Capability)

With a faithful mirror, detecting Telegram schema changes becomes trivial:

```bash
# Compare our generated DDL against Telegram's latest TL schema
php artisan teleframe:schema-diff
```

This would:
1. Regenerate the expected DDL from the new TL schema sources
2. Compare against the currently committed DDL
3. Report: added fields, removed fields, type changes, new constructors

This is **future scope** — the current spec delivers the mirror structure
that enables this capability.

## 10. Risks and Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking all existing data | High (dev/staging only) | Drop and recreate; no production data migration needed |
| Telegram ID overflow (32-bit int) | Medium | Telegram uses `long` (64-bit) for IDs — `BIGINT` handles this |
| MySQL 64-char identifier limit | Low | Content-addressed names (sha1) already in place |
| Deferred FK on SQLite | Low | SQLite ignores DEFERRABLE — acceptable for local dev |
| Scoped-ID surrogate PK performance | Low | Auto-increment is fast; composite unique is indexed |

## 11. Out of Scope

- AI-assisted schema change detection (future, D6 ruling)
- Data migration tooling (drop-and-recreate for now)
- PSR-7/17 HTTP layer (not affected)
- Handler substrate (not affected)
- Bus/Daemon/Backfill/Backup modules (use model PKs, but don't generate them)
