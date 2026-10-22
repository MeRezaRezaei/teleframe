# Model & relation gap — what can actually be JOINed, and what silently cannot

Companion to `docs/schema/mtproto-data-shape-map.md` (committed `ea613a63`), which maps
*method → shape → reference*. That document answers "which entities does the wire carry, and
which fetcher hydrates them". **This** document answers the follow-up that decides whether
any of it is queryable: *given rows are present, does the schema actually permit relating
them, and in what order must they be written so the relation is not permanently lost?*

**Status — rewritten after the horizontal-relations slice landed (commit `9c628c95`).**

> The generated model layer now carries the **horizontal** relations: entity-to-entity edges
> that no declared foreign key can express. Every model-relation number in this document was
> **re-derived by executing `php bin/regenerate` into a scratch directory** and counting the
> emitted PHP. Nothing here is transcribed. The measurement transcript is in §0.4.
>
> **No migration changed.** `git diff --name-only 9c628c95~1 HEAD -- src/Laravel/Migrations/`
> returns **0 files**, so every constraint-layer claim in §1 and §8 is unaffected by the slice.

**Method (unchanged, and still a limit — see §7).** Migrations and Eloquent models only. No
database was connected, no `.env` or credential file was read, no migration was run, no
Telegram call was made. Migrations are treated as authoritative over any live schema, which
may have drifted. The constraint numbers quoted here are read from
[`mirror-relation-graph.md`](mirror-relation-graph.md), which is **script-generated** by
executing the migration set against an in-memory SQLite connection — not by grepping
`->foreign(`.

**Correction honoured (unchanged).** The schema does **not** flatten a `Message` into one wide
row. Scalars live on `tf_messages` (22 columns, `2026_09_14_200010_create_tf_messages_tables.php:174`)
and every optional or non-scalar field lives on its own 1:1 companion table keyed
`(account_id, id)` — 34 companions in that file, plus `tf_messages_entities`
(`...200011:22`) and `tf_messages_media` / `tf_messages_service_action` (`...200012:25,89`).
Reference fields are **present and modelled**, in `{field}_type` / `{field}_id` inline peer
pairs (`...200010:126-131`) and in flattened union children (`...200010:157-176`,
`...200010:205-224`). The `tf_messages` PK is `(account_id, id)` at `...200010:112`.

---

## 0. THE TWO LAYERS — read this before any relation count

This is the correction the previous revision of this document most needed. It quoted a
hand-layer number against a generated-layer total as if they were one figure.

| | **GENERATED layer** | **HAND layer** |
|---|---|---|
| path | `src/Schema/Generated/Models/` | `src/Teleframe/Mirror/Models/` |
| ownership | `@generated`; overwritten by `php bin/regenerate` | hand-maintained; survives regeneration |
| model files | **257** | **250** (245 bind a `$table`; 5 are traits/base helpers) |
| relation methods | **747** | **346** |
| — `BelongsTo` | 296 | 172 |
| — `HasMany` | 451 | 31 |
| — `HasOne` | — | 143 |
| models with zero relations | **10** | 56 |

**Every "relation methods" total in this document is labelled with its layer.** A number
quoted without a layer label is a defect in this document.

Class naming follows the layers: the generated `Tl*` classes are the eleven TL **domain
anchors** (`TlUser`, `TlChat`, `TlChannel`, `TlDialog`, `TlUpdate`, `TlMessage`,
`TlChannelParticipant`, `TlDocument`, `TlPhoto`, `TlStickerSet`, `TlWallpaper`); their
companion tables are generated as `Tf*` classes (`TfMessagesFromId`, `TfMessagesFwdFrom`, …).
The hand layer uses the `Tf*` prefix throughout. **`TfAccounts` exists only in the GENERATED
layer** — there is no `src/Teleframe/Mirror/Models/TfAccounts.php`.

### 0.1 The generated layer, before → after

| measure | before (`9c628c95~1`) | after | delta |
|---|---:|---:|---:|
| relation methods | **406** | **747** | **+341** |
| — `HasMany` | 203 | 451 | +248 |
| — `BelongsTo` | 203 | 296 | +93 |
| models with zero relations | **24** | **10** | −14 |

Per-model, generated layer only:

| model | before | after | delta |
|---|---:|---:|---:|
| `TlUser` | 19 | **109** | +90 |
| `TlChat` | **5** | **78** | +73 |
| `TlChannel` | 19 | **93** | +74 |
| `TlDialog` | 0 | **1** | +1 |
| `TlUpdate` | 14 | **17** | +3 |
| `TfAccounts` (tenant root) | 0 | **11** | +11 |

### 0.2 The hand layer, measured today

| model | relation methods | of which horizontal |
|---|---:|---:|
| `TfMessage` | 31 | 0 |
| `TfUser` | 21 | 2 |
| `TfChannel` | 20 | 2 |
| `TfChat` | 6 | 2 |
| `TfUpdate` | 3 | 0 |
| `TfDialog` | 1 | 1 |
| `TfAccounts` | **does not exist** | — |

**Correction to §5a below.** Its `belongsTo(` figure of **142** is stale. Measured across the
hand layer today: **179** `belongsTo(` call lines, and **346** relation methods. The `284
relations / 142 belongsTo` pair was a hand-layer measurement; it was never comparable with the
generated layer's 406, and this document used it as though it were.

**Correction to §2's per-entity tables below.** `TfUser 19`, `TfChat 4`, `TfChannel 18` were
the **pre-slice hand-layer** counts. The hand layer now carries the **2-method mandate subset**
per identity model (§9.3), which is why they read 21 / 6 / 20 today.

### 0.3 Where the old document's "4" for `TlChat` came from

`TlChat` generated **5** relations before the slice, not 4. The figure **4** is the **hand**
`TfChat`'s *companion* relation count — a different file, a different layer, a different count.
This single confusion is why the two layers are now separated above. Likewise `TfUser 19` and
`TfChannel 18` in §2 are hand-layer companion counts, and are not the same quantity as the
generated `TlUser` / `TlChannel` figures.

### 0.4 The measurement transcript

```console
$ timeout -k 5 280 php bin/regenerate --out=/tmp/regen-doc
regenerated: 3 files, 664 types, 1685 constructors, 799 methods, 0 tables, 0 deferred FKs (manifest hash 33dcdbbbfdc0)

$ timeout -k 5 60 diff -rq /tmp/regen-doc/src/Schema/Generated/Models src/Schema/Generated/Models
IDENTICAL: regen output == committed working tree

$ REL='public function [A-Za-z0-9_]+\(\): \??(HasMany|BelongsTo|HasOne|BelongsToMany|MorphMany|MorphTo)\b'

# AFTER (scratch dir); BEFORE (old commit, read-only via git show into /tmp/before-models)
$ grep -rEo "$REL" /tmp/regen-doc/src/Schema/Generated/Models | wc -l
747
$ grep -rEo "$REL" /tmp/before-models/src/Schema/Generated/Models | wc -l
406

$ # BEFORE breakdown, from the committed tree at 9c628c95~1
$ git ls-tree -r --name-only 9c628c95~1 src/Schema/Generated/Models \
    | while read -r f; do git show "9c628c95~1:$f"; done \
    | grep -Eo "$REL" | grep -Eo '(HasMany|BelongsTo|HasOne|BelongsToMany|MorphMany|MorphTo)$' | sort | uniq -c
    203 HasMany
    203 BelongsTo

$ # AFTER breakdown
$ grep -rEo "$REL" /tmp/regen-doc/src/Schema/Generated/Models \
    | grep -Eo '(HasMany|BelongsTo|HasOne|BelongsToMany|MorphMany|MorphTo)$' | sort | uniq -c
    451 HasMany
    296 BelongsTo

$ # models with zero relations: AFTER 10, BEFORE 24   (per-file loop; see §0.1)

$ # hand layer, whole tree
$ grep -rEo "$REL" src/Teleframe/Mirror/Models | wc -l
346
```

The BEFORE figure was taken **without checking out or mutating the working tree**: the old
files were materialised into `/tmp/before-models` with `git show`, then counted there.

**A previous plan asserted "406 relation methods … 203 HasMany, 203 BelongsTo".** That figure
**does** reproduce — but only for the **GENERATED** layer, and only at `9c628c95~1`. It is
reproduced above rather than restated. It was wrong to be set beside the hand layer's
`TfMessage 31 / TfUser 19 / TfChat 4` as though the two were comparable.

---

## 1. The FK graph, as the migrations declare it

**Numbers in §1 and §1a are superseded — see §8. The corrected figures are 55 declared
constraints across all tables, not 40.** §1b is corrected in-place in this revision (see
§1b) because the original wording was wrong about what it counted.

### 1a. The graph is vertical at the CONSTRAINT layer — and that did not change

**Every declared constraint joins a child to its own parent or to the tenant root. Not one
declares an edge between two different entities.** The graph remains a forest of "companion
hangs off its subject" edges, plus the `telegram_accounts` fan-out.

`2026_09_14_299999_create_tf_foreign_keys.php:25-38` states the peer-pair omission as
deliberate, and gives the reason. Verbatim:

> - the polymorphic peer pair peer_type/peer_id on tf_messages and tf_dialogs → tf_users /
>   tf_chats / tf_channels. One composite FK cannot branch on peer_type, and the peer tables
>   disagree in key column count (2 vs the triad's 3). Peer correctness is enforced in app
>   code instead (MirrorFactWriter fkClues + PeerShapeTool)

> **At the relation layer this claim is now FALSE, and the distinction is the whole point of
> §9.** The relations layer *does* join two different entities — 341 of them. Nothing was
> added to the schema to make that possible, and nothing needed to be. What changed is that
> the edge is declared **in the model layer**, where a discriminator can be read.

**Verified independently:** `tf_messages_media` declares 55 columns and not one of them is a
document or photo reference (`2026_09_14_200012:25-88`). The second reason in that migration
is factually correct.

### 1b. Inbound constraints, per key entity — CORRECTED

From [`mirror-relation-graph.md`](mirror-relation-graph.md) ("Inbound constraints on the five
key entities"), which counts constraints **grouped by constraint id** against the executed
DDL:

| entity | inbound constraints | of which cross an entity boundary |
|---|---:|---:|
| `tf_messages` | 30 | **0** |
| `tf_dialogs` | **0** | **0** |
| `tf_users` | 19 | **0** |
| `tf_chats` | 5 | **0** |
| `tf_channels` | 19 | **0** |

> **This revision corrects the previous wording.** The earlier text read "`tf_users` **0**,
> `tf_chats` **0**, `tf_dialogs` **0**" — three entities with no inbound FK anywhere — and §8
> below records that claim as "confirmed unchanged". That wording was wrong: `tf_users` has
> **19** inbound constraints and `tf_chats` has **5**, added by
> `2026_10_04_000820_add_tf_users_family_foreign_keys.php` ("Task 4.2, family 2: `tf_users`
> and its nineteen single-parent children") and its chat sibling. All 24 are **vertical** —
> each companion points at its own parent.
>
> **The claim that is TRUE, and is the one the whole slice rests on, is the cross-entity
> column: not one constraint anywhere points from one entity table to another.** And for
> `tf_dialogs` specifically, zero inbound constraints of *any* kind is literally true.

**Three of the five key entities have no constraint that reaches them from another entity, and
the remaining two have only their own companion children.**

### 1c. Declared FKs on columns that do not exist

**None found** (as re-measured by the graph generator against the executed DDL, which is the
authority for this claim). No FK points at a table no migration creates.

### 1d. Key tables with no entity-crossing FK

Beyond the cross-entity zero of §1b, these tables are populated but never named as the target
of an edge from a **different** entity:

- `tf_users` (33 columns, `200001:63`) — 19 companions, every one of them its own child.
- `tf_chats` (13 columns, `200002:48`) — 5 companions, its own children.
- `tf_dialogs` (18 columns, `200004:51`) — no inbound constraint of any kind.
- `tf_messages` (22 columns, `200010:88`) — 30 inbound constraints, all from its own children.

The `tf_messages` companion family remains the largest block of same-parent tables in the
schema.

---

## 2. Per key entity: what exists vs what the wire carries

Counts of `$table-><type>('col'` declarations inside each `Schema::create` block, excluding
`primary`/`index`/`unique`/`foreign`. **Relation rows are labelled by layer.**

### message

| aspect | value | cite |
|---|---|---|
| parent tables | `tf_messages` (22 cols), `tf_messages_service` (13 cols) | `200010:88`, `200010:418` |
| companions | **34** in `200010`, + `tf_messages_entities`, + `tf_messages_media` & `tf_messages_service_action` | as cited |
| hand model | `TfMessage.php` | — |
| HAND-layer relations | **31**, all to its own companions | `TfMessage.php` |
| GENERATED-layer relations | **20** on `TlMessage` + the peer-pair forwards on its `Tf*` companions | generated |
| **relations to other entities (hand)** | **0** | — |
| **relations to other entities (generated)** | **yes** — `TfMessagesFromId::fromId()/peer()`, `TfMessagesFwdFrom::fromId()/peer()/savedFromId()/savedFromPeer()`, `TfMessagesReplyTo::peer()/replyToPeerId()` | §9 |

The hand `TfMessage`'s 31 relations are companion-only and **remain so** — the hand layer was
not given the horizontal set for messages; the mandate subset (§9.3) covers the three identity
models and `TfDialog`.

The class docblock states the cross-domain relations are owed (`TfMessage.php:22-25`). They
are now owed **to the generated layer**, not to `TfMessage`.

### user

| aspect | value | cite |
|---|---|---|
| parent | `tf_users`, **33 columns** | `200001:63` |
| companions | **19** | `200001:104-236` |
| HAND model | `TfUser.php` — **21** relations (19 companions + 2 mandate) | `TfUser.php:110-231` |
| GENERATED model | `TlUser.php` — **109** relations | generated |
| HAND relations to messages/channels/dialogs | **0** | — |
| GENERATED relations to other entities | **many** — the reverse half of every peer pair naming a user | §9 |

### chat

| aspect | value | cite |
|---|---|---|
| parent | `tf_chats`, **13 columns** | `200002:48` |
| companions | **5** | `200002:70,84,97,122` + `tf_chats_access_hash` |
| HAND model | `TfChat.php` — **6** relations (4 companions + 2 mandate) | `TfChat.php:68-121` |
| GENERATED model | `TlChat.php` — **78** relations | generated |
| HAND relations to messages/dialogs/participants | **0** | — |

### channel

| aspect | value | cite |
|---|---|---|
| parent | `tf_channels`, **34 columns** | `200003:58` |
| companions | **19** | `200003:101-328` |
| HAND model | `TfChannel.php` — **20** relations (18 companions + 2 mandate) | `TfChannel.php:115-236` |
| GENERATED model | `TlChannel.php` — **93** relations | generated |
| `participants` | present in **both** layers now | `TfChannel.php`, generated |

### dialog

| aspect | value | cite |
|---|---|---|
| parent | `tf_dialogs`, **18 columns** | `200004:51` |
| key | `(account_id, peer_type, peer_id)` | `200004:71` |
| companions | **5** | `200004:79-…` |
| HAND model | `TfDialog.php` — **1** relation (`peer(): ?BelongsTo`) | `TfDialog.php:168` |
| GENERATED model | `TlDialog.php` — **1** relation (`peer(): ?BelongsTo`) | generated |
| HAND `Builder` accessors | **6** — **not relations**, see §10.5 | `TfDialog.php:104-129` |

### update-star

| aspect | value | cite |
|---|---|---|
| `tf_updates` + companions | **23** tables | `200030` |
| HAND model | `TfUpdate.php` — **3** relation methods, 15 `Builder` accessors | `TfUpdate.php` |
| GENERATED model | `TlUpdate.php` — **17** relations, of which `userId()`, `chatId()`, `channelId()` are the catalog-declared scalar anchors | generated |
| `tf_updates_message.message_id` | exists, **no FK** — deliberate | `200030:139`, `299999:36-38` |

`299999:36-38` gives the reason and it is a good one: "'msg_id' refs — deliberate soft
routing links (the row may be mirror-id or not yet mirrored); never FK'd." A soft link is
still joinable in SQL. It is recorded here so it is not mistaken for an oversight — and note it
is *still* a soft link: no FK was added for it by this slice.

---

## 3. THE JOIN-FAILURE TABLE

The core deliverable. **Two verdicts are now split**, because a row can be unjoinable at the
constraint layer and joinable at the relation layer — which is the normal state after §9.

| # | expected relation | constraint layer | relation layer | blocking fact |
|---|---|---|---|---|
| 1 | **message → author** | no FK | **RESOLVED** (`TfMessagesFromId::fromId()`) | Discriminator read at query time, not by an FK. |
| 2 | **message → peer** | no FK | **RESOLVED** (`TfMessages::peer()`) | same |
| 3 | **message → reply target** | no FK on `reply_to_msg_id` | **PEER RESOLVED**; the message link stays soft | two near-identical peer pairs — §6 defect 3 |
| 4 | **message → forward source** | no FK | **RESOLVED** (4 forwards on `TfMessagesFwdFrom`) | |
| 5 | **message → media** | FK CASCADE (`299999:47-50`) | was already modelled | the one long-wired vertical edge |
| 6 | **message → document/photo** | **NO SUCH COLUMN** | **STILL NO SUCH COLUMN** | `tf_messages_media` has no document/photo reference (`200012:25-88`). Nothing in §9 invents one. |
| 7 | **media → document / photo** | no edge | **STILL NO EDGE** | unchanged by this slice |
| 8 | **dialog → peer** | no FK | **RESOLVED** (`TlDialog::peer()`, `TfDialog::peer()`) | |
| 9 | **chat → participants** | no such table | **STILL NO SUCH TABLE** | no participant table for chats exists |
| 10 | **chat → full record** | no such table | still none | `chatFull` stored nowhere |
| 11 | **channel → full record** | no such table | still none | `channelFull` stored nowhere |
| 12 | **channel → participants** | FK (`299999:61-64`) | **RESOLVED in both layers** | |
| 13 | **channel participant → user** | no FK | **RESOLVED** (`TlChannelParticipant::userId()`) | catalog-declared scalar; the one §1b row a scalar anchor closes |
| 14 | **update → message** | soft, no FK | still soft | `299999:36-38` stands |
| 15 | **user → messages** | no FK, no index | **RESOLVED, UNINDEXED** | relation exists; still a scan — see §10.6 |
| 16 | **message → entity (formatting)** | FK CASCADE | already modelled | |
| 17 | **message → reply markup buttons** | **FIXED** (§8) | — | |

**Tally after §9:** 9 rows are resolved at the relation layer (1, 2, 3-partial, 4, 8, 12, 13,
15); **5 remain structurally impossible** (6, 7, 9, 10, 11) because no column or table exists —
the model layer cannot conjure one; **1 remains soft by design** (14).

> **Rows 6, 7, 9, 10 and 11 are the honest remainder.** The horizontal slice closed every gap
> that was a *missing edge over existing columns*. It did not and cannot close a gap that is a
> *missing column or missing table*. Those remain exactly as §5c lists them.

---

## 4. The dependency DAG

**Unchanged by this revision — no migration moved, so the write-order contract is unchanged.**

```
tier 0  telegram_accounts            200009_000201:25
          │  (the only enforced edge in the schema)
          ▼
tier 1  tf_users / tf_chats / tf_channels / tf_dialogs
          │
          │  RESOLUTION GATE: every peer_*_type/_id pair in tier 2 resolves
          │  against exactly one tier-1 table, chosen by the type int
          │  (1=user 2=chat 3=channel — 200010:23-24).
          ▼
tier 2  tf_messages  + companions
          ├─ tf_messages_entities / tf_messages_media  ── FK CASCADE  ✅ order-free
          ▼  ⛔ HARD STOP — no edge exists, and none can be added without
             a new column (§3 rows 6-7)
tier 3  tf_documents / tf_photos     200020:14 / 200021:14

tier 2' tf_channel_participants      200031:72  ── FK to tf_channels
tier 2'' tf_updates                  200030:40  ── soft msg_id link to tier 2
```

### The order that matters, stated plainly

1. **`telegram_accounts`** first.
2. **Tier 1 identity before any message row.** Writing a message before its peers exist
   produces a row that is *storable but unjoinable forever* — and now also a relation that
   loads empty, which is exactly the ambiguity §9's `UnmappedEntityPeer` exists to prevent.
3. **`tf_messages_media` / `tf_messages_entities` are order-free** (FK CASCADE on the parent key).
4. **`tf_channel_participants` requires `tf_channels`** (RESTRICT).
5. **`tf_messages_media` → `tf_documents`/`tf_photos` cannot be ordered at all.**

---

## 5. Model gap list

### 5a. Declared companion tables with no Eloquent model

**CORRECTION (2026-10-01).** The figure "19 message-domain tables have no model" is stale and
is withdrawn. `TfMessageFromId` already existed, committed in `1e113af5`.

**A SECOND correction, to my own first correction.** The "142 call sites" figure was *not*
stale — 142 was the `belongsTo(` count across `src/Teleframe/Mirror/Models/`. It was simply a
hand-layer number, and this document then set it beside generated-layer figures. **Measured
today: 179 `belongsTo(` call lines and 346 relation methods in the hand layer** (§0.2).

Table counts are unchanged:

| measure | count | how |
|---|---|---|
| `tf_*` tables declared | **266** | `Schema::create('tf_…')`, corrected in §8 |
| Hand-layer PHP files | **250** | files in `src/Teleframe/Mirror/Models/` |
| — of those binding a `$table` | **245** | 5 are traits/base helpers: `MirrorChildModel`, `PinsDeclaredForeignKeyLegs`, `ResolvesEntityPeer`, `ResolvesMessagePeerScope`, `UnmappedEntityPeer` |
| generated-layer model files | **257** | `src/Schema/Generated/Models/` |

**The earlier figure "218 mirror models on disk" is withdrawn as stale** — it was measured
against an earlier migration set. The hand layer has grown, and 218 is no longer a count of
anything present on disk. The "47 declared tables with no model" figure depended on it and is
likewise **not re-derived here**; see §7.

### 5b. Relations that must be added — **CLOSED by §9**

This section previously listed five cross-entity relations as the gap. **They now ship, in
the generated layer** (§9). Restated against what was actually emitted:

| previously recommended | shipped as |
|---|---|
| 1. polymorphic peer resolver on the message family | `Tf*Messages*::fromId()/peer()/savedFromId()/savedFromPeer()/replyToPeerId()`, all `?BelongsTo`, all discriminator-driven |
| 2. `replyToMessage()` | **NOT shipped.** The *peer* half is resolved; the `reply_to_msg_id` message link stays soft (row 14) |
| 3. `TfChannel::participants()` | shipped in **both** layers |
| 4. `TfUser::messages()` / `TfChannel::messages()` | shipped as reverse relations — **but see §10.6, they are unindexed** |
| 5. `TfDialog::peer()` | shipped in **both** layers |

**What shipped is the generated layer.** The hand layer received only the mandate subset
(§9.3). Items 2's message half and 4's index remain open.

### 5c. Recommended schema changes — NOT written (recommendation only)

Per the brief, no migration or model was written. In priority order:

1. **Add `media_document_id` / `media_photo_id` (+ `_access_hash`) to `tf_messages_media`**
   and FK them to `tf_documents` / `tf_photos`. Still the only fix for §3 row 6.
2. ~~Add `parent_position` to the PK and FK to the row~~ — **DONE**, see §8.
3. **Inline the missing `tf_messages` child FKs**, matching the `tf_documents` / `tf_photos`
   house style.
4. **Create `tf_chats_full` and `tf_channels_full`.**
5. **Add `tf_chats_participants`** (FK to `tf_chats`) to close §3 row 9.
6. **Add an FK `tf_channel_participants.user_id` → `tf_users`** to close row 13 *at the
   constraint layer* — the **relation** is shipped, the constraint still is not.
7. **Index `tf_messages_from_id(account_id, from_id_type, from_id_id)`** before the reverse
   message query is relied on, or it is a full scan (§10.6).

---

## 6. Defects found, not fixed

1. **`tf_messages_reply_to` has two near-identical peer pairs.**
   `...200010:214-215` declares `reply_to_peer_id_type` / `reply_to_peer_id_id`;
   `...200010:221-222` declares `peer_type` / `peer_id`. Both are `tinyInteger` +
   `bigInteger` defaulting to 0. **§9 resolves both** (`TfMessagesReplyTo::peer()` and
   `::replyToPeerId()`), so a caller cannot now pick the wrong one *by accident* — but nothing
   declares which is semantically the reply peer. Still unresolved as a schema question.

2. **Cross-account relation join omits `account_id`.** Relations are declared as
   `hasOne(X::class, 'id', 'id')` / `belongsTo(TfMessage::class, 'id', 'id')`, while every such
   table has a composite PK of `(account_id, id)`. This is safe only because `AccountScope`
   adds `WHERE account_id = ?` — and `src/Schema/Eloquent/AccountScope.php:18-25` makes that
   scope a **no-op when no `AccountContext` is active**. So a relation traversal outside an
   account context can join message id 5 of account A to companion id 5 of account B.
   **The horizontal relations inherit this exposure and widen it**, since they are now far
   more numerous and are the relations a host will actually use.

3. **The `tf_messages` companion family is undocumented as such** in `299999`, which explains
   the peer and media omissions but not the same-parent edges.

---

## 7. What this document does NOT establish

- **Live schema state.** Nothing was connected to. Every claim is about **the migrations**,
  executed against in-memory SQLite by the graph generator.
- **Whether the horizontal relations are indexed.** They are generated from column pairs; the
  generator does not create indexes. §10.6.
- **`MirrorFactWriter fkClues` and `PeerShapeTool`** — cited by `299999:29-30` as the app-code
  enforcement for peer correctness. **Still not read.**
- **Which side of the polymorphic pair is authoritative** in `tf_messages_reply_to` (defect 1).
- **Whether `chatFull` / `channelFull` are intentionally out of scope**, or merely unbuilt.
- **Row-level data volume.** No row counts, so the cost of an unindexed reverse relation
  (§10.6) is unquantified.

---

## 8. Historical correction (2026-10-02, Task 5.1) — the FK numbers were produced by grepping

> **Read this section as history, not as current state.** §1 and §1a are superseded by it. §1b
> has since been corrected **again**, by this revision — see §1b.

**THE METHOD WAS WRONG, not just the numbers.** The original §1 opened with *"Enumerated from
every `->foreign(` in `src/Laravel/Migrations/`"*. That search cannot see this schema. Foreign
keys reach it through shared `Blueprint` helpers — `linkChild()`, `participantLink()`,
`channelUpdateLink()`, `differenceLink()`, `factKey()` — which are private static methods on the
migration classes, so `grep "->foreign("` sees only literal inline declarations.

Re-measured by EXECUTING the shipped migrations and reflecting with `PRAGMA foreign_key_list`,
grouped by constraint `id` (`bin/generate-relation-graph.php`):

| §1 claim | measured |
|---|---|
| **40** foreign keys across `tf_*` tables | **54** by `tf_*` tables; **55** by all tables |
| §1b `tf_updates`: **1** inbound FK | **17** |
| §1a *"not one joins two different entities"* | **true at the constraint layer** — by child-name prefix, 45 of 55 are vertical and all 45 are `CASCADE`; the other 10 point at `telegram_accounts` (6), `telegram_apps` (1), `tf_channels` (1), `tf_store_buckets` (2) |
| §5a `tf_*` tables declared: **264** | **266** |
| §3 row 17 / §6 defect 1: buttons have no FK to their row | **FIXED** — `tf_messages_reply_markup_rows_buttons → tf_messages_reply_markup_rows` exists, CASCADE, on `(account_id, peer_type, peer_id, id, parent_position)` |
| §1b inbound counts for `tf_messages` (2), `tf_channels` (1), `tf_users` (0), `tf_chats` (0), `tf_dialogs` (0) | **the two key-entity rows were wrong and are corrected in §1b**: `tf_users` **19**, `tf_chats` **5**, `tf_messages` **30**, `tf_channels` **19**, `tf_dialogs` **0**. All are same-parent edges. |

**DOES NOT SURVIVE:** every count in §1 and §1a, the `tf_updates` row, the 264 of §5a, and §3
row 17 / §6 defect 1 (fixed).

**SURVIVES UNCHANGED:** the structural blocks — no FK for the media→document edge, no
`tf_chats_full`, no chat participants table, the soft `msg_id` links — because none is a
counting error.

---

## 9. THE HORIZONTAL RELATIONS — what now ships (measured)

Generator: `src/Schema/Generator/HorizontalEntityRelations.php`. Input: the **executed**
`MirrorShape` + the committed `MirrorCatalog`. **Nothing is transcribed from a hand-written
table list** — the identity tables are reached through `Naming::domainModelFqcn()`, the same
function that decides which class owns `tf_users` / `tf_chats` / `tf_channels`, and the peer
kinds through `PeerShapeTool::PEER_*`, the enum the write path fills the discriminator with.

### 9.1 The emitted total, and why it reconciles exactly

| mechanism | forward | reverse | total |
|---|---:|---:|---:|
| 1 — discriminated peer pairs | 87 | 231 | 318 |
| 2 — catalog-declared scalar entity columns | 6 | 6 | 12 |
| 3 — tenant root | 0 | 11 | 11 |
| **total** | **93** | **248** | **341** |

**341 is exactly the measured generated-layer delta** (747 − 406). It also reconciles by type:
`BelongsTo` +93 (the forward halves), `HasMany` +248 (the reverse halves). Nothing else in the
tree moved, and nothing was lost.

### 9.2 Mechanism 1 — the 87 peer pairs across 66 tables

A `tf_*` table carrying an integer-typed `{base}_type` beside an integer-typed `{base}_id`.

- **72 pairs use a peer-named (unqualified) base** — 8 distinct: `peer`, `saved_from_peer`,
  `message_peer`, `reply_to_peer_id`, `saved_peer_id`, `boost_peer`, `theme_peer`,
  `starref_peer`. An unqualified `Peer` may hold all three kinds, so each yields **3** reverses:
  72 × 3 = **216**.
- **15 pairs use a `BASE_ENTITY`-qualified base** — 8 distinct: `from_id`, `saved_from_id`,
  `guestchat_via_from`, `recent_repliers`, `to_id`, `host_id`, `owner_id`, `released_by`. Each
  names exactly one entity, so each yields **1** reverse: 15 × 1 = **15**.
- 216 + 15 = **231** peer-pair reverses. Cross-check: the generated tree contains exactly **231**
  `->where('<child>.<base>_type', …)` clauses.

Forward halves are `peer()`, `fromId()`, `savedFromPeer()`, `replyToPeerId()` — named after the
base — returning `?BelongsTo`. Reverse halves are named `<base><ChildStem>()`, e.g.
`TlUser::peerDialogs()`, `TlUser::peerDialogsNotifySettings()`, and are built as:

```php
return $this->hasMany(TfDialog::class, 'peer_id', 'id')
    ->where('tf_dialogs.peer_type', 1);
```

**Both legs are constrained** — the id leg by the relation, the kind leg by the predicate — so a
reverse relation cannot match the wrong entity table even though the peer id spaces overlap.

**Why both directions are emitted.** A `belongsTo` constrains the *parent* table, so a
discriminator living on the *child* cannot be expressed on it: a plain `belongsTo` would match
on `peer_id` alone, and Telegram's peer id spaces overlap in exactly the way that produces a
confidently wrong answer. The reverse half is the honest one.

**Forward halves pick the table FROM the discriminator**, via
`ResolvesEntityPeer::entityPeer()`, never by guessing.

**Why `MorphTo` was rejected** (`ResolvesEntityPeer.php:17-32`): `Relation::$morphMap` is
**static and process-global**. A package populating it would overwrite — or be overwritten by
— the host application's own morph map. That is not a library's move. Two further reasons: a
`MorphTo` does not pin the tenant leg (`account_id` → `telegram_accounts.user_id`), and the
discriminator is a `tinyint` off the TL constructor order, not a class-name string, so the map
would buy nothing a three-arm `match` does not do readably.

### 9.3 The hand layer's mandate subset — deliberately incomplete

`TfUser`, `TfChat` and `TfChannel` carry exactly **two** horizontal methods each — the hand
layer's mandate, recorded in the files themselves (`TfUser.php:210-236`):

| model | mandate methods |
|---|---|
| `TfUser` | `peerDialogs()`, `userIdUpdates()` |
| `TfChat` | `peerDialogs()`, `chatIdUpdates()` |
| `TfChannel` | `peerDialogs()`, `channelIdUpdates()` |
| `TfDialog` | `peer()` |

**The generated anchors are COMPLETE (109 / 78 / 93); the hand models carry this 2-method
subset each.** The gap is deliberate and *measured*, not drift —
`tests/Schema/Mirror/HorizontalEntityRelationsTest` asserts the difference rather than letting
it drift. The method names are the generator's, so both surfaces are greppable by one string.

### 9.4 Mechanism 2 — the catalog-declared scalar anchors

`MirrorCatalog` declares each anchor's fields with their stored type. Where it declares
`user_id` / `chat_id` / `channel_id` as an integer scalar, that IS a reference to the identity
table. **Measured: 6 columns across 4 tables.**

| table | column | → |
|---|---|---|
| `tf_updates` | `user_id`, `chat_id`, `channel_id` | users / chats / channels |
| `tf_admin_log_events` | `user_id` | users |
| `tf_channel_updates` | `channel_id` | channels |
| `tf_channel_participants` | `user_id` | users |

`tf_channel_participants.user_id` is the one that closes §3 row 13 at the relation layer.

### 9.5 Mechanism 3 — the tenant root

`tf_accounts` is the one mirror table with **no foreign key at all** (the graph generator
measures exactly **1** `tf_*` table with no FK), and it carries no `account_id` column, so it
structurally cannot hold the tenant FK — yet every other mirror table points at it. It gets a
`hasMany` to each TL domain anchor, on `account_id = ` `id`, with the anchor set read from
`Naming` rather than from a list of table names.

`TfAccounts` (generated) — **11** relations, all `HasMany`:
`users`, `chats`, `channels`, `dialogs`, `documents`, `messages`, `photos`, `stickerSets`,
`updates`, `wallpapers`, `channelParticipants`.

### 9.6 The three outcomes stay distinct — and why raising matters

| discriminator | outcome |
|---|---|
| **kind 0** | `null`. A **real stored value** — the writer leaves both halves at their default rather than inventing a peer Telegram did not send. |
| **kind 1 / 2 / 3** | a `BelongsTo` relation. Emptiness means **"the entity row is not mirrored yet"** — a different fact, and visible as such. |
| **anything else** | **raises `UnmappedEntityPeer`.** The enum and the stored data have diverged. |

A **missing** discriminator column also **raises** rather than reading as `0` —
`getAttribute()` returns `null`, `(int) null` is `0`, and `0` means "no peer", so a renamed or
dropped column would silently turn every peer on the table into "no peer"
(`ResolvesEntityPeer::peerKind()`, `UnmappedEntityPeer::forMissingColumn()`).

**Why raising matters.** A `belongsTo` whose key does not exist returns an **empty** relation,
and an empty relation is **indistinguishable from "genuinely no rows"**. A host writing
`$dialog->peer->first_name` against a discriminator the mirror cannot interpret would get
`null` and conclude the data is missing from **Telegram** rather than from the mapping. That
is confidently wrong, and it is worse than absent. Hence the exception is raised loudly
instead of degraded.

---

## 10. Stated limits — recorded, not fixed

### 10.1 The hand layer is a subset, by design
See §9.3. Completeness lives in the generated anchors; the hand layer carries the mandate.

### 10.2 `BASE_ENTITY` is one hand-maintained list of 8 bases
Nothing in the executed DDL says which kinds a discriminator may hold. The column is a
`tinyint`, the write path stores whatever the wire normalised into it, and the TL declares the
field as `Peer` — the union that admits all three. "Can this base ever be kind 2?" is a fact
about **Telegram's type system**, not about this schema, and it has to be stated.

**The default is DENY.** A base whose name carries `peer` is unqualified and gets all three; a
base that names an entity gets only that entity; a base that does **neither** gets **no
reverse relation at all**. The reason is asymmetry: `messageActionChatJoinedByLink.to_id:Peer`
is always kind 1, so a reverse relation from `tf_chats` would return an empty set for every
row the mirror can hold — indistinguishable, to a host, from the bug this whole slice exists to
kill. **A missing accessor costs a lookup; a guaranteed-empty one costs a wrong reading.**

`HorizontalEntityRelationsTest::test_every_derived_peer_base_names_an_entity()` keeps the list
closed: a new peer base that is neither peer-named nor listed turns that test red.

### 10.3 Name-based entity references were REJECTED as unsafe — measured
`tf_messages_media.user_id` and `tf_messages_service_action.{user_id, chat_id, channel_id}`
are **deferred-nested-object residue** —
`2026_09_14_200012_create_tf_message_medias_tables.php:57` calls them "Scalar residue of each
ctor (nested objects deferred)", repeated at `:151` for all 67 ctors — and hold **0 on
essentially every row**. `tf_store_buckets.channel_id`
is a peer under a different name. **A name-only rule emits guaranteed-empty relations**, which
is §10.2's trap exactly. The catalog is the discriminator, not the column name.

### 10.4 Layering inversion — deliberate
The generated layer now **imports the hand layer**: 66 of 257 generated models carry
`use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer;` (and `use ResolvesEntityPeer;`
in the class body). Normally generated code depends on nothing hand-written. This inversion is
forced by the **ownership split** — the peer-kind table lives with the hand models, and both
files document it. Recorded, not hidden.

### 10.5 `TfDialog`'s six `Builder` accessors are NOT relations — known trap, shipped
`TfDialog::notifySettings()`, `pts()`, `draft()`, `folderId()`, `ttlPeriod()`, `folder()`
(`TfDialog.php:104-129`) each return a raw **`Builder`**, not a `Relation`. Consequences:

- `Model::isRelation()` is **false** for all six, so `Model::getRelationValue()` /
  `relationLoaded()` / eager loading do not apply;
- `$dialog->notifySettings` yields **`null`**, not the row;
- `$dialog->notifySettings()->first()` works only because the accessor returns a Builder.

This is deliberate and pre-existing — `TfDialog.php:27` explains it: the PK is composite, so
the model uses the Builder-accessor pattern instead of Eloquent `hasOne`. **Shipped, not
fixed, and out of this slice's scope.** The **generated** `TlDialog` has **0** such accessors
(measured), so the trap is hand-layer-only. Recorded here because it is the one place where
"it has a method with the relation's name" is false.

### 10.6 Reverse relations are unindexed
The generated reverse relations constrain `({child}.{base}_id, {child}.{base}_type)`. No index
is declared on those pairs, so "all messages by this user" remains a scan. §5c item 7.

### 10.7 The change detector is blind to `Closure` bodies
`DaemonCommand::handlerShape()` (`src/Laravel/Console/DaemonCommand.php:1141-1148`) reduces a
handler reference to a comparable shape, and a `Closure` compares as the **literal
`'closure'`**. A changed closure **body** is therefore invisible to the digest. Deliberately
coarse, and stated as a limit rather than silent.

### 10.8 71 tables carry a peer pair; 66 got relations
The graph generator measures **71** `tf_*` tables carrying a `{base}_type` + `{base}_id` pair.
The generator emits for **66**. The difference is its own filters: the discriminator must cast
to an **integer** under the generator's `castFor()`, and the column must not already be a
constrained FK column. This is what excludes `venue` — a `GeoPoint` in TEXT columns, so
`venue_type` casts to `string` and no peer edge is derived. Nothing in the generator names
`venue`.

### 10.9 `TfMessage` got no horizontal relations in the hand layer
The hand `TfMessage` still declares **31** companion-only relations. The horizontal message
relations live on the generated `Tf*Messages*` companions and on `TlMessage`. A host reading
only the hand layer will still see zero cross-entity relations on messages.
