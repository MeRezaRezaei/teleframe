# Telegram Mirror Schema — Full NF5 Relational Mirror (no JSON, no blob)

**Status:** Active (redesign)
**Date:** 2026-09-11
**Layer:** Wire speaks **Layer 227** (`EncryptedConnection::LAYER`); schema catalog **Layer 229**. The mirror mirrors the **wire** types (what Telegram actually sends a client) — the 227-wire vs 229-schema gap is intentional and never "fixed".
**Replaces:** `2026-09-10-telegram-mirror-schema-design.md` (JSONB-based) and the one-table-per-constructor pattern (393 tables).

## 1. Decision record — why this design

The operator's architecture decisions, in order of precedence:

1. **No JSON columns.** No `json` / `longText(json)` / `jsonb` anywhere. Every fact gets a real relational column or a real relational table.
2. **No blob / binary columns** (`blob`, `varbinary`, `binary(N)`). File **bytes** are stored in **Laravel storage** (`storage/app/telegram/...`); the database holds metadata + a storage path. `bytes` TL fields that are opaque tokens (`file_reference`, `file_unique_id`, `access_hash`, `...`) are stored **hex-encoded as text** (`VARCHAR`), re-encoded with `hex2bin()` at the MTProto call site.
3. **NF5 normalization** — the core reason: *every datum's meaning is absolute given its existence.* A row **existing** means the fact exists; a row **absent** means the fact does not exist. **Zero NULLable columns in the entire schema.**
4. **Update handling is separate from state storage.** The DB is the **state projection** of Telegram's current data; updates flow through the Redis bus (`tg:stream:updates` → observer → mirror writes). Update events themselves are **not mirrored** as tables.
5. **Exact mirror of what Telegram persists** — extracted from the official TL schema `schema/sources/TL_telegram_v227.tl` (the MTProto wire), cross-checked against TDLib's official persistence philosophy (TDLib serializes user/chat objects and stores per-type message content; we go **further** than TDLib by fully normalizing instead of storing TL blobs).

## 2. Source of truth & reproducibility

- **Input:** `schema/sources/TL_telegram_v227.tl` (types section only, up to `---functions---`).
- **Parser:** 1508 constructors across 601 types extracted.
- **Classifier:** deterministic curated mapping (see section 4) — a type is **mirrored** iff Telegram persists instances of it as first-class state; everything else is **ephemeral**.
- **Artifacts:** this spec + `2026-09-11-telegram-mirror-catalog.json` (machine-readable catalog, committed). Both regenerate from the TL file by the schema pipeline.

## 3. The three-layer classification

Every TL type resolves to exactly one of:

```
table     — 36 types, top-level mirror table tf_*
child     — 102 types, embedded 1:1 / 1:N child relations under a parent mirror table
ephemeral — 463 types, never persisted (request/response wrappers, Input*, transient state)
```

### How the classification was done (heuristic, from TDLib's persistence behavior)

TDLib persists **user-facing state** — users, chats/channels, messages + per-type content, dialogs, sticker sets, documents/photos (as metadata + file), call/group-call state, and per-entity fact tables for optional fields. It **derives or discards** — filters/input objects, update/response wrappers, search windows, pagination cursors, connection config.

Classification rules applied mechanically:

| Rule | Examples moved to... |
|---|---|
| `Input*` types, request helpers, filters, scopes, keys, reasons | ephemeral (`InputPeer`, `MessagesFilter`, `BotCommandScope`, `PrivacyKey`) |
| Namespaced API wrappers (`auth.*`, `account.*`, `messages.*`, `channels.*`, `updates.*`, `help.*`, `langpack.*`, `payments.*`, `phone.*`, `stats.*`, `stories.*`, `stickers.*`, `upload.*`, `users.*`) | ephemeral |
| Response containers with a `NotModified` / `Empty` twin | ephemeral (`messages.Messages`, `EmojiList`) |
| Transient flow state (WebView sessions, URL auth results, SRP handshake, payment credentials, search positions) | ephemeral |
| `Peer` / `DialogPeer` / `FolderPeer` / `NotifyPeer` — **input/output peer references** | NOT a table; inline `peer_type` + `peer_id` pair on the host row (5NF-correct; No join dependency arises from a 3-variant sum type) |
| Types that only ever appear *inside* a persisted entity | child (e.g. `MessageFwdHeader` inside `Message`) |
| Types Telegram conjugates as its own state | table (see section 4) |

## 4. The 36 mirror tables

| # | TL type(s) | Table |
|---|---|---|
| 1 | `User` (9 ctors) | `tf_users` |
| 2 | `Chat` (5 ctors: chat, channel, ...) | `tf_chats` |
| 3 | `EncryptedChat` (5 ctors) | `tf_encrypted_chats` |
| 4 | `Message` content ctors (`message`) | `tf_messages` |
| 5 | `Message` service ctor (`messageService`) | `tf_messages_service` |
| 6 | `MessageMedia` (19 ctors) | `tf_message_medias` |
| 7 | `MessageEntity` (25 ctors) | `tf_message_entities` |
| 8 | `MessageAction` (67 ctors) | `tf_message_actions` |
| 9 | `Dialog` (2 ctors) | `tf_dialogs` |
| 10 | `SavedDialog` (2 ctors) | `tf_saved_dialogs` |
| 11 | `Folder` (1) | `tf_folders` |
| 12 | `DialogFilter` (3 ctors) | `tf_dialog_filters` |
| 13 | `Document` (2 ctors) | `tf_documents` |
| 14 | `Photo` (2 ctors) | `tf_photos` |
| 15 | `WebPage` (5 ctors) | `tf_web_pages` |
| 16 | `StickerSet` (7+ covered ctors) | `tf_sticker_sets` |
| 17 | `StoryItem` (5 ctors) | `tf_story_items` |
| 18 | `PhoneCall` (6 ctors) | `tf_phone_calls` |
| 19 | `GroupCall` (2 ctors) | `tf_group_calls` |
| 20 | `ForumTopic` (2 ctors) | `tf_forum_topics` |
| 21 | `QuickReply` (1) | `tf_quick_replies` |
| 22 | `ChannelAdminLogEvent` (1) + `ChannelAdminLogEventAction` (52) | `tf_admin_log_events` / `tf_admin_log_event_actions` |
| 23 | `Reaction` (4 ctors) | `tf_reactions` |
| 24 | `SavedReactionTag` (1) | `tf_saved_reaction_tags` |
| 25 | `WallPaper` (3+ nofile ctors) | `tf_wallpapers` |
| 26 | `Theme` (3 ctors) | `tf_themes` |
| 27 | `StarsTransaction` | `tf_stars_transactions` |
| 28 | `SavedStarGift` | `tf_saved_star_gifts` |
| 29 | `StarsSubscription` | `tf_stars_subscriptions` |
| 30 | `TodoList` | `tf_todo_lists` |
| 31 | `TodoItem` | `tf_todo_items` |
| 32 | `AttachMenuBot` | `tf_attach_menu_bots` |
| 33 | `BotApp` | `tf_bot_apps` |
| 34 | `BotInfo` | `tf_bot_infos` |
| 35 | `BotInlineResult` | `tf_bot_inline_results` |
| 36 | `BusinessChatLink` | `tf_business_chat_links` |

### Message vs MessageService — separate tables (operator decision B)

Telegram sends both constructors of one TL `Message` type, but they carry **disjoint** facts (content messages carry `media`/`entities`/`reply_to`...; service messages carry `action`). Per the NF5 rule "constructor variants = distinct entities", they do NOT share `tf_messages` with nulls. Service messages live in their own `tf_messages_service` table. Both share the same PK pattern (`account_id, id`) and the same `peer` columns, so cross-table union queries work for "all messages of a chat" (`UNION ALL`, duplicates impossible due to disjoint id spaces guaranteed by Telegram).

## 5. NF5 decomposition rules (the contract)

> Every datum's meaning is absolute given its existence. Nulls are forbidden because a NULL cannot say *which* of {absent, unknown, not-applicable} it means. Decompose until every fact is a row or a column with a definite value.

1. **`flags.N?true`** (boolean present/absent) → `BOOLEAN NOT NULL DEFAULT FALSE` column. Absence = FALSE, presence = TRUE. **Unambiguous.**
2. **`flags.N?type`** (typed optional fact) → **separate 1:1 child table** under the parent: `tf_{parent}_{fact}`. Row exists ⇒ fact exists; row absent ⇒ fact absent. **Zero NULLs.**
3. **Two flag integers** (`flags` + `flags2`, e.g. `user#...`) — both are inductions over the decomposed facts and are **not stored**. Not storing derivable state is 5NF-correct.
4. **Constructor variants of one TL type** that carry disjoint field sets → separate tables (Message vs MessageService). Variants that are pure *shape* discriminators with overlapping fields → `type` discriminator column (FK to a type lookup) on the parent table (e.g. `MessageEntity`/`MessageAction`).
5. **`Vector<X>` collections** → 1:N child tables (`tf_{parent}_{fact}` with the parent's PK + a `position` ordinal for order-sensitive vectors).
6. **TL scalar types only** — see section 6. No `json`, no `blob`, no `binary`, no `binary(16)` PKs.

### Child table anatomy

Every child table follows one fixed shape:

```sql
CREATE TABLE tf_users_phone (
    account_id BIGINT NOT NULL,
    user_id    BIGINT NOT NULL,          -- FK → tf_users(account_id, id)
    phone      VARCHAR(255) NOT NULL,
    PRIMARY KEY (account_id, user_id),
    FOREIGN KEY (account_id, user_id) REFERENCES tf_users(account_id, id)
);
```

Vector children append `position SMALLINT NOT NULL` to the PK. Fact name = the TL field name verbatim (snake_case), so cross-referencing the schema is trivial.

## 6. Column type mapping (TL → SQL → Laravel cast)

| TL | SQL | Laravel cast | Notes |
|---|---|---|---|
| `int` | `INTEGER NOT NULL` | `integer` | 32-bit signed, Telegram's native time (`date`), counts, ids |
| `long` | `BIGINT NOT NULL` | `integer`/no cast (string-safe) | Telegram ids are signed 64-bit; negative = chat/channel. DB `BIGINT`, model keeps native `(int)` (PHP 64-bit). |
| `bool` | `BOOLEAN NOT NULL DEFAULT FALSE` | `boolean` | from `flags.N?true` |
| `string` | `TEXT NOT NULL` | `string` | message text, titles; unbounded |
| `string` (short, bounded) | `VARCHAR(n) NOT NULL` | `string` | username (32), phone (32), lang_code (16), mime (128), dc_id... bounded set only |
| `double` | `DOUBLE PRECISION NOT NULL` | `float` | geo lat/long, duration, ratings |
| `bytes` | `VARCHAR(max) NOT NULL` | `string` (hex) | `hex2bin()` before MTProto call; see section 8 |
| `Peer`-typed fields | `peer_type TINYINT NOT NULL \n + peer_id BIGINT NOT NULL` | `PeerIdTool` | 1=user 2=chat 3=channel (server scope ids) |
| `Vector<X>` | 1:N child table | relation | position ordinal |
| flags int | **not stored** | — | derivable |
| TL object field (e.g. `photo:UserProfilePhoto`) | child table w/ FK | relation | row existence = fact existence |

### `date` handling

Telegram sends unix **`int`** timestamps. They are stored as `INTEGER` (native `date`), and Laravel casts them to `CarbonImmutable` via an accessor using a `tl_date` cast — the DB keeps Telegram's native integer, the model exposes Carbon. Sorting/range queries are plain integer comparisons, exactly what MTProto expects for `min_date`/`max_date` params.

## 7. Keying strategy

- **Every table** — parent or child — carries `account_id BIGINT NOT NULL` as the first PK column.
- **Parent PK:** `(account_id, id)` where `id` = the Telegram natural id (`message_id`, `chat_id`, `user_id`, `sticker_set_id`...).
- **Child PK:** `(account_id, parent_id)` (+ `position` for vectors). A child row cannot exist without its parent row — enforced by `FOREIGN KEY`.
- Secondary unique index `(account_id, id)` reversed is unnecessary — the PK already covers it. Add `(id, account_id)` index only for cross-account lookups. Default: none, keep it lean.
- `FOREIGN KEY` constraints: **on, enforced** — the mirror must never allow orphan facts. Cascades: `ON DELETE CASCADE` on children (a deleted parent means its facts died), `RESTRICT` on peer references pointing at maybe-not-yet-mirrored rows (see section 9 — bus insert order resolves this).

## 8. File & bytes layer — files in storage, never in DB

### Real binary (Document / Photo / Sticker / Voice / Video / WallPaper)

The primary media metadata tables (`tf_documents`, `tf_photos`, `tf_wallpapers`, ...) carry **no byte columns**. They reference `tf_files`:

| Column | TL | Notes |
|---|---|---|
| `file_id` | `bytes` → HEX | server file_id token |
| `file_unique_id` | `bytes` → HEX | stable unique id |
| `dc_id` | `int` | DC hosting the file |
| `size` | `long` | byte count |
| `mime_type` | `string` | MIME |
| `access_hash` | `bytes` → HEX | server token |
| `storage_path` | `string NULL-able? no` → `VARCHAR` with a **separate presence table** | per NF5: `tf_files` holds metadata; a `tf_file_storage` 1:1 child row *exists* once downloaded: `(account_id, file_id, path, uploaded_at)` — no NULLs, row existence = the file is local |
| `uploaded_at` | `int` | in `tf_file_storage` |

**Disk layout:** `storage/app/telegram/{account_id}/{kind}/{file_unique_id}.{ext}` where `kind` ∈ document, photo, sticker, voice, video, wallpaper, theme, background. The `ext` from MIME. Path **is** the `path` column value.

### Opacity tokens (`bytes` fields — 79 in schema)

`file_reference`, `access_hash`, `file_id`, `hash`, `secret`, `n`, `ga_hash` are opaque server tokens echoed back deltas. They are stored **hex-encoded as plain VARCHAR**; the MTProto serialization layer (`Connection`/encoder) calls `hex2bin()` when constructing request payloads. **No binary column, no base64 bloat** — hex is exactly 2× the bytes, reversible with one stdlib call.

## 9. Update separation — state projection, not event log

```
MTProto connection
  → updates (Layer 227)
    → Redis bus  tg:stream:updates (consumer group "teleclient")
      → observed update by typed handler
        → writes mirror tables (tf_*)   ← state projection
        → emits domain events (UpdateStored, EchoEliminator, ...)
```

- The DB mirrors **current Telegram state only**. `Update` constructors (160 variants) are **not mirrored** — no `tf_update_events` table.
- The bus (existing `src/Teleframe/Ingest/`, `src/Teleframe/Bus/`) is the event layer; the mirror DB is the state layer. CQRS split.
- Idempotent upsert: each observer uses the Telegram natural key + `updated_at_unixtime` (or message `id` monotonicity) — this is TDLib's rule too: apply updates in `seq`/`pts` order, drop older-than-state projections.
- **Insert order dependency** (peer rows may not exist yet): the observer upserts **referenced parent facts first** (peer → message → media → child facts). Foreign keys are enforced, so the pipeline must be BFS by construction — the same net-order TDLib applies.

## 10. What is NOT mirrored (ephemeral — 463 types)

- All `Input*` request types, filters, scopes, keys, reasons, and `*Filter` helpers.
- Namespaced response wrappers (`auth.*`, `account.*`, `messages.*`, `channels.*`, `updates.*`, `help.*`, `langpack.*`, `payments.*`, `phone.*`, `stats.*`, `stories.*`, `stickers.*`, `upload.*`, `users.*`, ...).
- Transient flow state: WebView sessions, URL auth, SRP handshake internals, email/passkey verification, payment credentials.
- Search windows & pagination: `SearchResultsPosition`, `SearchResultsCalendarPeriod`, `HighScore`, `MessageRange`, `RecentMeUrl`, `PopularContact`, `ImportedContact`, `TopPeer*`.
- Connection configuration: `Config`, `DcOption`, `CdnConfig`, `NearestDc`, `PhoneConnection`.
- `Page`/`PageBlock`/`RichText` rendered-webpage graph (deferred, see roadmap — it is queryable via `WebPage` metadata + raw HTML until then).
- Pure value types that only appear as fields of the above (`Bool`, `True`, `Null`, `Error`, `DataJSON`, `JSONValue`).

These definitions are **the** contract for the generated code: any type classified ephemeral must never produce a migration.

## 11. Model layer — TDLib-informed data fetches inside models

The mirror tables get Eloquent models with **data-fetch methods modelling TDLib's canonical queries** — reading TDLib's official query patterns for each entity and transcribing them into model methods:

| TDLib query | Mirror model method |
|---|---|
| `loadMessages(user_id, chat_id, from_message_id, limit)` | `Message::forPeer($chat)->olderThan($fromMessageId)->take($limit)->get()` |
| `loadDialogs(...)` | `Dialog::index($account)` |
| `loadChats`, `loadContacts` | `Chat::index($account)`, `User::contacts($account)` |
| `searchMessages` (TDM text search on message content) | `Message::search($account, $text, $peer)` |
| media via `loadFile` | `Document::download($account, $local)` → `tf_file_storage` |

Every typed relation on a parent model is a real `hasOne` / `hasMany` on the child table (row existence = relation existence — no `nullable` relations). Each child model is `AccountScoped` (existing trait in `src/Schema/Eloquent/`).

## 12. What this schema does NOT contain (deliberately)

- No `json`, no `jsonb`, no `longText(json)` — **nowhere**.
- No `blob`, no `binary(N)`, no `binary(16)` uuid PKs.
- No nullable columns. Every column is `NOT NULL`. Absence is expressed by **row absence** in child tables.
- No event-log / audit tables — the mirror is state; the bus is the log.
- No auto-increment surrogate PKs — Telegram natural ids only, composite with `account_id`.

## 13. Generation pipeline (replaces the old 637-migration pipeline)

The catalog is the contract. Implemented by a new `Nf5MirrorGenerator`:

1. Read `TL_telegram_v227.tl` types section → parse → `catalog.json` (committed).
2. Curated classification (section 3-4) + per-table field SQL types = **migrations** (one `create_tf_*_tables` per parent, child tables in the same migration; then FKs migration).
3. **Models** (parent + child), **factories**, and **relations** from the same catalog.
4. `php artisan teleframe:regenerate --nf5` flag (or dedicated `teleframe:mirror`) — locked to the catalog, never hand-edited output.

Stages & tests:
- **Stage 0 (proof):** `tf_users`, `tf_messages`, `tf_messages_service`, `tf_message_medias`, `tf_message_entities` + children — covers the hottest 90% of mirror traffic. PHPUnit: every migration up + all-columns-present test.
- **Stage 1:** remaining 31 parents.
- **Stage 2:** vector children + order-sensitive positions.
- **Stage 3:** cross-table union query helpers (messages+service), TDLib-query methods.

## 14. The full catalog

### `tf_attach_menu_bots`

TL type: `AttachMenuBot` — constructors: `attachMenuBot`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `bot_id` | `BIGINT NOT NULL` |
| `short_name` | `TEXT NOT NULL` |
| `icons` | `1:N child` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`inactive`, `has_settings`, `request_write_access`, `show_in_attach_menu`, `show_in_side_menu`, `side_menu_disclaimer_needed`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_attach_menu_bots_peer_types` | `peer_types` | `1:N child` |

### `tf_bot_apps`

TL type: `BotApp` — constructors: `botAppNotModified`, `botApp`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `short_name` | `TEXT NOT NULL` |
| `title` | `TEXT NOT NULL` |
| `description` | `TEXT NOT NULL` |
| `photo` | `FK→Photo` |
| `hash` | `BIGINT NOT NULL` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_bot_apps_document` | `document` | `FK→Document` |

### `tf_bot_infos`

TL type: `BotInfo` — constructors: `botInfo`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`has_preview_medias`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_bot_infos_user_id` | `user_id` | `BIGINT NOT NULL` |
| `tf_bot_infos_description` | `description` | `TEXT NOT NULL` |
| `tf_bot_infos_description_photo` | `description_photo` | `FK→Photo` |
| `tf_bot_infos_description_document` | `description_document` | `FK→Document` |
| `tf_bot_infos_commands` | `commands` | `1:N child` |
| `tf_bot_infos_menu_button` | `menu_button` | `FK→BotMenuButton` |
| `tf_bot_infos_privacy_policy_url` | `privacy_policy_url` | `TEXT NOT NULL` |
| `tf_bot_infos_app_settings` | `app_settings` | `FK→BotAppSettings` |
| `tf_bot_infos_verifier_settings` | `verifier_settings` | `FK→BotVerifierSettings` |

### `tf_bot_inline_results`

TL type: `BotInlineResult` — constructors: `botInlineResult`, `botInlineMediaResult`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `TEXT NOT NULL` |
| `type` | `TEXT NOT NULL` |
| `send_message` | `FK→BotInlineMessage` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_bot_inline_results_title` | `title` | `TEXT NOT NULL` |
| `tf_bot_inline_results_description` | `description` | `TEXT NOT NULL` |
| `tf_bot_inline_results_url` | `url` | `TEXT NOT NULL` |
| `tf_bot_inline_results_thumb` | `thumb` | `FK→WebDocument` |
| `tf_bot_inline_results_content` | `content` | `FK→WebDocument` |

### `tf_business_chat_links`

TL type: `BusinessChatLink` — constructors: `businessChatLink`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `link` | `TEXT NOT NULL` |
| `message` | `TEXT NOT NULL` |
| `views` | `INTEGER NOT NULL` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_business_chat_links_entities` | `entities` | `1:N child` |
| `tf_business_chat_links_title` | `title` | `TEXT NOT NULL` |

### `tf_admin_log_events`

TL type: `ChannelAdminLogEvent` — constructors: `channelAdminLogEvent`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `date` | `INTEGER NOT NULL` |
| `user_id` | `BIGINT NOT NULL` |
| `action` | `FK→ChannelAdminLogEventAction` |

### `tf_channel_participants`

TL type: `ChannelParticipant` — constructors: `channelParticipant`, `channelParticipantSelf`, `channelParticipantCreator`, `channelParticipantAdmin`, `channelParticipantBanned`, `channelParticipantLeft`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `user_id` | `BIGINT NOT NULL` |
| `date` | `INTEGER NOT NULL` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_channel_participants_subscription_until_date` | `subscription_until_date` | `INTEGER NOT NULL` |
| `tf_channel_participants_rank` | `rank` | `TEXT NOT NULL` |

### `tf_chats`

TL type: `Chat` — constructors: `chatEmpty`, `chat`, `chatForbidden`, `channel`, `channelForbidden`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `title` | `TEXT NOT NULL` |
| `photo` | `FK→ChatPhoto` |
| `participants_count` | `INTEGER NOT NULL` |
| `date` | `INTEGER NOT NULL` |
| `version` | `INTEGER NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`creator`, `left`, `deactivated`, `call_active`, `call_not_empty`, `noforwards`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_chats_migrated_to` | `migrated_to` | `FK→InputChannel` |
| `tf_chats_admin_rights` | `admin_rights` | `FK→ChatAdminRights` |
| `tf_chats_default_banned_rights` | `default_banned_rights` | `FK→ChatBannedRights` |

### `tf_dialogs`

TL type: `Dialog` — constructors: `dialog`, `dialogFolder`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `peer` | `peer_type TINYINT + peer_id BIGINT` |
| `top_message` | `INTEGER NOT NULL` |
| `read_inbox_max_id` | `INTEGER NOT NULL` |
| `read_outbox_max_id` | `INTEGER NOT NULL` |
| `unread_count` | `INTEGER NOT NULL` |
| `unread_mentions_count` | `INTEGER NOT NULL` |
| `unread_reactions_count` | `INTEGER NOT NULL` |
| `unread_poll_votes_count` | `INTEGER NOT NULL` |
| `notify_settings` | `FK→PeerNotifySettings` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`pinned`, `unread_mark`, `view_forum_as_messages`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_dialogs_pts` | `pts` | `INTEGER NOT NULL` |
| `tf_dialogs_draft` | `draft` | `FK→DraftMessage` |
| `tf_dialogs_folder_id` | `folder_id` | `INTEGER NOT NULL` |
| `tf_dialogs_ttl_period` | `ttl_period` | `INTEGER NOT NULL` |

### `tf_dialog_filters`

TL type: `DialogFilter` — constructors: `dialogFilter`, `dialogFilterDefault`, `dialogFilterChatlist`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |
| `title` | `FK→TextWithEntities` |
| `pinned_peers` | `1:N child` |
| `include_peers` | `1:N child` |
| `exclude_peers` | `1:N child` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`contacts`, `non_contacts`, `groups`, `broadcasts`, `bots`, `exclude_muted`, `exclude_read`, `exclude_archived`, `title_noanimate`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_dialog_filters_emoticon` | `emoticon` | `TEXT NOT NULL` |
| `tf_dialog_filters_color` | `color` | `INTEGER NOT NULL` |

### `tf_documents`

TL type: `Document` — constructors: `documentEmpty`, `document`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `file_reference` | `VARCHAR(255) NOT NULL` |
| `date` | `INTEGER NOT NULL` |
| `mime_type` | `TEXT NOT NULL` |
| `size` | `BIGINT NOT NULL` |
| `dc_id` | `INTEGER NOT NULL` |
| `attributes` | `1:N child` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_documents_thumbs` | `thumbs` | `1:N child` |
| `tf_documents_video_thumbs` | `video_thumbs` | `1:N child` |

### `tf_encrypted_chats`

TL type: `EncryptedChat` — constructors: `encryptedChatEmpty`, `encryptedChatWaiting`, `encryptedChatRequested`, `encryptedChat`, `encryptedChatDiscarded`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `date` | `INTEGER NOT NULL` |
| `admin_id` | `BIGINT NOT NULL` |
| `participant_id` | `BIGINT NOT NULL` |

### `tf_folders`

TL type: `Folder` — constructors: `folder`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |
| `title` | `TEXT NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`autofill_new_broadcasts`, `autofill_public_groups`, `autofill_new_correspondents`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_folders_photo` | `photo` | `FK→ChatPhoto` |

### `tf_forum_topics`

TL type: `ForumTopic` — constructors: `forumTopicDeleted`, `forumTopic`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |

### `tf_group_calls`

TL type: `GroupCall` — constructors: `groupCallDiscarded`, `groupCall`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `duration` | `INTEGER NOT NULL` |

### `tf_messages`

TL type: `Message` — constructors: `messageEmpty`, `message`, `messageService`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |
| `peer_id` | `peer_type TINYINT + peer_id BIGINT` |
| `date` | `INTEGER NOT NULL` |
| `message` | `TEXT NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`out`, `mentioned`, `media_unread`, `silent`, `post`, `from_scheduled`, `legacy`, `edit_hide`, `pinned`, `noforwards`, `invert_media`, `offline`, `video_processing_pending`, `paid_suggested_post_stars`, `paid_suggested_post_ton`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_messages_from_id` | `from_id` | `peer_type TINYINT + peer_id BIGINT` |
| `tf_messages_from_boosts_applied` | `from_boosts_applied` | `INTEGER NOT NULL` |
| `tf_messages_from_rank` | `from_rank` | `TEXT NOT NULL` |
| `tf_messages_saved_peer_id` | `saved_peer_id` | `peer_type TINYINT + peer_id BIGINT` |
| `tf_messages_fwd_from` | `fwd_from` | `FK→MessageFwdHeader` |
| `tf_messages_via_bot_id` | `via_bot_id` | `BIGINT NOT NULL` |
| `tf_messages_via_business_bot_id` | `via_business_bot_id` | `BIGINT NOT NULL` |
| `tf_messages_guestchat_via_from` | `guestchat_via_from` | `peer_type TINYINT + peer_id BIGINT` |
| `tf_messages_reply_to` | `reply_to` | `FK→MessageReplyHeader` |
| `tf_messages_media` | `media` | `FK→MessageMedia` |
| `tf_messages_reply_markup` | `reply_markup` | `FK→ReplyMarkup` |
| `tf_messages_entities` | `entities` | `1:N child` |
| `tf_messages_views` | `views` | `INTEGER NOT NULL` |
| `tf_messages_forwards` | `forwards` | `INTEGER NOT NULL` |
| `tf_messages_replies` | `replies` | `FK→MessageReplies` |
| `tf_messages_edit_date` | `edit_date` | `INTEGER NOT NULL` |
| `tf_messages_post_author` | `post_author` | `TEXT NOT NULL` |
| `tf_messages_grouped_id` | `grouped_id` | `BIGINT NOT NULL` |
| `tf_messages_reactions` | `reactions` | `FK→MessageReactions` |
| `tf_messages_restriction_reason` | `restriction_reason` | `1:N child` |
| `tf_messages_ttl_period` | `ttl_period` | `INTEGER NOT NULL` |
| `tf_messages_quick_reply_shortcut_id` | `quick_reply_shortcut_id` | `INTEGER NOT NULL` |
| `tf_messages_effect` | `effect` | `BIGINT NOT NULL` |
| `tf_messages_factcheck` | `factcheck` | `FK→FactCheck` |
| `tf_messages_report_delivery_until_date` | `report_delivery_until_date` | `INTEGER NOT NULL` |
| `tf_messages_paid_message_stars` | `paid_message_stars` | `BIGINT NOT NULL` |
| `tf_messages_suggested_post` | `suggested_post` | `FK→SuggestedPost` |
| `tf_messages_schedule_repeat_period` | `schedule_repeat_period` | `INTEGER NOT NULL` |
| `tf_messages_summary_from_language` | `summary_from_language` | `TEXT NOT NULL` |
| `tf_messages_rich_message` | `rich_message` | `FK→RichMessage` |

### `tf_message_actions`

TL type: `MessageAction` — constructors: `messageActionEmpty`, `messageActionChatCreate`, `messageActionChatEditTitle`, `messageActionChatEditPhoto`, `messageActionChatDeletePhoto`, `messageActionChatAddUser`, `messageActionChatDeleteUser`, `messageActionChatJoinedByLink`, `messageActionChannelCreate`, `messageActionChatMigrateTo`, `messageActionChannelMigrateFrom`, `messageActionPinMessage`, `messageActionHistoryClear`, `messageActionGameScore`, `messageActionPaymentSentMe`, `messageActionPaymentSent`, `messageActionPhoneCall`, `messageActionScreenshotTaken`, `messageActionCustomAction`, `messageActionBotAllowed`, `messageActionSecureValuesSentMe`, `messageActionSecureValuesSent`, `messageActionContactSignUp`, `messageActionGeoProximityReached`, `messageActionGroupCall`, `messageActionInviteToGroupCall`, `messageActionSetMessagesTTL`, `messageActionGroupCallScheduled`, `messageActionSetChatTheme`, `messageActionChatJoinedByRequest`, `messageActionWebViewDataSentMe`, `messageActionWebViewDataSent`, `messageActionGiftPremium`, `messageActionTopicCreate`, `messageActionTopicEdit`, `messageActionSuggestProfilePhoto`, `messageActionRequestedPeer`, `messageActionSetChatWallPaper`, `messageActionGiftCode`, `messageActionGiveawayLaunch`, `messageActionGiveawayResults`, `messageActionBoostApply`, `messageActionRequestedPeerSentMe`, `messageActionPaymentRefunded`, `messageActionGiftStars`, `messageActionPrizeStars`, `messageActionStarGift`, `messageActionStarGiftUnique`, `messageActionPaidMessagesRefunded`, `messageActionPaidMessagesPrice`, `messageActionConferenceCall`, `messageActionTodoCompletions`, `messageActionTodoAppendTasks`, `messageActionSuggestedPostApproval`, `messageActionSuggestedPostSuccess`, `messageActionSuggestedPostRefund`, `messageActionGiftTon`, `messageActionSuggestBirthday`, `messageActionStarGiftPurchaseOffer`, `messageActionStarGiftPurchaseOfferDeclined`, `messageActionNewCreatorPending`, `messageActionChangeCreator`, `messageActionNoForwardsToggle`, `messageActionNoForwardsRequest`, `messageActionPollAppendAnswer`, `messageActionPollDeleteAnswer`, `messageActionManagedBotCreated`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `title` | `TEXT NOT NULL` |
| `users` | `1:N child` |

### `tf_message_entities`

TL type: `MessageEntity` — constructors: `messageEntityUnknown`, `messageEntityMention`, `messageEntityHashtag`, `messageEntityBotCommand`, `messageEntityUrl`, `messageEntityEmail`, `messageEntityBold`, `messageEntityItalic`, `messageEntityCode`, `messageEntityPre`, `messageEntityTextUrl`, `messageEntityMentionName`, `inputMessageEntityMentionName`, `messageEntityPhone`, `messageEntityCashtag`, `messageEntityUnderline`, `messageEntityStrike`, `messageEntityBankCard`, `messageEntitySpoiler`, `messageEntityCustomEmoji`, `messageEntityBlockquote`, `messageEntityFormattedDate`, `messageEntityDiffInsert`, `messageEntityDiffReplace`, `messageEntityDiffDelete`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `offset` | `INTEGER NOT NULL` |
| `length` | `INTEGER NOT NULL` |

### `tf_message_medias`

TL type: `MessageMedia` — constructors: `messageMediaEmpty`, `messageMediaPhoto`, `messageMediaGeo`, `messageMediaContact`, `messageMediaUnsupported`, `messageMediaDocument`, `messageMediaWebPage`, `messageMediaVenue`, `messageMediaGame`, `messageMediaInvoice`, `messageMediaGeoLive`, `messageMediaPoll`, `messageMediaDice`, `messageMediaStory`, `messageMediaGiveaway`, `messageMediaGiveawayResults`, `messageMediaPaidMedia`, `messageMediaToDo`, `messageMediaVideoStream`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`spoiler`, `live_photo`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_message_medias_photo` | `photo` | `FK→Photo` |
| `tf_message_medias_ttl_seconds` | `ttl_seconds` | `INTEGER NOT NULL` |
| `tf_message_medias_video` | `video` | `FK→Document` |

### `tf_phone_calls`

TL type: `PhoneCall` — constructors: `phoneCallEmpty`, `phoneCallWaiting`, `phoneCallRequested`, `phoneCallAccepted`, `phoneCall`, `phoneCallDiscarded`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `date` | `INTEGER NOT NULL` |
| `admin_id` | `BIGINT NOT NULL` |
| `participant_id` | `BIGINT NOT NULL` |
| `protocol` | `FK→PhoneCallProtocol` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`video`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_phone_calls_receive_date` | `receive_date` | `INTEGER NOT NULL` |

### `tf_photos`

TL type: `Photo` — constructors: `photoEmpty`, `photo`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `file_reference` | `VARCHAR(255) NOT NULL` |
| `date` | `INTEGER NOT NULL` |
| `sizes` | `1:N child` |
| `dc_id` | `INTEGER NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`has_stickers`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_photos_video_sizes` | `video_sizes` | `1:N child` |

### `tf_quick_replies`

TL type: `QuickReply` — constructors: `quickReply`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `shortcut_id` | `INTEGER NOT NULL` |
| `shortcut` | `TEXT NOT NULL` |
| `top_message` | `INTEGER NOT NULL` |
| `count` | `INTEGER NOT NULL` |

### `tf_reactions`

TL type: `Reaction` — constructors: `reactionEmpty`, `reactionEmoji`, `reactionCustomEmoji`, `reactionPaid`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `emoticon` | `TEXT NOT NULL` |

### `tf_saved_dialogs`

TL type: `SavedDialog` — constructors: `savedDialog`, `monoForumDialog`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `peer` | `peer_type TINYINT + peer_id BIGINT` |
| `top_message` | `INTEGER NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`pinned`

### `tf_saved_reaction_tags`

TL type: `SavedReactionTag` — constructors: `savedReactionTag`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `reaction` | `FK→Reaction` |
| `count` | `INTEGER NOT NULL` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_saved_reaction_tags_title` | `title` | `TEXT NOT NULL` |

### `tf_saved_star_gifts`

TL type: `SavedStarGift` — constructors: `savedStarGift`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `date` | `INTEGER NOT NULL` |
| `gift` | `FK→StarGift` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`name_hidden`, `unsaved`, `refunded`, `can_upgrade`, `pinned_to_top`, `upgrade_separate`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_saved_star_gifts_from_id` | `from_id` | `peer_type TINYINT + peer_id BIGINT` |
| `tf_saved_star_gifts_message` | `message` | `FK→TextWithEntities` |
| `tf_saved_star_gifts_msg_id` | `msg_id` | `INTEGER NOT NULL` |
| `tf_saved_star_gifts_saved_id` | `saved_id` | `BIGINT NOT NULL` |
| `tf_saved_star_gifts_convert_stars` | `convert_stars` | `BIGINT NOT NULL` |
| `tf_saved_star_gifts_upgrade_stars` | `upgrade_stars` | `BIGINT NOT NULL` |
| `tf_saved_star_gifts_can_export_at` | `can_export_at` | `INTEGER NOT NULL` |
| `tf_saved_star_gifts_transfer_stars` | `transfer_stars` | `BIGINT NOT NULL` |
| `tf_saved_star_gifts_can_transfer_at` | `can_transfer_at` | `INTEGER NOT NULL` |
| `tf_saved_star_gifts_can_resell_at` | `can_resell_at` | `INTEGER NOT NULL` |
| `tf_saved_star_gifts_collection_id` | `collection_id` | `1:N child` |
| `tf_saved_star_gifts_prepaid_upgrade_hash` | `prepaid_upgrade_hash` | `TEXT NOT NULL` |
| `tf_saved_star_gifts_drop_original_details_stars` | `drop_original_details_stars` | `BIGINT NOT NULL` |
| `tf_saved_star_gifts_gift_num` | `gift_num` | `INTEGER NOT NULL` |
| `tf_saved_star_gifts_can_craft_at` | `can_craft_at` | `INTEGER NOT NULL` |

### `tf_stars_subscriptions`

TL type: `StarsSubscription` — constructors: `starsSubscription`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `TEXT NOT NULL` |
| `peer` | `peer_type TINYINT + peer_id BIGINT` |
| `until_date` | `INTEGER NOT NULL` |
| `pricing` | `FK→StarsSubscriptionPricing` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`canceled`, `can_refulfill`, `missing_balance`, `bot_canceled`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_stars_subscriptions_chat_invite_hash` | `chat_invite_hash` | `TEXT NOT NULL` |
| `tf_stars_subscriptions_title` | `title` | `TEXT NOT NULL` |
| `tf_stars_subscriptions_photo` | `photo` | `FK→WebDocument` |
| `tf_stars_subscriptions_invoice_slug` | `invoice_slug` | `TEXT NOT NULL` |

### `tf_stars_transactions`

TL type: `StarsTransaction` — constructors: `starsTransaction`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `TEXT NOT NULL` |
| `amount` | `FK→StarsAmount` |
| `date` | `INTEGER NOT NULL` |
| `peer` | `FK→StarsTransactionPeer` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`refund`, `pending`, `failed`, `gift`, `reaction`, `stargift_upgrade`, `business_transfer`, `stargift_resale`, `posts_search`, `stargift_prepaid_upgrade`, `stargift_drop_original_details`, `phonegroup_message`, `stargift_auction_bid`, `offer`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_stars_transactions_title` | `title` | `TEXT NOT NULL` |
| `tf_stars_transactions_description` | `description` | `TEXT NOT NULL` |
| `tf_stars_transactions_photo` | `photo` | `FK→WebDocument` |
| `tf_stars_transactions_transaction_date` | `transaction_date` | `INTEGER NOT NULL` |
| `tf_stars_transactions_transaction_url` | `transaction_url` | `TEXT NOT NULL` |
| `tf_stars_transactions_bot_payload` | `bot_payload` | `VARCHAR(255) NOT NULL` |
| `tf_stars_transactions_msg_id` | `msg_id` | `INTEGER NOT NULL` |
| `tf_stars_transactions_extended_media` | `extended_media` | `1:N child` |
| `tf_stars_transactions_subscription_period` | `subscription_period` | `INTEGER NOT NULL` |
| `tf_stars_transactions_giveaway_post_id` | `giveaway_post_id` | `INTEGER NOT NULL` |
| `tf_stars_transactions_stargift` | `stargift` | `FK→StarGift` |
| `tf_stars_transactions_floodskip_number` | `floodskip_number` | `INTEGER NOT NULL` |
| `tf_stars_transactions_starref_commission_permille` | `starref_commission_permille` | `INTEGER NOT NULL` |
| `tf_stars_transactions_starref_peer` | `starref_peer` | `peer_type TINYINT + peer_id BIGINT` |
| `tf_stars_transactions_starref_amount` | `starref_amount` | `FK→StarsAmount` |
| `tf_stars_transactions_paid_messages` | `paid_messages` | `INTEGER NOT NULL` |
| `tf_stars_transactions_premium_gift_months` | `premium_gift_months` | `INTEGER NOT NULL` |
| `tf_stars_transactions_ads_proceeds_from_date` | `ads_proceeds_from_date` | `INTEGER NOT NULL` |
| `tf_stars_transactions_ads_proceeds_to_date` | `ads_proceeds_to_date` | `INTEGER NOT NULL` |

### `tf_sticker_sets`

TL type: `StickerSet` — constructors: `stickerSet`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `title` | `TEXT NOT NULL` |
| `short_name` | `TEXT NOT NULL` |
| `count` | `INTEGER NOT NULL` |
| `hash` | `INTEGER NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`archived`, `official`, `masks`, `emojis`, `text_color`, `channel_emoji_status`, `creator`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_sticker_sets_installed_date` | `installed_date` | `INTEGER NOT NULL` |
| `tf_sticker_sets_thumbs` | `thumbs` | `1:N child` |
| `tf_sticker_sets_thumb_dc_id` | `thumb_dc_id` | `INTEGER NOT NULL` |
| `tf_sticker_sets_thumb_version` | `thumb_version` | `INTEGER NOT NULL` |
| `tf_sticker_sets_thumb_document_id` | `thumb_document_id` | `BIGINT NOT NULL` |

### `tf_story_items`

TL type: `StoryItem` — constructors: `storyItemDeleted`, `storyItemSkipped`, `storyItem`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |

### `tf_themes`

TL type: `Theme` — constructors: `theme`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `slug` | `TEXT NOT NULL` |
| `title` | `TEXT NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`creator`, `default`, `for_chat`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_themes_document` | `document` | `FK→Document` |
| `tf_themes_settings` | `settings` | `1:N child` |
| `tf_themes_emoticon` | `emoticon` | `TEXT NOT NULL` |
| `tf_themes_installs_count` | `installs_count` | `INTEGER NOT NULL` |

### `tf_todo_items`

TL type: `TodoItem` — constructors: `todoItem`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `INTEGER NOT NULL` |
| `title` | `FK→TextWithEntities` |

### `tf_todo_lists`

TL type: `TodoList` — constructors: `todoList`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `title` | `FK→TextWithEntities` |
| `list` | `1:N child` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`others_can_append`, `others_can_complete`

### `tf_users`

TL type: `User` — constructors: `userEmpty`, `user`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`self`, `contact`, `mutual_contact`, `deleted`, `bot`, `bot_chat_history`, `bot_nochats`, `verified`, `restricted`, `min`, `bot_inline_geo`, `support`, `scam`, `apply_min_photo`, `fake`, `bot_attach_menu`, `premium`, `attach_menu_enabled`, `bot_can_edit`, `close_friend`, `stories_hidden`, `stories_unavailable`, `contact_require_premium`, `bot_business`, `bot_has_main_app`, `bot_forum_view`, `bot_forum_can_manage_topics`, `bot_can_manage_bots`, `bot_guestchat`, `bot_guard`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_users_access_hash` | `access_hash` | `BIGINT NOT NULL` |
| `tf_users_first_name` | `first_name` | `TEXT NOT NULL` |
| `tf_users_last_name` | `last_name` | `TEXT NOT NULL` |
| `tf_users_username` | `username` | `TEXT NOT NULL` |
| `tf_users_phone` | `phone` | `TEXT NOT NULL` |
| `tf_users_photo` | `photo` | `FK→UserProfilePhoto` |
| `tf_users_status` | `status` | `FK→UserStatus` |
| `tf_users_bot_info_version` | `bot_info_version` | `INTEGER NOT NULL` |
| `tf_users_restriction_reason` | `restriction_reason` | `1:N child` |
| `tf_users_bot_inline_placeholder` | `bot_inline_placeholder` | `TEXT NOT NULL` |
| `tf_users_lang_code` | `lang_code` | `TEXT NOT NULL` |
| `tf_users_emoji_status` | `emoji_status` | `FK→EmojiStatus` |
| `tf_users_usernames` | `usernames` | `1:N child` |
| `tf_users_stories_max_id` | `stories_max_id` | `FK→RecentStory` |
| `tf_users_color` | `color` | `FK→PeerColor` |
| `tf_users_profile_color` | `profile_color` | `FK→PeerColor` |
| `tf_users_bot_active_users` | `bot_active_users` | `INTEGER NOT NULL` |
| `tf_users_bot_verification_icon` | `bot_verification_icon` | `BIGINT NOT NULL` |
| `tf_users_send_paid_messages_stars` | `send_paid_messages_stars` | `BIGINT NOT NULL` |

### `tf_wallpapers`

TL type: `WallPaper` — constructors: `wallPaper`, `wallPaperNoFile`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `access_hash` | `BIGINT NOT NULL` |
| `slug` | `TEXT NOT NULL` |
| `document` | `FK→Document` |

**Presence flags** (`BOOLEAN NOT NULL DEFAULT FALSE`):

`creator`, `default`, `pattern`, `dark`

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_wallpapers_settings` | `settings` | `FK→WallPaperSettings` |

### `tf_web_pages`

TL type: `WebPage` — constructors: `webPageEmpty`, `webPagePending`, `webPage`, `webPageNotModified`

**Base columns** (always present — non-flag fields):

| Column | SQL type |
|---|---|
| `id` | `BIGINT NOT NULL` |
| `date` | `INTEGER NOT NULL` |

**Child fact tables** (1:1 child, or 1:N for vectors — row existence = fact existence):

| Child table | Fact | SQL type |
|---|---|---|
| `tf_web_pages_url` | `url` | `TEXT NOT NULL` |
