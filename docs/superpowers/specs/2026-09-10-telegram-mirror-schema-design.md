# Telegram Mirror Schema — TDLib-Style Domain Tables + JSONB

**Status:** SUPERSEDED — JSONB decision reversed on 2026-09-11.
**Replaces (and now replaced by):** See `2026-09-11-telegram-mirror-schema-nf5-design.md` — full NF5 relational mirror, zero JSON/blob/nullable columns.
**Date:** 2026-09-10 (redesigned)
**Replaces:** The one-table-per-constructor UUID/CTI pattern (393 tables) and the earlier
one-table-per-constructor-with-natural-IDs spec.

## 1. Motivation

The previous design created **one table per TL constructor variant** (393 tables),
producing:
- Repeated-word table names (`tl_messages_message_message`)
- Random hash suffixes for long names (`tl_channels_admin_log_results_ad_a1b2c3d4e5f6`)
- MySQL FK type incompatibility (`bigIncrements unsigned` vs `bigInteger signed`)
- Schema bloat: most constructor variants are ephemeral API response wrappers that
  are never directly queried

TDLib (Telegram's official database library) solves this with **~10 domain tables**
that store the full TL object as a `data BLOB` plus extracted queryable columns.
This is the approach we adopt.

## 2. Core Architecture

### 2.1 Domain Tables

One table per **persisted entity domain** — not per constructor. A TL type like
`Message` has 3 constructors (`messageEmpty`, `message`, `messageService`) but they
all map to **one** `tf_messages` table. The `constructor_id` column discriminates.

```
┌──────────────────────────────────────────────────────────────┐
│  TL Constructor Variants (393 types)                        │
│  messageEmpty, message, messageService,                     │
│  userEmpty, user,                                           │
│  chatEmpty, chat, chatForbidden, channel, channelForbidden  │
│  ...                                                        │
└──────────────────────┬───────────────────────────────────────┘
                       │  domain classification
                       ▼
┌──────────────────────────────────────────────────────────────┐
│  Domain Tables (~15 tables)                                 │
│  tf_users, tf_chats, tf_messages, tf_dialogs, ...           │
│  Each stores: extracted columns + tl_data JSONB             │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Table Schema Template

Every domain table follows this pattern:

```sql
CREATE TABLE tf_<domain> (
    id              BIGINT PRIMARY KEY,     -- auto-increment or Telegram native ID
    constructor_id  INT NOT NULL,           -- CRC32 of the specific constructor
    account_id      BIGINT NOT NULL,        -- multi-tenant scope

    -- Extracted query columns (domain-specific, see §3)
    <query_columns>,

    -- Full TL object as JSON (never loses data)
    tl_data         JSONB NOT NULL,

    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
```

**Why JSONB, not binary BLOB?**
- PostgreSQL JSONB is queryable (`->`, `@>`, GIN index)
- Human-readable for debugging
- No serialization/deserialization library needed
- TDLib uses binary BLOB because SQLite lacks JSON; we have JSONB

### 2.3 ID Strategy

| Entity | Telegram ID | PK Strategy |
|--------|------------|-------------|
| User | `id:long` (globally unique) | `BIGINT PRIMARY KEY` = Telegram user ID |
| Chat | `id:long` (globally unique) | `BIGINT PRIMARY KEY` = Telegram chat ID |
| Channel | `id:long` (globally unique) | `BIGINT PRIMARY KEY` = Telegram channel ID |
| Message | `id:int` (scoped to chat) | `BIGSERIAL` surrogate + `UNIQUE(peer_id, id, account_id)` |
| Dialog | No natural ID | `BIGSERIAL` surrogate + `UNIQUE(peer_id, account_id)` |
| Document | `id:long` (globally unique) | `BIGINT PRIMARY KEY` = Telegram document ID |
| Photo | `id:long` (globally unique) | `BIGINT PRIMARY KEY` = Telegram photo ID |
| StickerSet | `id:long` (globally unique) | `BIGINT PRIMARY KEY` = Telegram sticker set ID |
| Story | composite `(peer_id, id)` | `BIGSERIAL` surrogate + `UNIQUE(peer_id, id, account_id)` |
| Update | none | `BIGSERIAL` surrogate |

### 2.4 Multi-Account Scoping

Every table carries `account_id BIGINT NOT NULL`. Telegram native IDs (User, Chat,
Channel, Document, Photo) are globally unique within Telegram but NOT across accounts
(the same user ID can appear in different accounts' data). Therefore:

- **Global-ID types**: `UNIQUE(id, account_id)` — the Telegram ID + account pair is unique
- **Scoped-ID types**: `UNIQUE(peer_id, id, account_id)` — chat-scoped + account

## 3. Domain Table Definitions

### 3.1 `tf_users` — User entities

Maps TL types: `User`, `userEmpty`

```sql
CREATE TABLE tf_users (
    id              BIGINT NOT NULL,        -- Telegram user ID
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    access_hash     BIGINT,
    first_name      TEXT,
    last_name       TEXT,
    username        TEXT,
    phone           TEXT,
    is_bot          BOOLEAN DEFAULT FALSE,
    is_self         BOOLEAN DEFAULT FALSE,
    is_contact      BOOLEAN DEFAULT FALSE,
    is_premium      BOOLEAN DEFAULT FALSE,
    is_deleted      BOOLEAN DEFAULT FALSE,
    photo_id        BIGINT,                 -- UserProfilePhoto.id (extracted for quick avatar lookup)
    status_type     TEXT,                   -- UserStatus type name: 'online', 'offline', 'recently', etc.
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
CREATE INDEX ix_tf_users_username ON tf_users (username) WHERE username IS NOT NULL;
CREATE INDEX ix_tf_users_phone ON tf_users (phone) WHERE phone IS NOT NULL;
CREATE INDEX ix_tf_users_account ON tf_users (account_id);
```

### 3.2 `tf_chats` — Basic group chat entities

Maps TL types: `Chat`, `chatEmpty`, `chatForbidden`

```sql
CREATE TABLE tf_chats (
    id              BIGINT NOT NULL,        -- Telegram chat ID (negative)
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    title           TEXT,
    participants_count INT,
    version         INT,
    date            INT,                    -- unix timestamp
    is_deactivated  BOOLEAN DEFAULT FALSE,
    is_left         BOOLEAN DEFAULT FALSE,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
```

### 3.3 `tf_channels` — Supergroup / channel entities

Maps TL types: `Channel`, `channelForbidden`

```sql
CREATE TABLE tf_channels (
    id              BIGINT NOT NULL,        -- Telegram channel ID (negative, -100xxx)
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    access_hash     BIGINT,
    title           TEXT,
    username        TEXT,
    date            INT,
    participants_count INT,
    is_broadcast    BOOLEAN DEFAULT FALSE,
    is_megagroup    BOOLEAN DEFAULT FALSE,
    is_verified     BOOLEAN DEFAULT FALSE,
    is_restricted   BOOLEAN DEFAULT FALSE,
    is_left         BOOLEAN DEFAULT FALSE,
    is_forum        BOOLEAN DEFAULT FALSE,
    restriction_reason TEXT,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
CREATE INDEX ix_tf_channels_username ON tf_channels (username) WHERE username IS NOT NULL;
```

### 3.4 `tf_messages` — Messages (largest table)

Maps TL types: `Message`, `messageService`, `messageEmpty`

```sql
CREATE TABLE tf_messages (
    id              BIGSERIAL PRIMARY KEY,  -- surrogate PK
    message_id      INT NOT NULL,           -- Telegram message ID (scoped to chat)
    peer_id         BIGINT NOT NULL,        -- chat/channel ID (Telegram native)
    from_id         BIGINT,                 -- sender user ID (nullable, flagged)
    date            INT NOT NULL,           -- unix timestamp
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    is_out          BOOLEAN DEFAULT FALSE,
    is_mentioned    BOOLEAN DEFAULT FALSE,
    is_silent       BOOLEAN DEFAULT FALSE,
    is_pinned       BOOLEAN DEFAULT FALSE,
    message_text    TEXT,                   -- extracted from message.message for full-text search
    media_type      TEXT,                   -- MessageMedia constructor name: 'messageMediaPhoto', etc.
    reply_to_msg_id INT,                    -- extracted from MessageReplyHeader for threading
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE (peer_id, message_id, account_id)
);
CREATE INDEX ix_tf_messages_peer_date ON tf_messages (peer_id, date);
CREATE INDEX ix_tf_messages_from ON tf_messages (from_id) WHERE from_id IS NOT NULL;
CREATE INDEX ix_tf_messages_account ON tf_messages (account_id);
CREATE INDEX ix_tf_messages_text ON tf_messages USING GIN (to_tsvector('english', message_text));
```

### 3.5 `tf_dialogs` — Dialog / chat state

Maps TL types: `Dialog`, `dialogFolder`

```sql
CREATE TABLE tf_dialogs (
    id              BIGSERIAL PRIMARY KEY,
    peer_id         BIGINT NOT NULL,        -- chat/channel/user ID
    peer_type       TEXT NOT NULL,           -- 'user', 'chat', 'channel'
    account_id      BIGINT NOT NULL,
    top_message_id  INT,
    unread_count    INT DEFAULT 0,
    unread_mentions INT DEFAULT 0,
    is_pinned       BOOLEAN DEFAULT FALSE,
    folder_id       INT DEFAULT 0,
    pts             INT,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE (peer_id, account_id)
);
```

### 3.6 `tf_updates` — Update event log

Maps TL type: `Update` (160 constructors)

```sql
CREATE TABLE tf_updates (
    id              BIGSERIAL PRIMARY KEY,
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    -- Extracted common update fields
    peer_id         BIGINT,                 -- affected peer (for routing)
    message_id      INT,                    -- affected message (for message updates)
    user_id         BIGINT,                 -- affected user (for user updates)
    pts             INT,
    pts_count       INT,
    date            INT,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
CREATE INDEX ix_tf_updates_account ON tf_updates (account_id);
CREATE INDEX ix_tf_updates_peer ON tf_updates (peer_id, account_id);
```

### 3.7 `tf_documents` — Document / file entities

Maps TL types: `Document`, `documentEmpty`

```sql
CREATE TABLE tf_documents (
    id              BIGINT NOT NULL,        -- Telegram document ID
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    access_hash     BIGINT,
    date            INT,
    mime_type       TEXT,
    size            BIGINT,
    dc_id           INT,
    file_reference  BYTEA,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
```

### 3.8 `tf_photos` — Photo entities

Maps TL types: `Photo`, `photoEmpty`

```sql
CREATE TABLE tf_photos (
    id              BIGINT NOT NULL,        -- Telegram photo ID
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    access_hash     BIGINT,
    date            INT,
    dc_id           INT,
    has_stickers    BOOLEAN DEFAULT FALSE,
    file_reference  BYTEA,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
```

### 3.9 `tf_sticker_sets` — Sticker set metadata

Maps TL types: `messages.StickerSet`, `messages.StickerSetNotModified`

```sql
CREATE TABLE tf_sticker_sets (
    id              BIGINT NOT NULL,        -- Telegram sticker set ID
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    access_hash     BIGINT,
    title           TEXT,
    short_name      TEXT,
    count           INT,
    hashes          JSONB,                  -- DocumentAttributeSticker set info
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
```

### 3.10 `tf_stories` — Story items

Maps TL types: `stories.Stories`, `StoryItem`

```sql
CREATE TABLE tf_stories (
    id              BIGSERIAL PRIMARY KEY,
    story_id        INT NOT NULL,
    peer_id         BIGINT NOT NULL,
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    date            INT,
    expire_date     INT,
    caption         TEXT,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE (peer_id, story_id, account_id)
);
```

### 3.11 `tf_wallpapers` — Wallpaper / theme entities

Maps TL types: `WallPaper`, `WallPaperSolid`

```sql
CREATE TABLE tf_wallpapers (
    id              BIGINT NOT NULL,
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    access_hash     BIGINT,
    title           TEXT,
    slug            TEXT,
    document_id     BIGINT,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    PRIMARY KEY (id, account_id)
);
```

### 3.12 `tf_channel_participants` — Channel/supergroup participants

Maps TL types: `channels.ChannelParticipant`

```sql
CREATE TABLE tf_channel_participants (
    id              BIGSERIAL PRIMARY KEY,
    channel_id      BIGINT NOT NULL,
    user_id         BIGINT NOT NULL,
    constructor_id  INT NOT NULL,
    account_id      BIGINT NOT NULL,
    date            INT,
    tl_data         JSONB NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    UNIQUE (channel_id, user_id, account_id)
);
```

### 3.13 `tf_route_tables` — Method route tracking

Unchanged from current design: one row per API method with a `route_id` UUID.

```sql
CREATE TABLE tf_routes (
    id              BIGSERIAL PRIMARY KEY,
    route_id        TEXT NOT NULL UNIQUE,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
```

## 4. TL Type → Domain Table Classification

The generator classifies every TL type into one of:

| Classification | Rule | Target Table |
|---------------|------|-------------|
| `entity_user` | Type name = `User` or has `id:long` + `first_name:string` params | `tf_users` |
| `entity_chat` | Type name = `Chat` or has `id:long` + `title:string` + `participants_count:int` | `tf_chats` |
| `entity_channel` | Type name = `Channel` or has `id:long` + `megagroup:bool` params | `tf_channels` |
| `entity_message` | Type name = `Message` or has `id:int` + `peer_id:Peer` + `date:int` | `tf_messages` |
| `entity_dialog` | Type name = `Dialog` or has `peer:Peer` + `top_message:int` | `tf_dialogs` |
| `entity_update` | Namespace = `updates` or type name starts with `update` | `tf_updates` |
| `entity_document` | Type name = `Document` or has `id:long` + `mime_type:string` | `tf_documents` |
| `entity_photo` | Type name = `Photo` or has `id:long` + `sizes:PhotoSize` | `tf_photos` |
| `entity_sticker_set` | Type name = `StickerSet` or has `id:long` + `short_name:string` | `tf_sticker_sets` |
| `entity_story` | Type name = `StoryItem` or namespace = `stories` | `tf_stories` |
| `entity_wallpaper` | Type name = `WallPaper` or has `slug:string` | `tf_wallpapers` |
| `entity_channel_participant` | Type name = `ChannelParticipant` | `tf_channel_participants` |
| `ephemeral` | API response wrappers (e.g., `messages.Messages`, `users.Users`) | **no table** |
| `primitive` | `Bool`, `True`, `Null`, `Error`, MTProto internals | **no table** |

### 4.1 Classification heuristics

The generator uses these signals (checked in order):

1. **Exact name match** — `User`, `Chat`, `Channel`, `Message`, `Dialog`, `Document`, `Photo`, `StickerSet`, `WallPaper`, `ChannelParticipant`
2. **Namespace match** — `updates.*` → `tf_updates`, `stories.*` → `tf_stories`
3. **Param signature** — heuristic matching on key param combinations
4. **Fallback** — if no match, classify as `ephemeral` (no table generated)

## 5. Mapping TL Constructors to Domain Tables

Multiple constructors of the same TL type map to **one** table:

| TL Type | Constructors | Domain Table | Discriminator |
|---------|-------------|-------------|--------------|
| `Message` | `messageEmpty`, `message`, `messageService` | `tf_messages` | `constructor_id` |
| `User` | `userEmpty`, `user` | `tf_users` | `constructor_id` |
| `Chat` | `chatEmpty`, `chat`, `chatForbidden` | `tf_chats` | `constructor_id` |
| `Chat` also contains `Channel`, `channelForbidden` | | `tf_channels` | `constructor_id` |
| `Document` | `documentEmpty`, `document` | `tf_documents` | `constructor_id` |
| `Photo` | `photoEmpty`, `photo` | `tf_photos` | `constructor_id` |

**The `constructor_id` column** stores the CRC32 of the specific constructor that was
ingested. This allows:
- Filtering by constructor variant (`WHERE constructor_id = 0x95f24ed8` for `message`)
- Detecting empty/null variants (`messageEmpty` has CRC32 different from `message`)
- Future migration detection when Telegram adds new constructors

## 6. JSONB `tl_data` Content

The `tl_data` column stores the **complete** TL object as received from Telegram:

```json
{
  "_": "message",
  "flags": 1234,
  "id": 12345,
  "from_id": {"_": "peerUser", "user_id": 12345},
  "peer_id": {"_": "peerChannel", "channel_id": -1001234567890},
  "date": 1720000000,
  "message": "Hello world",
  "media": {"_": "messageMediaEmpty"},
  "entities": [
    {"_": "messageEntityMention", "offset": 0, "length": 5}
  ],
  "views": 42
}
```

This is the **authoritative store**. Extracted columns (§3) are indexes over this data.
If a field is not in the extracted columns, it is still accessible via `tl_data->>'field'`.

### 6.1 Serialization format

The `UpdateIngestor` serializes TL objects to JSON:
1. Recursively walk the TL object array
2. Convert `Peer`/`InputPeer` to their long encoding (`PeerIdTool::userLong` etc.)
3. Store vectors as JSON arrays
4. Store nested objects as JSON objects
5. Binary data (`bytes`) → base64 encoded string

## 7. Files Changed

### 7.1 Core Generator (rewrite)

| File | Change |
|------|--------|
| `src/Schema/Generator/MigrationGenerator.php` | **Complete rewrite**: emit ~15 domain table migrations instead of 393 constructor tables. Remove `childTable()`, `constructorTable()`. Add `domainTable()` that emits the correct DDL per domain. |
| `src/Schema/Generator/Naming.php` | **Simplify**: remove `constructorTable()`, `childTable()`, `fit()` (hash suffix), `dedupeNamespace()`. Add `domainTable()` returning simple names like `tf_users`. Keep `snake()`, `pascal()`, `column()`, `dbType()`, `cast()`. |
| `src/Schema/Generator/ModelGenerator.php` | **Rewrite**: emit one model per domain table (e.g., `TlUser`) with `$primaryKey`, `$keyType`, `tl_data` cast to `array`. Remove anchor/instance/child model pattern. |
| `src/Schema/Generator/FactoryGenerator.php` | Update for domain tables. |

### 7.2 Eloquent Models (rewrite)

| File | Change |
|------|--------|
| `src/Schema/Eloquent/TlAnchorModel.php` | Rename to `DomainModel.php`. Remove UUID PK logic. Base model with `protected $primaryKey = 'id'`, `protected $keyType = 'int'`, `protected $casts = ['tl_data' => 'array']`. |
| `src/Schema/Eloquent/TlInstanceModel.php` | **Delete** — merged into `DomainModel`. |
| `src/Schema/Eloquent/HasTlChildren.php` | **Delete** — no more child tables. |
| `src/Schema/Eloquent/AccountScoped.php` | Keep — still needed for `account_id` scoping. |
| `src/Schema/Eloquent/PeerResolution.php` | Update `PEER_FQCN_MAP` to point to new model classes. |

### 7.3 Ingest Layer (update)

| File | Change |
|------|--------|
| `src/Teleframe/Ingest/UpdateIngestor.php` | Change storage logic: classify TL type → domain table → JSONB serialize → extract query columns → upsert. |
| `src/Teleframe/Ingest/EntityAggregator.php` | Update to work with domain tables instead of per-constructor tables. |
| `src/Teleframe/Ingest/PayloadWalker.php` | No change (works with raw arrays). |

### 7.4 Tests (rewrite)

| File | Change |
|------|--------|
| `tests/Schema/MigrationGeneratorTest.php` | Expect ~15 domain table DDL outputs instead of 393 |
| `tests/Schema/NamingTest.php` | Test new `domainTable()`, remove `constructorTable()` tests |
| `tests/Schema/ModelGeneratorTest.php` | Expect domain model classes |
| `tests/Ingest/UpdateIngestorTest.php` | Update storage expectations |
| `tests/Standalone/PlainPhpLoadTest.php` | Update smoke checks |

### 7.5 Generated Artifacts (regenerate)

All files under `generated/` are regenerated by `php artisan teleframe:regenerate`.
New output: ~15 migration files + ~15 model files (vs 393 + 393 previously).

## 8. Migration Strategy

### 8.1 Breaking Change

This is a **complete schema rewrite**. The existing 393+ tables with per-constructor
design are incompatible.

**Approach:** Drop and recreate. Existing data is from development. Production
deployments start fresh.

### 8.2 New Migration File Layout

```
2026_08_28_000001_create_tf_users_table.php
2026_08_28_000002_create_tf_chats_table.php
2026_08_28_000003_create_tf_channels_table.php
2026_08_28_000004_create_tf_messages_table.php
2026_08_28_000005_create_tf_dialogs_table.php
2026_08_28_000006_create_tf_updates_table.php
2026_08_28_000007_create_tf_documents_table.php
2026_08_28_000008_create_tf_photos_table.php
2026_08_28_000009_create_tf_sticker_sets_table.php
2026_08_28_000010_create_tf_stories_table.php
2026_08_28_000011_create_tf_wallpapers_table.php
2026_08_28_000012_create_tf_channel_participants_table.php
2026_08_28_000013_create_tf_routes_table.php
2026_08_28_000090_add_tl_foreign_keys.php
```

### 8.3 Down migrations

Each `down()` drops its own table. The FK file drops constraints.

## 9. Query Patterns

### 9.1 Fetch all messages in a chat

```sql
SELECT * FROM tf_messages
WHERE peer_id = -1001234567890 AND account_id = 42
ORDER BY date DESC
LIMIT 50;
```

### 9.2 Full-text search in messages

```sql
SELECT * FROM tf_messages
WHERE account_id = 42
  AND to_tsvector('english', message_text) @@ plainto_tsquery('english', 'hello world');
```

### 9.3 Access nested JSONB fields

```sql
-- Get reply_to message ID from tl_data
SELECT tl_data->'reply_to'->>'reply_to_msg_id' AS reply_id
FROM tf_messages WHERE id = 123;

-- Get all entities from tl_data
SELECT jsonb_array_elements(tl_data->'entities') AS entity
FROM tf_messages WHERE id = 123;
```

### 9.4 Filter by constructor variant

```sql
-- Only full messages (not messageEmpty, not messageService)
SELECT * FROM tf_messages
WHERE constructor_id = 1515228898  -- CRC32 of 'message'
  AND account_id = 42;
```

## 10. Performance Considerations

| Concern | Mitigation |
|---------|-----------|
| JSONB bloat | GIN index on `tl_data` for `@>` queries; partial indexes on extracted columns |
| Full table scan for text search | GIN index on `to_tsvector(message_text)` |
| Multi-account data volume | `account_id` in PK/unique constraints; partitioning-ready |
| Message table size (billions) | Partition by `(peer_id % N)` or by date range |
| JSONB serialization cost | O(n) walk of TL array; acceptable for ingest rate |

## 11. Risks and Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking existing data | High (dev only) | Drop and recreate |
| Classification edge cases | Medium | Heuristics + manual override in `curated-tables.json` |
| JSONB query performance | Low | GIN indexes; extracted columns for hot paths |
| Telegram adds new entity types | Low | New domain table via config; existing tables unaffected |

## 12. Out of Scope

- Table partitioning (future, when data volume demands it)
- Binary TL serialization (JSONB is sufficient for PostgreSQL)
- Data migration tooling (drop-and-recreate for now)
- Handler substrate (not affected)
- Bus/Daemon/Backfill/Backup (use model PKs, don't generate them)
