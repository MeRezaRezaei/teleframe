# Model & relation gap — what can actually be JOINed, and what silently cannot

Companion to `docs/schema/mtproto-data-shape-map.md` (committed `ea613a63`), which maps
*method → shape → reference*. That document answers "which entities does the wire carry, and
which fetcher hydrates them". **This** document answers the follow-up that decides whether
any of it is queryable: *given rows are present, does the schema actually permit relating
them, and in what order must they be written so the relation is not permanently lost?*

**Method.** Migrations and Eloquent models only. No database was connected, no `.env` or
credential file was read, no migration was run, no Telegram call was made. Migrations are
treated as authoritative over any live schema, which may have drifted.

**Correction honoured.** The schema does **not** flatten a `Message` into one wide row.
Scalars live on `tf_messages` (22 columns, `2026_09_14_200010_create_tf_messages_tables.php:174`)
and every optional or non-scalar field lives on its own 1:1 companion table keyed
`(account_id, id)` — 34 companions in that file, plus `tf_messages_entities`
(`...200011:22`) and `tf_messages_media` / `tf_messages_service_action` (`...200012:25,89`).
Reference fields are **present and modelled**, in `{field}_type` / `{field}_id` inline peer
pairs (`...200010:126-131`) and in flattened union children (`...200010:157-176`,
`...200010:205-224`). The `tf_messages` PK is `(account_id, id)` at `...200010:112`. The earlier first-draft claim that reference fields are *absent* is
wrong and is preserved on the record in the map; nothing here repeats it.

---

## 1. The FK graph, as the migrations declare it

**40 foreign keys exist across all `tf_*` tables.** Enumerated from every `->foreign(` in
`src/Laravel/Migrations/`, excluding comments and the two `telegram_*` FKs at
`2026_09_09_000201:25`.

| # | child table(s) | child cols | → parent | on delete | file:line |
|---|---|---|---|---|---|
| 1 | `tf_messages_media` | `account_id, id` | `tf_messages` | CASCADE | `2026_09_14_299999:47-50` |
| 2 | `tf_messages_entities` | `account_id, id` | `tf_messages` | CASCADE | `2026_09_14_299999:54-57` |
| 3 | `tf_channel_participants` | `account_id, channel_id` | `tf_channels` | RESTRICT | `2026_09_14_299999:61-64` |
| 4-6 | `tf_documents_attributes`, `_thumbs`, `_video_thumbs` | `account_id, id` | `tf_documents` | CASCADE | `2026_09_14_200020:53,71,91` |
| 7-8 | `tf_photos_sizes`, `_video_sizes` | `account_id, id` | `tf_photos` | CASCADE | `2026_09_14_200021:39,59` |
| 9 | `tf_web_pages_*` child | `account_id, id` | `tf_web_pages` | — | `2026_09_14_200022:30` |
| 10-14 | 5 sticker-set children | `account_id, id` | `tf_sticker_sets` | — | `2026_09_14_200023:41,59,72,85,98` |
| 15 | `tf_updates_*` child | `account_id, seq, position` | `tf_updates` | CASCADE | `2026_09_14_200030:96-99` |
| 16-18 | 3 routing children | `account_id, seq, position` | `tf_update_routing` | CASCADE | `2026_09_14_200030:257,269,283` |
| 19-21 | participant companion FKs | `account_id` | `telegram_accounts` | CASCADE | `2026_09_14_200031:84,157,197` |
| 22 | participant link child | `account_id, channel_id, user_id` | `tf_channel_participants` | CASCADE | `2026_09_14_200031:240-244` |
| 23 | channel-update child | `account_id, channel_id, position` | `tf_channel_updates` | CASCADE | `2026_09_14_200031:249-253` |
| 24 | difference child | `account_id, position` | `tf_update_differences` | CASCADE | `2026_09_14_200031:258-262` |
| 25-30 | 6 `account_id` FKs | `account_id` | `telegram_accounts` | CASCADE | `2026_09_14_200030:46,248,301` + misc |
| 31-35 | 5 misc children | `account_id, id` | misc parents | — | `2026_09_14_200050:45,83,98,110,122` |
| 36-37 | 2 todo-list children | `account_id, todo_list_id` | `tf_todo_lists` | — | `2026_09_14_200050:175,189` |
| 38-39 | 3 more misc children | `account_id, id` | misc parents | — | `2026_09_14_200050:242,254,284` |
| 40-41 | 2 store children | `account_id, bucket` | store bucket | — | `2026_09_20_000100:46,66` |

### 1a. The decisive fact: the graph is vertical, not horizontal

**Every one of the 40 FKs joins a child to its own parent.** Not one joins two different
*entities*. The declared graph is a forest of "companion hangs off its subject" edges.

`2026_09_14_299999_create_tf_foreign_keys.php:25-38` states this as deliberate, and gives
three reasons. The first two are sound. Verbatim:

> - the polymorphic peer pair peer_type/peer_id on tf_messages and tf_dialogs → tf_users /
>   tf_chats / tf_channels. One composite FK cannot branch on peer_type, and the peer tables
>   disagree in key column count (2 vs the triad's 3). Peer correctness is enforced in app
>   code instead (MirrorFactWriter fkClues + PeerShapeTool)
> - tf_messages_media → tf_documents / tf_photos. The curated media table has NO
>   media_document_id / media_photo_id columns (nested Photo/Document objects were deferred by
>   2026_09_14_200012); their document/photo ids also ref ledger ids, not mirror ids.

**Verified independently:** `tf_messages_media` declares 55 columns and not one of them is a
document or photo reference (`2026_09_14_200012:25-88`). There is no `document_id`, no
`photo_id`, no `media_document_id`. The second reason is factually correct.

### 1b. Inbound FK count, per key entity

| key entity | FKs pointing **at** it | source |
|---|---|---|
| `tf_messages` | **2** (media, entities) | `299999:47,54` |
| `tf_channels` | **1** (participants) | `299999:61` |
| `tf_updates` | **1** (its own child) | `200030:96` |
| `tf_users` | **0** | — |
| `tf_chats` | **0** | — |
| `tf_dialogs` | **0** | — |

**Three of the six key entities have no foreign key pointing at them anywhere in the
repository, and two more have exactly one, which is only their own companion child.**

### 1c. Declared FKs on columns that do not exist

**None found.** All 40 were cross-checked: each child column is declared in the same
`Schema::create` block as the FK (or, for the five `299999` constraints, added by
`Schema::table` to a table created earlier in the same curated dial), and each parent
`(table, columns)` pair matches a real primary key. Specifically re-verified:

- `299999:61` — `tf_channel_participants.channel_id` exists (`200031:74`); parent
  `(account_id, id)` is the `tf_channels` PK (`200003:94`). The child column pair
  `(account_id, channel_id)` is a *prefix* of the child PK `(account_id, channel_id, user_id)`
  (`200031:83`), which is a legal FK source. **Valid.**
- `299999:47,54` — `tf_messages_media.id` and `tf_messages_entities.id` both exist; parent
  `(account_id, id)` is the `tf_messages` PK (`200010:112`). **Valid.**

No FK points at a table that no migration creates. **No such defect found.**

### 1d. Key tables with no FK at all

Beyond the three zero-inbound entities in §1b, these tables are created and populated but
**never appear as an FK target and never declare an outbound entity FK**:

- `tf_users` (33 columns, `200001:63`) — 19 companion tables, 0 FKs. Not one companion is
  FK-linked to `tf_users`, even though all 19 share its PK.
- `tf_chats` (13 columns, `200002:48`) — 4 companions, 0 FKs.
- `tf_dialogs` (18 columns, `200004:51`) — 6 companions, 0 FKs.
- `tf_messages` (22 columns, `200010:88`) — 36 companions, only 2 FK-linked (media, entities).
  The other **34** are unconstrained.

**The `tf_messages` companion family is the largest block of unconstrained tables in the
schema: 34 tables sharing one PK and joined by nothing.**

### 1e. Why the message companions have no FK where documents do

This is the most useful structural signal in the migration set, and it is a consistency
defect rather than a design choice. Compare:

- `tf_documents` declares 3 child FKs **inline**, in the same file, immediately after each
  child's `primary()` (`200020:52-54,70-72,90-91`).
- `tf_photos` declares 2 the same way (`200021:38-40,58-60`).
- `tf_messages` declares **zero** inline; its header explicitly defers all FK work
  (`200010:31-32`: "FK constraints are NOT declared here"), and the follow-up
  `299999` migration then wires only 2 of 36.

So the documents/photos pattern — inline child FK, same file — is the house style, and the
messages domain silently opts out. There is no comment in `299999` explaining why
`tf_messages_from_id`, `tf_messages_reply_to`, `tf_messages_fwd_from` and 31 others were
skipped while `tf_messages_media` and `tf_messages_entities` were not.

---

## 2. Per key entity: what exists vs what the wire carries

Counts are of `$table-><type>('col'` declarations inside each `Schema::create` block,
excluding `primary`/`index`/`unique`/`foreign`.

### message

| aspect | value | cite |
|---|---|---|
| parent tables | `tf_messages` (22 cols), `tf_messages_service` (13 cols) | `200010:88`, `200010:418` |
| companions | **34** in `200010`, + `tf_messages_entities` (`200011`), + `tf_messages_media` & `tf_messages_service_action` (`200012`) = **37** | as cited |
| models | `TfMessage.php` exists; `TfMessageService.php` exists | — |
| declared relations | **13**, all `hasOne`/`hasMany` to its own companions | `TfMessage.php:58-121` |
| relations to *other* entities | **0** | — |

The 13 relations are `from`, `savedPeer`, `fwdFrom`, `replyTo`, `media`, `replyMarkup`,
`entities`, `views`, `forwards`, `replies`, `reactions`, `factCheck`, `suggestedPost`
(`TfMessage.php:58,63,68,73,78,83,88,93,98,103,108,113,118`). Every one is
`$this->hasOne(X::class, 'id', 'id')` or `hasMany` with the same two bare columns
(`TfMessage.php:60,65,70,75,80,85,90,95,100,105,110,115,120`).

The class docblock states the cross-domain relations are owed and not yet written
(`TfMessage.php:22-25`): "Cross-domain peer relations (peer → TfUser / TfChat / TfChannel
keyed by peer_type 1/2/3) resolve against the identity worktree models and belong to the
Task-8 FK wiring — they are added there once tf_users / tf_chats / tf_channels land".
`299999` is that Task-8 file. It did not add them. The debt is still open.

### user

| aspect | value | cite |
|---|---|---|
| parent | `tf_users`, **33 columns** | `200001:63` |
| companions | **19** | `200001:104-236` |
| model | `TfUser.php` | — |
| declared relations | **19 `hasOne`/`hasMany`, all to its own companions** | `TfUser.php:110-202` |
| relations to messages/channels/dialogs | **0** | — |

### chat

| aspect | value | cite |
|---|---|---|
| parent | `tf_chats`, **13 columns** | `200002:48` |
| companions | **4** (`_photo`, `_migrated_to`, `_admin_rights`, `_default_banned_rights`) | `200002:70,84,97,122` |
| model | `TfChat.php` | — |
| declared relations | **4**, all to own companions | `TfChat.php:68-85` |
| relations to messages/dialogs/participants | **0** | — |

### channel

| aspect | value | cite |
|---|---|---|
| parent | `tf_channels`, **34 columns** | `200003:58` |
| companions | **18** | `200003:101-328` |
| model | `TfChannel.php` | — |
| declared relations | **18**, all to own companions | `TfChannel.php:115-202` |
| relations to messages/dialogs/participants | **0** (participants has an FK to channels, but `TfChannel` declares no `hasMany` back to `TfChannelParticipant`) | — |

### dialog

| aspect | value | cite |
|---|---|---|
| parent | `tf_dialogs`, **18 columns** | `200004:51` |
| key | `(account_id, peer_type, peer_id)` | `200004:71` |
| companions | **6** (`_notify_settings`, `_pts`, `_draft`, `_folder_id`, `_ttl_period`, `_folder`) | `200004:79-...` |
| model | `TfDialog.php` | — |
| declared relations | **6, and none of them is an Eloquent relation** — all return a raw `Builder` | `TfDialog.php:101,106,111,116,121,126` |

`TfDialog` deliberately avoids Eloquent relations because of the composite PK
(`TfDialog.php:27`: "Because the PK is composite we use the TfUpdateDifference
Builder-accessor pattern instead of Eloquent hasOne"). `TfUpdate` does the same, 17
`Builder` accessors (`TfUpdate.php:40-121`).

### update-star

| aspect | value | cite |
|---|---|---|
| `tf_updates` + companions | **23** tables | `200030` (23 `Schema::create`) |
| model | `TfUpdate.php`, 17 `Builder` accessors | `TfUpdate.php:40-121` |
| `tf_updates_message.message_id` | exists, **no FK** — deliberate | `200030:139`, `299999:36-38` |
| `tf_stars_transactions_msg_id.msg_id` | exists, **no FK** — deliberate | `200040:222`, `299999:36-38` |

`299999:36-38` gives the reason and it is a good one: "'msg_id' refs — deliberate soft
routing links (the row may be mirror-id or not yet mirrored); never FK'd." A soft link is
still joinable in SQL. It is recorded here so it is not mistaken for an oversight.

---

## 3. THE JOIN-FAILURE TABLE

The core deliverable. For each relation the wire implies, exactly what the schema permits.

| # | expected relation | verdict | blocking fact |
|---|---|---|---|
| 1 | **message → author** | **CANNOT JOIN** | Author lives in `tf_messages_from_id.from_id_type` / `.from_id_id` (`200010:129-130`). A 2-column polymorphic pair across 3 target tables. `TfMessage::from()` returns the *pair holder* (`TfMessage.php:58`), never `TfUser`. No model method and no FK resolves type 1 → `tf_users.id`. |
| 2 | **message → peer** | **CANNOT JOIN** | `tf_messages.peer_type` + `.peer_id` (`200010:91-92`). Same polymorphic-pair problem. Deliberately un-FK'd (`299999:26-31`). No relation on `TfMessage` at all. |
| 3 | **message → reply target** | **CANNOT JOIN** | `tf_messages_reply_to.reply_to_msg_id` (`200010:213`) is a bare message id with **no `reply_to_peer_id` required and no FK**. Worse, `tf_messages_reply_to` carries **two confusingly similar peer pairs**: `reply_to_peer_id_type`/`reply_to_peer_id_id` (`200010:214-215`) *and* `peer_type`/`peer_id` (`200010:221-222`). Nothing declares which is the reply peer. `TfMessage::replyTo()` returns the pair holder only (`TfMessage.php:73`). |
| 4 | **message → forward source** | **CANNOT JOIN** | `tf_messages_fwd_from` carries `from_id_type`/`from_id_id` (`200010:162-163`), `saved_from_peer_type`/`_id` (`200010:168-169`), `saved_from_id_type`/`_id` (`200010:171-172`) — three polymorphic pairs, 18 columns total, zero FKs, zero model relations. `TfMessage::fwdFrom()` returns the holder only (`TfMessage.php:68`). |
| 5 | **message → media** | **CAN JOIN** | `tf_messages_media.id` = parent id, FK enforced CASCADE (`299999:47-50`), relation declared (`TfMessage.php:78`). The one fully-wired vertical edge in the message family. |
| 6 | **message → document/photo** | **NO SUCH COLUMN** | `tf_messages_media` has 55 columns and no document or photo reference whatsoever (`200012:25-88`). Confirmed against `299999:32-35`. A photo message is **permanently unresolvable to its photo** — there is nothing to join on and nothing to backfill. |
| 7 | **media → document / photo** | **NO SUCH TABLE EDGE** | `tf_documents` (PK `account_id,id`, `200020:25`) and `tf_photos` (`200021:24`) both exist and are fully FK-wired *internally* (6 child FKs), but nothing connects either to the message domain. Their ids are also wire-id space, not necessarily mirror-id space (`299999:34-35`). |
| 8 | **dialog → peer** | **CANNOT JOIN** | `tf_dialogs` PK is `(account_id, peer_type, peer_id)` (`200004:71`) — the *only* table whose PK is the peer triple. This makes it the natural anchor for peer resolution, and it is exactly the table with 0 FKs and 0 relations. `TfDialog` exposes only its own 6 companions (`TfDialog.php:101-126`). |
| 9 | **chat → participants** | **NO SUCH TABLE** | `tf_channel_participants` exists and is FK'd to `tf_channels` (`299999:61-64`) — but there is **no participant table for chats at all**. `200002` creates 4 companions, none of them participants. `chatFull.participants` (per the map, §3) has nowhere to land for a basic group. |
| 10 | **chat → full record** | **CANNOT JOIN** | No `tf_chats_full` / `chatFull` table exists. `200002` is 5 `Schema::create` total (`tf_chats` + 4 companions). `chatFull` carries 20 fields and is a fetchable return type per the map. Nothing stores it. |
| 11 | **channel → full record** | **CANNOT JOIN** | No `tf_channels_full` / `channelFull` table. `200003` is 19 `Schema::create`, all `tf_channels` + 18 `tf_channels_*` companions of the *bare* channel. `channelFull` carries 67 fields per the map and is stored nowhere. |
| 12 | **channel → participants** | **CAN JOIN (SQL), not modelled** | `tf_channel_participants` exists (9 columns, `200031:72-83`) with FK to `tf_channels` (`299999:61-64`) and 7 companion tables (`200031:89-140`). But `TfChannel` declares 18 relations and **none is `participants()`** (`TfChannel.php:115-202`). The join works; the model cannot express it. |
| 13 | **channel participant → user** | **CANNOT JOIN** | `tf_channel_participants.user_id` (`200031:75`) is part of the PK (`:83`) but has **no FK to `tf_users`**, which itself has 0 inbound FKs. A participant row can name a user that was never mirrored, and nothing prevents it. |
| 14 | **update → message** | **CANNOT JOIN (soft)** | `tf_updates_message.message_id` (`200030:139`) and `tf_updates_messages.message_id` (`200030:147`) exist with no FK, deliberately (`299999:36-38`). Joinable in SQL **if** the id is a mirror id; silently empty if not. No model relation — `TfUpdate::message()` returns a `Builder` on the *child table*, not the message (`TfUpdate.php:60`). |
| 15 | **user → messages** | **CANNOT JOIN** | No path. `tf_messages_from_id` has no index or FK to `tf_users`; `TfUser` declares 19 relations, none to messages (`TfUser.php:110-202`). "All messages by this user" is an unindexed full scan across a polymorphic pair. |
| 16 | **message → entity (formatting)** | **CAN JOIN** | `tf_messages_entities` FK CASCADE (`299999:54-57`), relation `hasMany` (`TfMessage.php:88`). Fully wired. |
| 17 | **message → reply markup rows/buttons** | **CANNOT JOIN (structural)** | `tf_messages_reply_markup_rows` PK `(account_id, id, position)` (`200010:391`), `tf_messages_reply_markup_rows_buttons` PK `(account_id, id, position)` (`:412`) with `parent_position` as a plain column (`:398`) — **outside the PK and with no FK**. Two buttons in the same row sharing a `position` but differing in `parent_position` collide on the PK. Neither nested table has a model. |

**Tally: 2 CAN JOIN (rows 5, 16), 1 CAN JOIN but unmodelled (12), 3 CANNOT JOIN (1, 2, 8),
1 NO SUCH COLUMN (6), 1 NO SUCH TABLE EDGE (7), 1 NO SUCH TABLE (9), 8 CANNOT JOIN or
structurally blocked (3, 4, 10, 11, 13, 14, 15, 17).**

Of the eight relations the operator named — message→author, →peer, →reply target,
→forward source, →media, dialog→peer, chat→participants, channel→full record — **exactly
one (message→media) can be joined.**

---

## 4. The dependency DAG

Derived from the wire references the map enumerates (map §3), mapped onto the tables that
actually exist. Read top-down: **a table must be populated before any table below it, or the
join below it is permanently unresolvable.**

```
tier 0  telegram_accounts            200009_000201:25
          │  (6 of 40 FKs point here; the only enforced edge in the schema)
          ▼
tier 1  tf_users        33 cols      200001:63      ── 0 inbound FK
          tf_chats        13 cols     200002:48      ── 0 inbound FK
          tf_channels     34 cols     200003:58      ── 1 inbound FK
          tf_dialogs      18 cols     200004:51      ── 0 inbound FK
          │
          │  RESOLUTION GATE: every peer_*_type/_id pair in tier 2 resolves
          │  against exactly one tier-1 table, chosen by the type int
          │  (1=user 2=chat 3=channel — 200010:23-24).
          ▼
tier 2  tf_messages                  200010:88      ── 2 inbound FK
          ├─ tf_messages_from_id      :126  (from_id_type/_id)
          ├─ tf_messages_saved_peer_id:148  (saved_peer_id_type/_id)
          ├─ tf_messages_fwd_from     :157  (3 peer pairs)
          ├─ tf_messages_guestchat_via_from :193
          ├─ tf_messages_reply_to     :205  (reply_to_msg_id + 2 peer pairs)
          └─ tf_messages_service      :418  (13 cols, disjoint id space)
          │
          ├─ tf_messages_entities     200011  ── FK CASCADE  ✅ order-free
          └─ tf_messages_media        200012  ── FK CASCADE  ✅ order-free
          │
          ▼  ⛔ HARD STOP — no edge exists, and none can be added without
             a new column (see §3 rows 6-7)
tier 3  tf_documents / tf_photos     200020:14 / 200021:14
          reachable from tier 2: NO. Orphaned by schema design (299999:32-35).

tier 2' tf_channel_participants      200031:72  ── FK to tf_channels (299999:61)
          └─ requires tf_channels populated; user_id requires tf_users
             (tier 1) but is NOT FK-enforced (see §3 row 13)

tier 2'' tf_updates                  200030:40  ── FK to telegram_accounts
          └─ tf_updates_message.message_id → tier 2 tf_messages
             SOFT, unordered, may not be a mirror id (299999:36-38)
```

### The order that matters, stated plainly

1. **`telegram_accounts`** first — it is the only table the FK layer actually enforces, and
   6 of the 40 constraints hang off it.
2. **Tier 1 identity (`tf_users`, `tf_chats`, `tf_channels`)** before **any message row**.
   Every peer reference in tier 2 is a `(type, id)` pair pointing into tier 1. Writing a
   message before its peers exist produces a row that is *storable but unjoinable forever* —
   nothing backfills a peer from a message.
3. **`tf_messages_media` / `tf_messages_entities` are order-free** relative to
   `tf_messages` because their FK is `CASCADE` on the parent key and their `id` **is** the
   parent id (`299999:14-19`). The documents/photos pattern, by contrast, forces
   parent-first ordering for its 6 children (`200020:52-54`, `200021:38-40`).
4. **`tf_channel_participants` requires `tf_channels`** — enforced, `RESTRICT`
   (`299999:61-64`).
5. **`tf_messages_media` → `tf_documents`/`tf_photos` cannot be ordered at all.** There is
   no column to order on. This is not a sequencing bug; it is a missing edge in the schema.

**The asymmetry that defines the ingest contract:** for 6 document/photo children, parent
first is *enforced*. For 34 message companions, parent first is *convention only* — nothing
stops a companion row landing before its `tf_messages` parent, and the 2 FK'd ones would
reject it while the other 32 would not. An ingestor that writes companions optimistically
will produce orphans in 32 tables with no error.

---

## 5. Model gap list

### 5a. Declared companion tables with no Eloquent model

**CORRECTION (2026-10-01). The figure "19 message-domain tables have no model" is
stale and is withdrawn.** A lane dispatched to build the first missing model found
`TfMessageFromId` already existed, committed in `1e113af5` ("feat(messages):
hand-authored NF5 mirror surface for the messages domain (Task 3)").

A SECOND correction, to my own first correction: I initially wrote that the "142 call
sites" figure was also stale. That was a misreading on my part. **142 is the
`belongsTo(` count across `src/Teleframe/Mirror/Models/` — verified: exactly 142
occurrences — not a count of `TfMessageFromId` references.** Only 3 files reference
`TfMessageFromId`, and one of those is its own definition. §5b's "284 relations /
142 `belongsTo`" figures are therefore CORRECT and stand. The error was in
conflating two different numbers while trying to correct a third.

**The real number, measured across the whole domain rather than the messages slice:**

| measure | count | how |
|---|---|---|
| `tf_*` tables declared in `src/Laravel/Migrations/` | **264** | `Schema::create('tf_…')` across all migrations |
| Mirror models on disk | **218** | files in `src/Teleframe/Mirror/Models/` |
| of those, tables a model actually binds via `$table` | **217** | resolved per model file |
| **declared tables with NO model** | **47** | set difference |

So the gap is **47 tables, not 19** — and the earlier figure was not merely stale but
scoped to a slice, which made it read as a total. That is the exact error this
project's own goals file warns about: a partial measurement presented as a complete
one. The first 10 of the 47 are all `tf_dialog_filters*` and `tf_accounts`.

Two consequences the models stage must respect:

1. **Do not rebuild what exists.** 217 tables are already bound. A build list
   generated from the old figure would have re-created `TfMessageFromId` and hit a
   fatal redeclare.
2. **The build list must be regenerated from the real 47**, not patched from the old
   19. `docs/schema/model-build-order.md` is the place that list is made.

Also recorded: `tf_messages_from_id` has 4 columns and **no FK** to `tf_messages`,
while `tf_messages_media` and `tf_messages_entities` do get one
(`2026_09_14_299999_create_tf_foreign_keys.php:47,54`). The messages domain declares
zero inline child FKs of its own.

### 5b. Relations that must be added (the real gap)

The models are not missing companions; they are missing **entities**. Of 284 relation
declarations across `src/Teleframe/Mirror/Models/` and 142 `belongsTo` calls, **not one
crosses an entity boundary.** Every `belongsTo` points from a companion back to its own
subject (e.g. `TfMessageFromId::message()` → `belongsTo(TfMessage::class, 'id', 'id')`,
`TfMessageFromId.php:30`).

Recommended, in order:

1. **A polymorphic peer resolver on `TfMessage`** — `peer()`, `from()`, `fwdFrom()`,
   `replyTo()` currently return pair *holders*. They need a single resolver that switches
   on the `_type` int (1→`TfUser`, 2→`TfChat`, 3→`TfChannel`) and returns a
   `BelongsTo` on the right table. This single addition unblocks §3 rows 1, 2, 3, 4 and 8
   at once. Highest leverage item in this document.
2. **`replyToMessage()`** — `belongsTo` `tf_messages_reply_to.reply_to_msg_id` →
   `tf_messages.id`. Needs `peer_type` resolved first to scope the peer.
3. **`TfChannel::participants()`** — `hasMany` `TfChannelParticipant`. The join already
   exists (`299999:61`); only the method is missing (§3 row 12).
4. **`TfUser::messages()` / `TfChannel::messages()`** — reverse of 1. Requires an index on
   `tf_messages_from_id(account_id, from_id_type, from_id_id)`, which does not exist.
5. **`TfDialog::peer()`** — the resolver from 1, applied to `tf_dialogs`, whose PK is
   already the peer triple.

### 5c. Recommended schema changes (NOT written — recommendation only; item 2 is DONE, see §8)

Per the brief, no migration or model was written. In priority order:

1. **Add `media_document_id` / `media_photo_id` (+ `_access_hash`) to `tf_messages_media`**
   and FK them to `tf_documents` / `tf_photos`. This is the only fix for §3 row 6 — the
   "make the full shape" requirement. Without it, photo and document messages are
   permanently unresolvable and the gap widens with every message ingested.
2. **DONE (measured 2026-10-02) — read "Add `parent_position` to the PK and FK to the row".**
   The shipped PK is now `(account_id, id, peer_type, peer_id, position, parent_position)` with a `CASCADE` FK to `tf_messages_reply_markup_rows`. Fixes §3 row 17.
3. **Inline the 34 missing `tf_messages` child FKs**, matching the `tf_documents` /
   `tf_photos` house style (`200020:52-54`, `200021:38-40`). Cheap, mechanical, and makes
   the 32-table orphan window in §4 impossible.
4. **Create `tf_chats_full` and `tf_channels_full`.** `chatFull` (20 fields) and
   `channelFull` (67 fields) are fetchable return types per the map and are stored nowhere.
5. **Add `tf_chats_participants`** (FK to `tf_chats`) to close §3 row 9.
6. **Add an FK `tf_channel_participants.user_id` → `tf_users`** to close §3 row 13.
7. **Index `tf_messages_from_id(account_id, from_id_type, from_id_id)`** before item 4 of
   §5b, or the reverse message query is a full scan.

---

## 6. Defects found, not fixed

Reported, not touched, per the brief.

1. **`tf_messages_reply_markup_rows_buttons` — `parent_position` outside the PK.**
   `src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:398` declares
   `parent_position`; the PK at `:412` is `(account_id, id, position)`. Two buttons in the
   same row with equal `position` but different `parent_position` **collide**. No FK
   connects buttons to their row.

2. **Cross-account relation join omits `account_id`.** Every relation in the model layer is
   declared as `hasOne(X::class, 'id', 'id')` (`TfMessage.php:60` and 12 siblings;
   `TfUser.php:112` and 18 siblings) or `belongsTo(TfMessage::class, 'id', 'id')`
   (`TfMessageFromId.php:30`). The join key is `id` **alone**, while every one of these
   tables has a composite PK of `(account_id, id)`. This is only safe because
   `AccountScope` adds `WHERE account_id = ?` — and
   `src/Schema/Eloquent/AccountScope.php:18-25` makes that scope a **no-op when no
   `AccountContext` is active**: "When no context is active the scope is a no-op (returns
   all rows)". So an eager load or relation traversal outside an account context joins
   message id 5 of account A to companion id 5 of account B. The composite-key design is
   documented as the reason for the `Builder`-accessor pattern in `TfDialog.php:27` and
   `TfUpdate` — the `hasOne` relations in `TfMessage`/`TfUser`/`TfChat`/`TfChannel` did not
   get the same treatment.

3. **`tf_messages_reply_to` has two near-identical peer pairs.**
   `...200010:214-215` declares `reply_to_peer_id_type` / `reply_to_peer_id_id`;
   `...200010:221-222` declares `peer_type` / `peer_id`. Both are `tinyInteger` +
   `bigInteger` defaults of 0. Nothing declares which is the reply peer and which is the
   header's own peer, and there is no FK on either. A query author picking the wrong pair
   gets `0`/`0` and no error.

4. **The 34 unconstrained `tf_messages` companions are undocumented as such.**
   `299999` explains why the *peer* and *media→document* edges are absent (`:25-38`) but
   says nothing about why 34 of 36 own-companion edges are missing while 2 exist. Read
   alone, `299999` presents as a near-complete FK layer; it is 3 edges.

---

## 7. What this document does NOT establish

- **Live schema state.** Nothing was connected to. A real database may have drifted from
  these migrations in either direction. Every claim here is about **the migrations**.
- **Which side of the polymorphic pair is authoritative** in `tf_messages_reply_to`
  (defect 3). The wire union is `messageReplyHeader` / `messageReplyStoryHeader`; resolving
  it needs the `.tl` source, which was not read for this document.
- **`MirrorFactWriter fkClues` and `PeerShapeTool`** — cited by `299999:29-30` as the
  app-code enforcement for peer correctness. **Not read.** If they do enforce the peer pair
  at write time, the app-code substitute for the missing FKs is stronger than §1 suggests;
  if they do not, §1 stands as written. This is the single most load-bearing unverified
  claim in the document and it should be checked before acting on §5c.
- **Whether `chatFull` / `channelFull` are intentionally out of scope** for the mirror
  surface, or merely not yet built. The map lists both as fetchable; no migration or spec
  comment was found either way.
- **Row-level data volume.** No row counts, so the practical cost of the 32-table orphan
  window in §4 is unquantified.
- **`tf_store`, `tf_misc`, `tf_bots`, stars/business** are enumerated for the FK count only.
  Their internal shape was not analysed.

---

## 8. CORRECTION (2026-10-02, Task 5.1) — this document's FK numbers were produced by grepping

The dated correction blocks below are APPENDED rather than edited in place, so every `file:line`
in the plan and in `model-build-order.md` that points into this file stays valid — as do the only two edits made IN PLACE since (§5c and its item 2), which were deliberately line-count-neutral for that same reason.

**THE METHOD WAS WRONG, not just the numbers.** §1 opens with *"**40 foreign keys exist across
all `tf_*` tables.** Enumerated from every `->foreign(` in `src/Laravel/Migrations/`"*. That search
cannot see this schema. Foreign keys reach it through shared `Blueprint` helpers —
`linkChild()`, `participantLink()`, `channelUpdateLink()`, `differenceLink()`, `factKey()` — which
are private static methods on the migration classes, so `grep "->foreign("` sees only literal
inline declarations. §7 above already conceded it never connected a database; that concession was
not only about live drift. **The static claims were wrong too.**

Re-measured by EXECUTING all 34 shipped migrations and reflecting with `PRAGMA foreign_key_list`,
grouped by constraint `id` (`bin/generate-relation-graph.php`; graph in
[`mirror-relation-graph.md`](mirror-relation-graph.md)):

| §1 claim | measured |
|---|---|
| **40** foreign keys across `tf_*` tables | **54** constraints declared by `tf_*` tables; **55** by all 270 tables |
| enumeration was `->foreign(` text search | 54 constraints, 53 of them `CASCADE`, 1 `RESTRICT`, 1 `SET NULL` |
| §1b `tf_updates`: **1** inbound FK | **17** |
| §1a *"every one of the 40 FKs joins a child to its own parent; not one joins two different entities"* | by child-name prefix, **45 of 55** are vertical and all 45 are `CASCADE`; the other **10** point at `telegram_accounts` (6), `telegram_apps` (1), `tf_channels` (1), `tf_store_buckets` (2) |
| §5a `tf_*` tables declared: **264** | **266** |
| §3 row 17 / §6 defect 1: buttons have **no FK** to their row; `parent_position` is "outside the PK and with no FK" | **`tf_messages_reply_markup_rows_buttons → tf_messages_reply_markup_rows` EXISTS**, `CASCADE`, on `(account_id, peer_type, peer_id, id, parent_position)`. **That defect is FIXED.** |
| §1b inbound counts for `tf_messages` (2), `tf_channels` (1), `tf_users` (0), `tf_chats` (0), `tf_dialogs` (0) | **all five confirmed unchanged** |

The four inbound counts that were right are right because §1b was counting from the enumerated
list, and those five genuinely had no other source. The number that was wrong — `tf_updates` at 1
against a measured 17 — is the tell: the enumeration had found the constraint declared *in the same
file* and missed the sixteen the helpers emitted.

### What survives, and what does not

**DOES NOT SURVIVE:** every count in §1 and §1a, the `tf_updates` row of §1b, the 264 of §5a, and
§3 row 17 / §6 defect 1 (fixed). Treat §1 as superseded in full. **§1d's COMPANION COUNTS are also
superseded** — measured by derived parent (`mirror-relation-graph.md`): `tf_users` **19** (confirmed),
`tf_chats` **5** (was 4), `tf_dialogs` **5** (was 6), `tf_messages` **31** children (was 36, which
counted migration-file companions rather than derived parents), `tf_channels` **19** (confirmed).

**SURVIVES UNCHANGED:** §3's verdicts are still the useful part of this document and were not
re-derived here — the **2 CAN JOIN**, the **1 CAN JOIN but unmodelled**, and the structural blocks.
They are *consistent* with the measured graph: the relations this document calls unjoinable are
unjoinable for reasons of KEY SHAPE (a polymorphic pair, a missing column), not for want of a
constraint, so a corrected FK count does not unblock any of them. The one relation the corrected
measurement DOES unblock is the reply-markup buttons row (§3 row 17, §6 defect 1).

**STILL OPEN, and not closed by the generator:** §5b's cross-entity relations, §5c's remaining six
recommended migration changes (item 2 — the buttons `parent_position` PK + FK — is DONE and struck
above), and §7's never-verified `MirrorFactWriter fkClues` / `PeerShapeTool`
claim — which now matters more, because §7a of the NF5 spec's keying strategy is corrected *on the
strength of* it. If the write path does not enforce the peer pair, the "no orphan facts" property
was never true of peers. **67 tables carry a polymorphic `{base}_type` + `{base}_id` pair**; see
the table graph for which.

**THE ~80 UNANALYSED TABLES ARE STILL UNANALYSED.** The generator makes the set visible — every
`root` and every `soft` row in `mirror-relation-graph.md` is a relation no constraint backs — but a
measured table list is not a read of each table's semantics. This concession stands.
