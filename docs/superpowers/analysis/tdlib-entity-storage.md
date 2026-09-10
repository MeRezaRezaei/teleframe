# TDLib Entity Storage Schema Analysis

## Overview

TDLib stores Telegram entities in a single encrypted SQLite database (`db.sqlite`)
plus an encrypted binlog (`td.binlog`). Unlike Teleframe -- which mirrors every TL
constructor into its own Postgres table with column-per-field normalization -- TDLib
uses a radically different approach: **serialized binary blobs in a generic key-value
store**, with structured SQL tables reserved only for entities that need query access
(messages, dialogs, stories, files).

This document maps TDLib's storage architecture in detail, then compares it against
Teleframe's `tl_*` schema and derives actionable improvement recommendations.

**Source reference**: TDLib Layer 227-era, September 2026 snapshot.

---

## Entity Decomposition Strategy

### TDLib: Blob-Per-Entity (No Decomposition)

TDLib does **not** decompose Telegram objects into relational columns. Instead,
each entity type is serialized as a single binary blob using a custom TL-based
binary format (`log_event_store` / `log_event_parse`). The serialization follows
the entity's struct definition exactly, field by field, using a compact binary
encoding that omits field names.

**Key mapping** (entity type -> storage key):

| Entity Type | Storage Key Pattern | Storage Backend |
|---|---|---|
| User | `"us" + user_id` | SQLite PMC (common KV table) |
| Chat (basic group) | `"gr" + chat_id` | SQLite PMC |
| Channel (supergroup) | `"ch" + channel_id` | SQLite PMC |
| UserFull | `"us_full" + user_id` | SQLite PMC |
| ChatFull | `"gr_full" + chat_id` | SQLite PMC |
| ChannelFull | `"ch_full" + channel_id` | SQLite PMC |
| SecretChat | `"ss" + secret_chat_id` | SQLite PMC |
| Message | PK = `(dialog_id, message_id)` | Specialized table |
| Dialog | PK = `dialog_id` | Specialized table |
| Story | PK = `(dialog_id, story_id)` | Specialized table |
| File | PK = `file_db_id` | KV table (`files`) |
| WebPage | `"wp" + url_hash` | SQLite PMC |
| Singleton state | `"my_id"`, `"freeze_state"`, etc. | Binlog PMC |

The `common` SQLite table (via `SqliteKeyValue`) stores all entities as:
```sql
CREATE TABLE IF NOT EXISTS common (k BLOB PRIMARY KEY, v BLOB)
```

The `files` table is identical:
```sql
CREATE TABLE IF NOT EXISTS files (k BLOB PRIMARY KEY, v BLOB)
```

### Teleframe: Column-Per-Field Decomposition

Teleframe takes the opposite approach. Every TL constructor gets its own Postgres
table, with each field becoming a typed column. Vector fields get child tables.
The schema is fully normalized with deferred cross-type foreign keys.

**Example contrast -- User entity:**

| Aspect | TDLib | Teleframe |
|---|---|---|
| Storage unit | 1 blob per user | 1 row per constructor variant |
| Query "get user by id" | `kv.get("us" + id)` | `SELECT * FROM tl_user_user WHERE tl_id = ? AND account_id = ?` |
| Query "all premium users" | Must load every user, filter in memory | `SELECT * FROM tl_user_user WHERE is_premium = true` |
| Query "users in chat" | N/A (not queryable) | `SELECT * FROM tl_user_user WHERE ...` via chat membership join |

---

## Table Structure & Indexing

### TDLib SQLite Tables

TDLib creates **only 4 specialized tables** (plus 2 KV tables). All others are
handled via the generic key-value store:

#### `messages` table
```sql
CREATE TABLE messages (
    dialog_id INT8,
    message_id INT8,
    unique_message_id INT4,
    sender_user_id INT8,
    random_id INT8,
    data BLOB,               -- full serialized message
    ttl_expires_at INT4,
    index_mask INT4,          -- bitmask for media-type filter indices
    search_id INT8,           -- for FTS linkage
    text STRING,              -- extracted text for FTS
    notification_id INT4,
    top_thread_message_id INT8,
    PRIMARY KEY (dialog_id, message_id)
)
```

**Indexing strategy:**
- 30 partial indices on `(dialog_id, message_id)` using `index_mask` bitmask --
  one per `MessageSearchFilter` type. Each index is:
  ```sql
  CREATE INDEX message_index_N ON messages (dialog_id, message_id)
    WHERE (index_mask & (1 << N)) != 0
  ```
- FTS5 virtual table `messages_fts` for full-text search, with insert/delete triggers
- Partial index on `search_id` (WHERE IS NOT NULL)
- Partial index on `random_id` (WHERE IS NOT NULL)
- Partial index on `unique_message_id` (WHERE IS NOT NULL)
- Partial index on `ttl_expires_at` (WHERE IS NOT NULL)
- Partial index on `notification_id` (WHERE IS NOT NULL)

#### `dialogs` table
```sql
CREATE TABLE dialogs (
    dialog_id INT8 PRIMARY KEY,
    dialog_order INT8,
    data BLOB,
    folder_id INT4
)
```
- Index: `(folder_id, dialog_order, dialog_id) WHERE folder_id IS NOT NULL`

#### `stories` / `active_stories` / `active_story_lists`
```sql
CREATE TABLE stories (
    dialog_id INT8, story_id INT4,
    expires_at INT4, notification_id INT4,
    data BLOB,
    PRIMARY KEY (dialog_id, story_id)
)

CREATE TABLE active_stories (
    dialog_id INT8 PRIMARY KEY,
    story_list_id INT4, dialog_order INT8,
    data BLOB
)

CREATE TABLE active_story_lists (
    story_list_id INT4 PRIMARY KEY,
    data BLOB
)
```
- Partial index on `expires_at` for TTL expiration cleanup
- Partial index on `notification_id`
- Composite index `(story_list_id, dialog_order, dialog_id)` for feed ordering

#### `notification_groups`
```sql
CREATE TABLE notification_groups (
    notification_group_id INT4 PRIMARY KEY,
    dialog_id INT8,
    last_notification_date INT4
)
```
- Index on `(last_notification_date, dialog_id, notification_group_id)`

### Teleframe Indexing

Teleframe generates content-addressed index names to avoid PG name collisions:
```php
$table->index('col', 'ix_' . substr(sha1($table . ':' . $col), 0, 24));
```

Every `*_id` column and every ref column gets an index. This creates a much
heavier index footprint than TDLib's selective approach.

**Key difference**: TDLib indexes only what it queries. Teleframe indexes
everything, accepting write overhead for query flexibility.

---

## Merge / Upsert Strategy

### TDLib: Dirty-Flag Conditional Save + Full-Entity Overwrite

TDLib uses a sophisticated dirty-flag system. Each in-memory entity has per-field
change flags:

```cpp
// From UserManager.h - User struct
bool is_name_changed = true;
bool is_username_changed = true;
bool is_photo_changed = true;
bool is_is_contact_changed = true;
bool is_status_changed = true;
// ... etc (20+ change flags per entity)
```

The merge logic in `update_user()` (line 9872 of UserManager.cpp):
1. New data arrives from the server (update or full response)
2. TDLib compares each field against the in-memory cache
3. Only changed fields update the struct and set dirty flags
4. Side effects fire per flag: `on_dialog_title_updated`, `on_dialog_photo_updated`, etc.
5. `save_user()` checks dirty flags; if `is_saved && is_status_saved` and no dirty
   flags remain, it skips the write
6. On write, the **entire entity blob** is written via `INSERT OR REPLACE`:
   ```cpp
   G()->td_db()->get_sqlite_pmc()->set(
       get_user_database_key(user_id),
       log_event_store(*u).as_slice().str(),
       callback);
   ```

**Two-phase persistence:**
1. **Binlog** (crash recovery): `binlog_add()` or `binlog_rewrite()` with the
   entity's `log_event_id`. On successful SQLite save, the binlog event is erased.
2. **SQLite PMC** (read cache): Full entity blob overwrite.

**Merge conflict resolution**: Newer data always wins. TDLib trusts the server.
If a "min" user (partial data) arrives, it only updates `access_hash` if the
current value is missing or stale. Full server responses always replace.

### Teleframe: Application-Level Idempotency

Teleframe's `tl_*` tables have no DB-level unique constraint for scoped-ID types
(message-like). The ingest layer handles idempotency at the application level.
New data arrives as `UpdateStored` events and is processed through the pipeline.

**Key difference**: TDLib does conditional writes (skip if unchanged). Teleframe
always writes, relying on the pipeline and Postgres upsert semantics.

---

## Peer Resolution Pattern

### TDLib: In-Memory Manager Pattern

TDLib does NOT store peers in a normalized peer table. Instead:
- Each manager (UserManager, ChatManager) maintains an in-memory hash map:
  `users_[user_id]`, `chats_[chat_id]`, `channels_[channel_id]`
- Peer references in messages are stored as inline `PeerId` values (64-bit
  packed `(type, id)`)
- Loading from database: `load_user_from_database_impl()` fetches the blob,
  deserializes it into the struct, and inserts into the in-memory map
- Resolution is O(1) hash map lookup after the initial load

**Peer type dispatch:**
```cpp
// PeerId packs type in upper bits
// DialogId for users = PeerId(user_id)
// DialogId for chats = PeerId(chat_id)  
// DialogId for channels = PeerId(channel_id)
```

### Teleframe: Column-Based References

Teleframe stores peer references as `bigInteger` columns. Peer type info is
encoded via `PeerIdTool` as canonical longs. Cross-type FKs are deferred
and bucketed (512 per migration file).

**Key difference**: TDLib resolves peers through in-memory manager singletons.
Teleframe resolves peers through Postgres FK constraints and SQL joins.

---

## File Reference Lifecycle

### TDLib: Embedded BLOB + Source-Based Repair

File references in TDLib are embedded directly inside the serialized `RemoteFileLocation`:

```cpp
struct RemoteFileLocation {
    DcId dc_id_;
    string file_reference_;  // opaque bytes from server
    Variant<WebRemoteFileLocation, PhotoRemoteFileLocation,
            CommonRemoteFileLocation> variant_;
};
```

**Storage**: File metadata is stored in the `files` KV table, keyed by:
- Remote location hash (for server-side lookups)
- Local path hash (for filesystem lookups)
- Generate path hash (for partial downloads)

**File reference lifecycle:**
1. **Acquisition**: Server returns `file_reference` bytes with file info
2. **Usage**: Client includes `file_reference` in `InputFileLocation` RPC calls
3. **Expiry**: Server returns `FILE_REFERENCE_EXPIRED` error (code 406)
4. **Invalidation**: `delete_file_reference()` sets reference to `"#"` (invalid)
5. **Repair**: `FileManager::on_file_reference_repaired()` triggers
   `context_->repair_file_reference()` which re-fetches the entity that
   owns the reference (message, chat, etc.) to get a fresh reference
6. **Source tracking**: Each file maintains `FileSourceId` links to the
   original entities (messages, chats) that reference it. On expiry,
   TDLib walks these sources to find a valid reference.

**Flags for repair state:**
```cpp
bool download_was_update_file_reference_ = false;
bool upload_was_update_file_reference_ = false;
```

When download/upload fails with expired reference, the flag is set. On
next attempt, TDLib checks the flag and triggers a repair cycle before retrying.

### Teleframe: Column-Based File References

Teleframe stores file references as columns in `tl_input_file_location_*` and
related tables. File reference expiry is handled at the MTProto transport layer
(see `tdlib-error-recovery.md`). The `file_reference` bytes live in a
`binary` column and are passed through to the wire layer.

**Key difference**: TDLib integrates file reference repair deeply into the
FileManager actor system with source tracking. Teleframe relies on the transport
layer's error recovery path.

---

## Schema Versioning & Migrations

### TDLib: PRAGMA user_version + Drop-and-Recreate

TDLib uses SQLite's `PRAGMA user_version` for schema versioning. The version
is a monotonically increasing integer:

```cpp
// Version.h
enum class DbVersion : int32 {
    CreateDialogDb = 3,
    AddMessageDbMediaIndex,
    AddMessageDb30MediaIndex,
    AddMessageDbFts,
    AddMessagesCallIndex,
    FixFileRemoteLocationKeyBug,
    AddNotificationsSupport,
    AddFolders,
    AddScheduledMessages,
    StorePinnedDialogsInBinlog,
    AddMessageThreadSupport,
    AddMessageThreadDatabase,
    Next
};
```

**Migration strategy:**
1. Read `PRAGMA user_version` on startup
2. If version > current: **drop everything** and recreate (schema too new)
3. If version < needed: apply incremental migrations:
   ```cpp
   if (version < static_cast<int32>(DbVersion::AddFolders)) {
       TRY_STATUS(db.exec("ALTER TABLE dialogs ADD COLUMN folder_id INT4"));
       TRY_STATUS(add_dialogs_in_folder_index());
   }
   ```
4. Set `PRAGMA user_version` to current after migration
5. **All migrations run inside a single BEGIN TRANSACTION**

**TL serialization versioning**: Each entity struct has a `CACHE_VERSION`:
```cpp
static constexpr uint32 CACHE_VERSION = 4;
uint32 cache_version = 0;
```
When deserializing, if the cache version doesn't match, the entity is discarded
and re-fetched from the server. This allows the binary format to evolve without
needing to parse old formats.

### Teleframe: Laravel Migrations

Teleframe uses Laravel's migration system:
- Sequential numbered migration files (`2026_08_28_000002_create_tl_*_tables.php`)
- Schema::create / Schema::table with Blueprint
- Deferred cross-type FKs in bucketed ALTER files
- Regeneration via `php artisan teleframe:regenerate --ship`
- The `@generated` marker prevents hand-editing

**Key difference**: TDLib can drop and recreate tables freely (single-user
desktop app). Teleframe must preserve data across deployments (server-side,
multi-tenant). TDLib's versioning is simpler because the only "user" is the
local client.

---

## Comparison with Teleframe's tl_* Schema

| Dimension | TDLib | Teleframe | Implication |
|---|---|---|---|
| **Granularity** | 1 blob per entity | 1 column per field | TDLib: efficient bulk reads; Teleframe: flexible queries |
| **Query power** | KV lookup only for most entities | Full SQL with JOINs, WHERE, aggregates | Teleframe enables analytics and complex lookups |
| **Write pattern** | Full overwrite (`INSERT OR REPLACE`) | Per-field update (potentially) | TDLib simpler but less efficient for partial updates |
| **Index strategy** | Selective: only query-path indices | Comprehensive: every ID column indexed | TDLib faster writes; Teleframe better read coverage |
| **Schema evolution** | Drop-and-recreate + CACHE_VERSION | Incremental Laravel migrations | Teleframe preserves data across upgrades |
| **Multi-tenancy** | Not supported (single-user) | `account_id` column on every table | Teleframe has isolation overhead |
| **Peer resolution** | In-memory hash map (O(1)) | SQL JOINs via FK constraints | TDLib faster; Teleframe more consistent |
| **File references** | Embedded in serialized blob | Separate typed columns | Teleframe more queryable; TDLib more compact |
| **Normalization** | Zero (everything is a blob) | High (every param is a column) | TDLib avoids NULL overhead; Teleframe avoids serialization |
| **Update tracking** | 20+ dirty flags per entity | `updated_at` timestamp | TDLib more granular; Teleframe simpler |
| **Binlog / WAL** | Append-only binlog for crash recovery | Postgres WAL (built-in) | Equivalent; both have crash safety |
| **Bloat** | 1-5KB per entity blob | Variable, often 100-500 bytes per row | TDLib uses more storage for large entities |
| **Partial updates** | Not possible (full blob rewrite) | Native SQL UPDATE SET col=val | Teleframe wins for hot-path partial updates |

---

## Schema Improvement Recommendations

### 1. Adopt Selective Indexing (High Value, Low Risk)

TDLib indexes only columns that appear in actual query paths. Teleframe indexes
every `*_id` column. Recommendation: audit actual query patterns from the
Ingest pipeline, handler matcher, and `UpdateDispatcher` to identify which
columns are actually queried. Remove indices on columns that are only written,
never read. This reduces write amplification on the hot ingest path.

**Effort**: Low (modify `MigrationGenerator::indexLine` conditions)
**Impact**: Write throughput improvement, reduced PG index maintenance

### 2. Add Conditional/Partial Indices (Medium Value, Low Risk)

TDLib's partial indices (`WHERE col IS NOT NULL`) avoid indexing NULL values.
Teleframe's schema has extensive nullable columns (all param columns are
nullable). Recommendation: add partial indices for frequently-queried columns
where NULL filtering is common:

```sql
-- Instead of:
CREATE INDEX ix_... ON tl_user_user (tl_id);
-- Use:
CREATE INDEX ix_... ON tl_user_user (tl_id) WHERE tl_id IS NOT NULL;
```

This reduces index size and improves maintenance performance on sparse tables.

**Effort**: Low (modify MigrationGenerator)
**Impact**: Reduced index bloat, faster writes on sparse tables

### 3. Consider a Entity-Level Cache Table (High Value, Medium Risk)

TDLib's blob-per-entity pattern is extremely fast for full-entity reads
(single KV lookup vs multiple column reads). For entities that are always
read in full (like when hydrating an Update), consider adding an optional
`data` BLOB column as a materialized cache alongside the normalized columns:

```sql
ALTER TABLE tl_user_user ADD COLUMN data_cache BYTEA;
```

Populate on ingest, use for full-entity hydration, fall back to columns for
partial reads. This is a hybrid approach that gets TDLib's read speed for the
common path while preserving Teleframe's queryability.

**Effort**: Medium (requires ingest pipeline changes)
**Impact**: Faster entity hydration for the update pipeline

### 4. Add Dirty-Flag Tracking for Optimized Writes (Medium Value, Medium Risk)

TDLib's 20+ dirty flags per entity allow it to skip writes when nothing changed.
Teleframe could add a lightweight change-tracking mechanism:

```sql
ALTER TABLE tl_user_user ADD COLUMN _change_mask INT4 DEFAULT 0;
```

On ingest, only write rows where the change mask is non-zero. This avoids
the "always write" overhead when processing duplicate or no-op updates.

**Effort**: Medium (requires ingest logic changes)
**Impact**: Reduced write volume, especially for high-frequency update types

### 5. Adopt Versioned Serialization for Schema Evolution (Low Value, Low Risk)

TDLib's `CACHE_VERSION` pattern allows binary format evolution without complex
parsing. Teleframe could adopt a similar version tag on entity rows:

```sql
ALTER TABLE tl_user_user ADD COLUMN _schema_version INT4 DEFAULT 1;
```

When the schema pipeline regenerates tables with new columns, existing rows
keep their old version. Application code can detect version mismatches and
re-fetch from the wire. This enables smoother schema evolution without
requiring data backfill migrations.

**Effort**: Low (add column to generator, add version check to ingest)
**Impact**: Smoother schema upgrades, reduced migration complexity

### 6. Partition Hot Tables by account_id (Medium Value, High Risk)

Teleframe's `account_id` column on every table enables multi-tenancy but adds
index overhead. For high-volume tenants, consider Postgres table partitioning:

```sql
CREATE TABLE tl_user_user (...) PARTITION BY LIST (account_id);
```

This aligns with TDLib's single-tenant efficiency per partition while
maintaining multi-tenant isolation. However, this requires significant
ingest pipeline changes and is a higher-risk recommendation.

**Effort**: High (ingest, query, and migration changes)
**Impact**: Better query performance per-tenant, reduced index scans

### 7. Implement TTL-Based Auto-Cleanup for Ephemeral Entities (Low Value, Low Risk)

TDLib's message table has `ttl_expires_at` with a partial index for cleanup.
Teleframe stores message-like entities (scoped IDs) that may also be ephemeral.
Adding TTL columns and periodic cleanup for message, story, and notification
tables would reduce database bloat over time.

**Effort**: Low (add column, add cleanup command)
**Impact**: Reduced storage growth for long-running instances
