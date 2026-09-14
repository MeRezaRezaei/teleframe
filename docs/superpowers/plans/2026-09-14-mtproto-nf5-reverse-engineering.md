# MTProto NF5 Mirror Reverse-Engineering Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the auto-generated TN5 mirror (25 generated migrations / 400 generated models / 13 legacy tf_* migrations) with a **hand-derived, reverse-engineered NF5 mirror** — migrations, Eloquent models, and relations read directly from the committed MTProto TL sources — verified on MySQL and with the real seeded Telegram account. No more auto-generation of the shipped mirror.

**Architecture:** The MTProto TL files (`schema/sources/*.tl`, Layer 229 catalog, 3224 lines — the authoritative wire truth) are reverse-engineered by hand into NF5 tables per the locked NF5 rules (zero nullable, zero JSON, `account_id` first PK column, FKs, no auto-increment, constructor variant separation). Each domain (identity, messages, media, updates, stars/business, misc, calls) is authored by one sub-agent in its own worktree on a short-lived branch; I merge the worktrees onto a feature branch off `main`, run the gates, then merge to `main`. The mirror stage in `SchemaRegenerator` is disabled (no regeneration), so the shipped mirror is hand-written code that the schema pipeline never touches.

**Tech Stack:** PHP 8.3, Laravel 13 (Illuminate database/schema/eloquent), MySQL (host `teleframe` DB), Testbench+SQLite for migration tests, the committed TL sources as the reverse-engineering truth.

**Spec:**
- `docs/superpowers/specs/2026-09-14-mtproto-reverse-engineering-verbatim.md` (owner's directive — the plan argues from it; re-read after every compaction)
- `docs/superpowers/specs/2026-09-14-teleframe-nf5-mirror-ingest-verbatim.md` (NF5 truth model — why: FK-failure = wrong ingest path clue; two-layer loop prevention; telegram-reliance scopes)
- `docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md` (locked NF5 rules — normalization, type map, PK/FK shapes)
- `schema/sources/TL_telegram_v227.tl` (+ `TL_mtproto_v1.tl`, `TL_secret.tl`) — the MTProto source of truth to read, never edit

## Global Constraints

- **The TL files are sacred**: `schema/sources/*.tl` are READ-ONLY inputs (updated only via `teleframe:schema-update`, never hand-edited).
- **NF5 locked rules** (from the 2026-09-11 design): zero NULLable columns anywhere; zero `json`/`jsonb`/`blob`/`binary` (bytes → hex `TEXT`); every table's PK starts with `account_id BIGINT NOT NULL` + Telegram-supplied keys (+ `position SMALLINT NOT NULL` for vectors); no auto-increment anywhere (Telegram assigns ids — a reverence scope mirrored as-is); `flags.N?true` → `BOOLEAN NOT NULL DEFAULT FALSE`; typed optional fact → 1:1 child table; `Vector<X>` → 1:N child with `position`; multi-ctor unions → `constructor VARCHAR(64) NOT NULL` discriminator (single-ctor tables omit it); service-message constructors live in their own table (`tf_messages_service`), never nulls in `tf_messages`; `Peer` fields → inline `peer_type TINYINT NOT NULL` + `peer_id BIGINT NOT NULL` pair; `int`→`INTEGER`, `long`→`BIGINT`, `bool`→`BOOLEAN`, `string`→`TEXT` (bounded→`VARCHAR(n)`), `double`→`DOUBLE PRECISION`; no timestamps/audit columns (`$timestamps = false`).
- **Hand-written artifacts are first-class code** — NO `@generated` banners, not owned by `SchemaRegenerator`, never rewritten by `teleframe:regenerate`.
- **Zero-regex** in `src/Core/**` and `src/Teleframe/**` (allow-list unchanged); `src/Schema/**` keeps its allow-list.
- **Gates after every commit**: `composer verify` (phpunit + phpstan level 5 + regeneration idempotence), `php bin/standalone-smoke.php` exit 0; host MySQL migrate must stay clean.
- **Real-account safety protocol**: read-only verification (nearest-dc/updates state); never deleteAccount/logOut/resetAuthorization/migrate:fresh/db:wipe on the live host DB.
- **Sub-agents work in isolated worktrees** on short-lived `wt/*` branches; I merge worktrees myself onto `feat/nf5-mirror-reverse-engineering`; only I merge final work to `main`. No push until the end (user approves).
- **Verbatim protocol**: after each context compaction, re-read the three verbatims before continuing (the goal survives the 200k limit via the verbatims + this plan + plan ledger).
- **Do NOT emit `[goal:complete]`** unless the forensic P1–P4 AND this reverse-engineering directive are both satisfied.

## Direction of attack (locked)

| # | Source of truth | How it becomes the mirror |
|---|---|---|
| 1 | `TL_telegram_v227.tl` constructors | Read each data-bearing ctor (`message*`, `user*`, `chat*`, `channel*`, `dialog*`, `update*`, `document*`, `photo*`, `webPage*`, `MessageEntity*`, `MessageMedia*`, `StarGift*`, `business*`, ...). Input*/request-only ctors are NEVER mirrored (they are not facts). |
| 2 | NF5 design rules | Applied by hand, constructor-by-constructor. |
| 3 | Existing committed catalog (`2026-09-11-telegram-mirror-catalog.json`) | Advisory index of the 36 parent domains — NOT authoritative; the TL is. Discrepancies resolve to the TL. |
| 4 | Live Telegram | Final gate: migrate on MySQL; real account chunk-sync/nearest-dc proof. |

## Worktree topology

- Feature branch: `feat/nf5-mirror-reverse-engineering` off `main` (created by me).
- One worktree per domain task, each on its own short-lived branch `wt/<domain>` off the feature branch; sub-agent commits there; I merge `wt/<domain>` → feature branch after each task review.

---

### Task 0: Purge the auto-generated mirror surface + re-baseline gates

**Files:**
- Delete: `src/Schema/Generated/migrations/mirror/**` (25 files), `src/Schema/Generated/Models/Mirror/**` (400 models), `src/Schema/Generated/Factories/Mirror/**` (400 factories), `src/Schema/Generated/migrations/2026_08_28_000001..000013_create_tf_*_table.php` (13 legacy auto-generated tf_* migrations), `src/Laravel/Migrations/2026_08_28_000001..000013_create_tf_*_table.php` (13 legacy ship-dial copies).
- Modify: `src/Schema/Generator/SchemaRegenerator.php` (disable the mirror stage — the `mirror()` call around line 112 and the `manifest['mirror']` block around line 126), `src/Schema/Generated/schema-manifest.json` (drop `mirror` section; re-pin golden).
- Modify: tests that asserted the generated mirror (they now assert absence/purge):
  - `tests/Schema/RegenerationGoldenTest.php` (manifest pin re-baselined; mirror-section assertions removed)
  - `tests/Schema/ShipDialGoldenTest.php` (tf_* migration counts now 0; app-owned list unchanged)
  - `tests/Schema/Mirror/**` (generated-mirror tests removed or re-pointed at hand-authored tables)
  - `tests/Schema/GeneratedLoadTest.php`, `tests/Schema/RelationGenerationTest.php` (pointed at `src/Teleframe/Mirror/Models/*` instead)
- Interacts with: Task 1 (analysis doc) must confirm purge list matches committed files.

**Interfaces:**
- Produces: clean tree where `composer verify` is green with NO generated mirror present; `SchemaRegenerator` idempotence still passes (generated surface = auto-generated legacy Tl*/Data surface only).

- [ ] **Step 1: Snapshot purge inventory**
  `git ls-files 'src/Schema/Generated/migrations/mirror/*' 'src/Schema/Generated/Models/Mirror/*' 'src/Schema/Generated/Factories/Mirror/*' 'src/Schema/Generated/migrations/2026_08_28_*' 'src/Laravel/Migrations/2026_08_28_*' > /tmp/purge-list.txt` — verify `wc -l` ≈ 450+ files and every path is generated-mirror (contains `GENERATED — do not edit` banner) before deleting any.

- [ ] **Step 2: Delete + confirm**
  Delete the inventoried files. Confirm `git status` shows only deletes. **Do not delete** app-owned migrations (`user_bindings`, `telegram_apps`, `telegram_accounts`, `tg_update_routing`, `add_user_id`).

- [ ] **Step 3: Disable the mirror regeneration stage**
  In `SchemaRegenerator::regenerate()` remove/guard the mirror call so `bin/regenerate` no longer writes `migrations/mirror` or `Models/Mirror`. Keep the legacy auto-generated surface (Tl*, Data) intact — the purge is the MIRROR only.

- [ ] **Step 4: Re-baseline manifest + golden tests**
  Drop the `mirror` section from `schema-manifest.json`; re-pin its sha in `RegenerationGoldenTest`. Update `ShipDialGoldenTest`, `GeneratedLoadTest`, `RelationGenerationTest`, and the `tests/Schema/Mirror` suite to the purged reality.

- [ ] **Step 5: Gate check**
  Run `composer verify` and `php bin/standalone-smoke.php`. Expected: green with the mirror absent, regeneration idempotent.

- [ ] **Step 6: Commit**
  `git add -A && git commit -m "refactor(schema): purge auto-generated NF5 mirror (owner directive, verbatim #3)"`

---

### Task 1: Reverse-engineering analysis map (the design contract)

**Files:**
- Create: `docs/superpowers/specs/2026-09-14-mtproto-nf5-reverse-engineering-map.md`
- Read: `schema/sources/TL_telegram_v227.tl` (3039 lines), `docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md`, `docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json`
- Consumes: Task 0 purge list.

**Interfaces:**
- Produces (consumed by Tasks 2–7): the authoritative table map — for each parent domain: exact TL constructors to read (line ranges or ctor names), resulting tables (name, PK shape, `constructor` discriminator or not, FK targets), and deferred/ignored ctor list (Input*, request-only, transient).

- [ ] **Step 1: Read the whole wire TL**
  Read `TL_telegram_v227.tl` top to bottom (3039 lines). Note the type system: `Input*` (never mirrored), actual entities (`User`, `Chat`, `Channel`, `Message`, `Dialog`, `Document`, `Photo`, `WebPage`, `Update*`, `MessageEntity*`, `MessageMedia*`, `StickerSet`, `StoryItem`, `StarGift*`, `Business*`, ...). Cross-check against the 36 catalog parents.

- [ ] **Step 2: Domain split**
  Assign every data-bearing ctor to exactly one domain: **identity** (users/chats/channels/dialogs), **messages** (message ctors + entities + media + actions + replies), **media** (documents/photos/webpages/sticker sets/attachments), **updates** (update ctors + state + channel participants), **stars/business** (star gifts/transactions/subscriptions, business links/info/intro + bot apps/attach menu), **misc** (folders, saved dialogs, themes, quick replies, todo, encrypted chats, group calls, phone calls, admin log events).

- [ ] **Step 3: Write the map**
  Produce the map doc: per domain, a table of `TL ctor(s) → NF5 table(s)` with PK/FK shapes and NF5 rule applied (discriminator / 1:1 child / vector child / peer pair / hex bytes). Include an explicit **NOT mirrored** appendix (Input*, request-only, RPC wrappers, mtproto/secret layers).

- [ ] **Step 4: Self-review against the verbatim**
  Re-read the 2026-09-14 reverse-engineering verbatim. Confirm the map covers "the full correct migration and models and relations" intent and that nothing factual is deferred without a reason (defer with a one-line rationale per item).

- [ ] **Step 5: Commit**
  `git add docs/superpowers/specs/2026-09-14-mtproto-nf5-reverse-engineering-map.md && git commit -m "docs(schema): MTProto reverse-engineering map (hand-derived NF5 contract)"`

---

### Task 2: Domain — identity (users, chats, channels, dialogs)

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_200001_create_tf_users_tables.php`, `..._200002_create_tf_chats_tables.php`, `..._200003_create_tf_channels_tables.php`, `..._200004_create_tf_dialogs_tables.php` (hand-written per the map; children tables for multi-value facts inside the same file, e.g. `tf_users_usernames`, `tf_chats_participants` where the TL shows vectors).
- Create: `src/Teleframe/Mirror/Models/TfUser.php`, `TfChat.php`, `TfChannel.php`, `TfDialog.php` (+ child models) — namespace `MeRezaRezaei\Teleframe\Mirror\Models`, extending the account-scoped base model pattern (`TfMirrorModel`/`AccountScoped` from `src/Schema/Eloquent/`), `$timestamps = false`, casts per the NF5 type map.
- Test: `tests/Mirror/IdentityMirrorTest.php`
- Consumes: Task 1 map §identity; `src/Schema/Eloquent/{TfMirrorModel,AccountScoped}.php`.

**Interfaces:**
- Consumes: exact TL ctors per the map (e.g. `user`, `userEmpty`, `userProfilePhoto`, `userStatus*`, `chat`, `chatForbidden`, `channel`, `channelForbidden`, `dialog`, `dialogFolder`, ...).
- Produces: `TfUser`, `TfChat`, `TfChannel`, `TfDialog` relations (`accounts()`, `messages()` when later tasks land — stub with `hasMany`/`belongsTo` declared so Task 3+ links them).

- [ ] **Step 1: Read the identity TL ctors** (map §identity list) — extract every field and its type/`flags.N?` shape.
- [ ] **Step 2: Write the identity migrations** — apply NF5 rules by hand; `down()` drops in reverse FK order.
- [ ] **Step 3: Write the models** — casts, `$table`, guarded, relations declared, `$timestamps = false`, `incrementing = false`, `keyType = 'int'`.
- [ ] **Step 4: Write the failing test** — migrate on a `:memory:` schema, insert a representative row, assert PK/FK columns exist and a relation resolves.
- [ ] **Step 5: Run test to green + phpstan on the new files** — `vendor/bin/phpunit tests/Mirror/IdentityMirrorTest.php`, `vendor/bin/phpstan analyse src/Teleframe/Mirror src/Laravel/Migrations --no-progress`.
- [ ] **Step 6: Commit** on `wt/identity`.

---

### Task 3: Domain — messages (content + service + entities + media + actions)

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php` (tf_messages + tf_messages_service + children: fwd_from, reply_to, entities, media, actions per the map), `..._200011_create_tf_message_entities_tables.php`, `..._200012_create_tf_message_medias_tables.php`.
- Create: `src/Teleframe/Mirror/Models/TfMessage.php`, `TfMessageService.php`, `TfMessageEntity.php`, `TfMessageMedia.php`, `TfMessageAction.php` (+ children).
- Test: `tests/Mirror/MessageMirrorTest.php`
- Consumes: Task 1 map §messages; Task 2 tables (peer/user/channel FKs).

**Interfaces:**
- Produces: `TfMessage::entities/media/from/fwdFrom/replyTo` relations; `TfMessageService` discriminated by `constructor`; cross-table union documented in the map (`UNION ALL` over `tf_messages` + `tf_messages_service`).

- [ ] **Step 1: Read message TL ctors** — `message`, `messageService`, `messageFwdHeader`, `messageReplyHeader`, `MessageEntity*` (30+ ctors), `MessageMedia*` (20+ ctors), `MessageAction*` (40+ ctors), `messageMedia*`/`messageAction*` flag shapes.
- [ ] **Step 2: Write the message migrations** — `tf_messages` (content) and `tf_messages_service` (discriminator tables) per the NF5 spec §split; children for vectors/optionals.
- [ ] **Step 3: Write the models** — with the relations to identity tables.
- [ ] **Step 4: Write the failing test** — a content message with entities + media inserts across children; a service message lands in `tf_messages_service`.
- [ ] **Step 5: Test + phpstan green.**
- [ ] **Step 6: Commit** on `wt/messages`.

---

### Task 4: Domain — media (documents, photos, web pages, sticker sets)

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_200020_create_tf_documents_tables.php`, `..._200021_create_tf_photos_tables.php`, `..._200022_create_tf_web_pages_tables.php`, `..._200023_create_tf_sticker_sets_tables.php` (+ children per the TL vectors, e.g. `tf_documents_attributes`, `tf_sticker_sets_stickers`).
- Create: models `TfDocument.php`, `TfPhoto.php`, `TfWebPage.php`, `TfStickerSet.php` (+ children).
- Test: `tests/Mirror/MediaMirrorTest.php`
- Consumes: Task 1 map §media; Task 3 message-media link table if the TL shows embedded media.

**Interfaces:**
- Produces: media models with `messages()` relations; `access_hash`/`file_reference` hex `TEXT` columns (R4: hex2bin at the MTProto call site).

- [ ] **Step 1: Read media TL ctors** — `document`, `photo`, `webPage`, `stickerSet`, `DocumentAttribute*`, `PhotoSize*`, `fileReference`/`accessHash` shapes.
- [ ] **Step 2: Write the media migrations.**
- [ ] **Step 3: Write the models.**
- [ ] **Step 4: Failing test** — media rows with hex `file_reference`; sticker set ↔ sticker vector child.
- [ ] **Step 5: Test + phpstan green.**
- [ ] **Step 6: Commit** on `wt/media`.

---

### Task 5: Domain — updates (update ctors + state + channel participants)

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_200030_create_tf_updates_tables.php` (tf_updates + tf_channel_participants + pts/qts state table per the truth model), `..._200031_create_tf_channel_participants_tables.php`.
- Create: `src/Teleframe/Mirror/Models/TfUpdate.php`, `TfChannelParticipant.php`, `TfUpdateState.php`.
- Test: `tests/Mirror/UpdatesMirrorTest.php`
- Consumes: Task 1 map §updates; the truth-model plan's Cycle-3 chunk-sync requirement (state must be storable).

**Interfaces:**
- Produces: `TfUpdateState` (account_id PK + pts/qts/date/seq) — the seam `updates.getState`/`getDifference` writes into; `TfUpdate` stores each update fact as rows (dispatch target for the future ingester).

- [ ] **Step 1: Read update TL ctors** — `update*` family (150+ ctors): decide per the map which are fact-bearing (stored) vs. transient (ignored). Check `updates.state`/`updates.difference` shapes.
- [ ] **Step 2: Write the updates migrations** — including the state table (pts/qts/seq) that the chunk-sync writes.
- [ ] **Step 3: Write the models.**
- [ ] **Step 4: Failing test** — state row upsert; a fact-bearing update row stores its payload columns; transient ctors (documented) have no table.
- [ ] **Step 5: Test + phpstan green.**
- [ ] **Step 6: Commit** on `wt/updates`.

---

### Task 6: Domain — stars/business (star gifts, transactions, subscriptions, business, bots)

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php` (tf_stars_transactions, tf_stars_subscriptions, tf_saved_star_gifts, tf_business_* per the map) — single migration file for this domain unless the TL demands splits.
- Create: models per table.
- Test: `tests/Mirror/StarsBusinessMirrorTest.php`
- Consumes: Task 1 map §stars/business.

**Interfaces:**
- Produces: star/business models; note in the map doc whether any ctor lacks NF5-storable fields (defer with rationale).

- [ ] **Step 1: Read stars/business TL ctors** (per the map §stars/business).
- [ ] **Step 2: Write migrations + models.**
- [ ] **Step 3: Failing test** — a representative star-gift row + business link row insert with FKs.
- [ ] **Step 4: Test + phpstan green.**
- [ ] **Step 5: Commit** on `wt/stars-business`.

---

### Task 7: Domain — misc (folders, saved dialogs, themes, quick replies, todo, encrypted, calls, admin log)

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_200050_create_tf_misc_tables.php` (per the map §misc).
- Create: models per table.
- Test: `tests/Mirror/MiscMirrorTest.php`
- Consumes: Task 1 map §misc.

**Interfaces:**
- Produces: misc models; each table's NF5 justification cited from the TL (no speculative tables).

- [ ] **Step 1: Read misc TL ctors** (per the map §misc).
- [ ] **Step 2: Write migrations + models.**
- [ ] **Step 3: Failing test.**
- [ ] **Step 4: Test + phpstan green.**
- [ ] **Step 5: Commit** on `wt/misc`.

---

### Task 8: Cross-domain FK wiring + relation completeness

**Files:**
- Create: `src/Laravel/Migrations/2026_09_14_299999_create_tf_foreign_keys.php` (cross-table FKs: messages→users/chats/channels, media→messages, updates→messages, participants→chats/channels, star tables→messages/users).
- Modify: every `src/Teleframe/Mirror/Models/*.php` — ensure the full relation surface is declared (belongsTo/hasMany per the FK wiring).
- Test: `tests/Mirror/RelationIntegrityTest.php`

**Interfaces:**
- Consumes: all Task 2–7 tables/models; the NF5 spec's FK rules (`ON DELETE CASCADE` children, `RESTRICT` for `{target}_id` refs).
- Produces: the relational integrity proof — the FK-failure-is-a-clue mechanism the verbatim demands.

- [ ] **Step 1: Write the FK migration** — one file, all cross-table constraints, `down()` drops in reverse.
- [ ] **Step 2: Complete model relations** — every FK pair has a matching Eloquent relation (name per the map).
- [ ] **Step 3: Failing test** — migrate all tables on `:memory:`, insert a parent+child, assert FK violation on orphan insert and cascade on parent delete.
- [ ] **Step 4: Test + phpstan green.**
- [ ] **Step 5: Commit** on the feature branch (via worktree merge).

---

### Task 9: Integration — MySQL host migrate + live-account proof (done by me, not a sub-agent)

**Files:**
- Modify: nothing in the repo; the host app (`teleframe-app`) migrates via `php artisan migrate --force` against MySQL `teleframe`.
- Run: live read-only proof with the seeded real account.

**Interfaces:**
- Consumes: the merged feature branch (Tasks 0–8).

- [ ] **Step 1: Merge all `wt/*` worktrees onto the feature branch** (myself), then run `composer verify` + `php bin/standalone-smoke.php` + `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`.
- [ ] **Step 2: Host MySQL migrate** — in `teleframe-app`, `composer update` the path-repo, `php artisan migrate --force`; assert every tf_* table ships and `migrate:status` is clean.
- [ ] **Step 3: Live proof** — `php scripts/verify-seed.php` (read-only nearest-dc) still passes against the merged code; if the ingester path is ready, a read-only `updates.getState` stores state (write to `tf_update_state` only after explicit user approval — the safety protocol).
- [ ] **Step 4: Update HANDOFF.md** (this cycle's section: purge + reverse-engineering + gates).
- [ ] **Step 5: Merge feature branch → `main` myself**, then commit.

---

## Self-review checklist (run before declaring done)

1. **Spec coverage:** every domain in the reverse-engineering verbatim's intent (purge auto-gen, hand-derive migrations/models/relations from the MTProto, worktrees+sub-agents, merge myself) maps to a task — Task 0 (purge), Tasks 1–7 (hand-derive), Task 8 (relations), Task 9 (merge+live). ✓
2. **Placeholder scan:** no "TBD"; steps name exact ctor families and file paths; resolution rules are the NF5 locked rules. ✓
3. **Type consistency:** `tf_messages`/`tf_messages_service` split, `constructor` discriminator rule, `account_id`-first PK, hex `TEXT` bytes — identical across Tasks 2–8 (single source: the map from Task 1). ✓
4. **Verbatim protocol:** this plan + the three verbatims + the SDD ledger are the compaction survival kit.

## Execution handoff

**Plan complete and saved to `docs/superpowers/plans/2026-09-14-mtproto-nf5-reverse-engineering.md`.**

Per the owner's directive: **Subagent-Driven** execution with worktrees (one implementer sub-agent per domain task, each in its own `wt/<domain>` worktree; I review between tasks, merge worktrees myself, run Task 9 myself). No inline execution choice needed — the verbatim mandates sub-agents + worktrees + my merges.