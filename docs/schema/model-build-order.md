# Model build order — the 47 unbound tables, staged for collision-free parallel lanes

**Purpose.** Stage 2 of the owner's method is *models and relations*. This document turns
"tables have no Eloquent model" into an **ordered build list** with a stated reason for each
position, a **collision analysis** that determines how many positions can be built
simultaneously, the **relation graph that must eventually exist**, and the **decisions that
block stage 2**.

**Method — and its limits.** Migrations and model files only, read as text.
**No database was connected. No `.env` or credential file was read. No migration was run.
No Telegram call was made. No model was written or edited by this lane.** Every claim is
cited `file:line`; anything not directly verified is marked `UNVERIFIED`.

---

## 0. The count is 47, not 19 — and the 19-list was itself incomplete

**`docs/schema/model-relation-gap.md` §5a has been rewritten by a concurrent lane and now
**withdraws** its own headline figure.** It states: *"CORRECTION (2026-10-01). The figure
'19 message-domain tables have no model' is stale and is withdrawn."* That same correction
names this file as where the real list belongs: *"The build list must be regenerated from
the real 47, not patched from the old 19. `docs/schema/model-build-order.md` is the place
that list is made."*

**I reproduced the 47 independently, from scratch, and it matches exactly.** Method: the set
difference of (a) every `Schema::create('tf_…')` across `src/Laravel/Migrations/` against
(b) every `$table = 'tf_…'` across `src/Teleframe/Mirror/Models/`.

| measure | count | how it was measured |
|---|---|---|
| `tf_*` tables declared in `src/Laravel/Migrations/` | **264** | `Schema::create\('(tf_[a-z0-9_]+)'`, deduped |
| model files in `src/Teleframe/Mirror/Models/` | **218** | `*.php` count |
| of those, tables a model actually binds via `$table` | **217** | `$table = '…'`, deduped |
| **declared tables with NO model** | **47** | set difference |

Reproduce in one line (bounded):

```bash
LC_ALL=C comm -23 \
  <(grep -rhoE "Schema::create\('(tf_[a-z0-9_]+)'" src/Laravel/Migrations/ \
     | sed -E "s/.*'(tf_[a-z0-9_]+)'/\1/" | sort -u) \
  <(grep -rhoE "\\\$table[[:space:]]*=[[:space:]]*'(tf_[a-z0-9_]+)'" \
     src/Teleframe/Mirror/Models/ | sed -E "s/.*'(tf_[a-z0-9_]+)'/\1/" | sort -u) | wc -l
```

`LC_ALL=C` is **load-bearing**: without it `comm` reports "input is not in sorted order"
and silently returns a wrong set difference. A sibling lane got this right; flag it for
anyone who re-runs it.

**Why the original figure was wrong, mechanically:** the 19 counted only the messages slice
and read it as a total. The decisive omission is a **single migration the earlier analysis
never opened** — `2026_09_29_000100_create_tf_mirror_gap_tables.php`, dated **after** the
messages migrations, which contributes **20 of the 47**. A gap list scoped to one directory
of a schema that is still growing is a snapshot of a moving target.

### 0a. Two further defects the 19-list carried, found while rebuilding it

1. **The 19-list was missing a 20th message table.** `tf_messages_rich_message`
   (`2026_09_29_000200_create_tf_messages_rich_message_table.php:74`, PK
   `(account_id, id)`, 4 columns) is in the gap and is not in the withdrawn list. So the
   message domain is **20**, not 19.
2. **Six gap tables are an orphaned duplicate family.** `tf_message_actions`,
   `tf_message_actions_users`, `tf_message_entities`, `tf_message_medias`,
   `tf_message_medias_ttl_seconds`, `tf_message_search` are **singular**
   `tf_message_*`, and there is **no singular `tf_message` parent table anywhere** —
   verified: `Schema::create('tf_message'` returns no match. They duplicate the plural
   `tf_messages_media` / `tf_messages_entities` domain under a second naming scheme, keyed
   on their own id space (`message_media_id`, `message_entity_id`, `message_action_id`)
   rather than on `tf_messages.id`. **Building models for these six may be building the
   wrong thing** — see §5, decision (d).

---

## 1. THE ORDERED BUILD LIST

### 1a. The ordering rule, stated before it is applied

> **R1 — dependency first.** A model that another model *references* comes first.
> **R2 — leverage second.** Among models with no unmet dependency, order by the number of
> Eloquent relations the model unlocks, highest first.
> **R3 — deterministic tie-break.** Equal `unlocks` ⇒ ascending table name (byte order,
> `LC_ALL=C`). R3 exists so two lanes reading this document produce the same list; it
> carries no engineering meaning.

**"Unlocks" is counted mechanically, not by judgement.** Adding a model `M` for table `T`
unlocks:

- **+1** a relation on the **attach-point** model — the existing model that already declares
  the family (`TfMessage` carries 13 relations today, at `TfMessage.php:58,63,68,73,78,83,
  88,93,98,103,108,113,118`), **or a brand-new parent model** where none exists;
- **+1** a `belongsTo` back to the attach point on `M` itself — the established shape of
  the family, e.g. `TfMessageFromId::message()`, which returns
  `$this->belongsTo(TfMessage::class, 'id', 'id')`; **cite that one by method name, not by
  line — the file is being edited concurrently (§1e)**;
- **+1 more** if `T` carries a `(x_type, x_id)` peer pair, because only then does `M`
  participate in the cross-entity peer resolution governed by §4(a).

Baseline **2**; peer-pair tables **3**; the nested-rows table **3** because it also owns
the only intra-model edge in the message family.

**Template choice is part of the position, and it is not uniform.** Three base classes exist
and each is correct for a different subset of the 47:

| base class | `$primaryKey` | use for |
|---|---|---|
| `TfMirrorModel` (`src/Schema/Eloquent/TfMirrorModel.php:15`, `:18-21`) | `id` | **root/parent tables** — the 4 with no declared PK, and every new parent in the gap |
| `TfChildModel` (`src/Schema/Eloquent/TfChildModel.php:13`, `:15`) | `parent_id` (virtual) | companions whose real PK column is literally `id` — the large majority |
| `MirrorChildModel` (`src/Teleframe/Mirror/Models/MirrorChildModel.php:20`, `:22`) | `account_id` | updates-domain children; **do not use for the gap** — the gap is not the updates domain |
| *override* | the real column | **8 tables whose PK column is not `id`** — see §2b group V |

All 47 additionally `use AccountScoped;` (`src/Schema/Eloquent/AccountScoped.php`).

### 1b. Group A — the 20 message-domain tables (attach point: `TfMessage` / `TfMessageService`)

All 20 except one live in
`src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php`. Column counts and
PK shapes below are read from each `Schema::create` block, not inferred.

| # | table → class to create | `create` at | cols | PK | relation unlocked | unlocks | why here |
|---|---|---|---|---|---|---|---|
| **1** | `tf_messages_reply_markup_rows` → `TfMessageReplyMarkupRows` | `200010:386` | 4 | `(account_id,id,position)` | `replyMarkupRows()` `hasMany` | **3** | Owns the only intra-model edge (`buttons()`); **R1 forces it ahead of #13**; sole structural blocker |
| **2** | `tf_messages_guestchat_via_from` → `TfMessageGuestchatViaFrom` | `200010:193` | 4 | `(account_id,id)` | `guestchatViaFrom()` `hasOne` | **3** | Peer-pair holder; participates in §4(a) |
| **3** | `tf_messages_service_saved_peer_id` → `TfMessageServiceSavedPeerId` | `200010:448` | 4 | `(account_id,id)` | on **`TfMessageService`**, not `TfMessage` | **3** | Peer-pair holder; **its parent has a disjoint id space** (`200010:418`) — the attach point is a *different model*; see §1d |
| 4 | `tf_messages_restriction_reason` → `TfMessageRestrictionReason` | `200010:326` | 6 | `(account_id,id,position)` | `restrictionReasons()` `hasMany` | 2 | the other `(…,position)` vector |
| 5 | `tf_messages_reply_markup_rows_buttons` → `TfMessageReplyMarkupRowsButtons` | `200010:394` | **17** | `(account_id,id,position)` **+ out-of-PK `parent_position` (`:398`)** | *none on `TfMessage`* — reached only via #1 | 2 | R1-forced after #1; **BLOCKED until M1 adds `parent_position` to the PK** |
| 6 | `tf_messages_rich_message` → `TfMessageRichMessage` | `2026_09_29_000200:74` | 4 | `(account_id,id)` | `richMessage()` `hasOne` | 2 | the 20th message table; **missed by the withdrawn 19** |
| 7 | `tf_messages_edit_date` → `TfMessageEditDate` | `200010:255` | 3 | `(account_id,id)` | `editDate()` `hasOne` | 2 | baseline |
| 8 | `tf_messages_effect` → `TfMessageEffect` | `200010:290` | 3 | `(account_id,id)` | `effect()` `hasOne` | 2 | baseline |
| 9 | `tf_messages_from_boosts_applied` → `TfMessageFromBoostsApplied` | `200010:134` | 3 | `(account_id,id)` | `fromBoostsApplied()` `hasOne` | 2 | baseline; pairs with #10 on the author read path |
| 10 | `tf_messages_from_rank` → `TfMessageFromRank` | `200010:141` | 3 | `(account_id,id)` | `fromRank()` `hasOne` | 2 | baseline; pairs with #9 |
| 11 | `tf_messages_grouped_id` → `TfMessageGroupedId` | `200010:269` | 3 | `(account_id,id)` | `groupedId()` `hasOne` | 2 | baseline |
| 12 | `tf_messages_paid_message_stars` → `TfMessagePaidMessageStars` | `200010:304` | 3 | `(account_id,id)` | `paidMessageStars()` `hasOne` | 2 | baseline |
| 13 | `tf_messages_post_author` → `TfMessagePostAuthor` | `200010:262` | 3 | `(account_id,id)` | `postAuthor()` `hasOne` | 2 | baseline |
| 14 | `tf_messages_quick_reply_shortcut_id` → `TfMessageQuickReplyShortcutId` | `200010:283` | 3 | `(account_id,id)` | `quickReplyShortcutId()` `hasOne` | 2 | baseline |
| 15 | `tf_messages_report_delivery_until_date` → `TfMessageReportDeliveryUntilDate` | `200010:297` | 3 | `(account_id,id)` | `reportDeliveryUntilDate()` `hasOne` | 2 | baseline |
| 16 | `tf_messages_schedule_repeat_period` → `TfMessageScheduleRepeatPeriod` | `200010:311` | 3 | `(account_id,id)` | `scheduleRepeatPeriod()` `hasOne` | 2 | baseline |
| 17 | `tf_messages_summary_from_language` → `TfMessageSummaryFromLanguage` | `200010:318` | 3 | `(account_id,id)` | `summaryFromLanguage()` `hasOne` | 2 | baseline |
| 18 | `tf_messages_ttl_period` → `TfMessageTtlPeriod` | `200010:276` | 3 | `(account_id,id)` | `ttlPeriod()` `hasOne` | 2 | baseline |
| 19 | `tf_messages_via_bot_id` → `TfMessageViaBotId` | `200010:179` | 3 | `(account_id,id)` | `viaBotId()` `hasOne` | 2 | baseline — **`via_bot_id` is a bare id, NOT a peer pair** (`:181`) |
| 20 | `tf_messages_via_business_bot_id` → `TfMessageViaBusinessBotId` | `200010:186` | 3 | `(account_id,id)` | `viaBusinessBotId()` `hasOne` | 2 | baseline — **bare id, NOT a peer pair** (`:188`) |

### 1c. Group B — the 8 `tf_dialog_filters` tables (attach point: **new** `TfDialogFilter`)

All in `src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php`. The
parent model **does not exist** — verified: no `TfDialogFilter*` file in
`src/Teleframe/Mirror/Models/`. So this family needs a **new root entity model plus 7
children**, and the 7 relation stubs land in that *new* file, which removes them from the
collision set entirely.

| # | table → class | `create` at | cols | PK | unlocks |
|---|---|---|---|---|---|
| **21** | `tf_dialog_filters` → `TfDialogFilter` (**root, new**) | `gap:168` | 12 | `(account_id,id)` | 3 — 1 parent + 7 children gated on it (**R1**) |
| 22 | `tf_dialog_filters_title_entities` → `TfDialogFilterTitleEntities` | `gap:193` | 19 | `(account_id,id,position)` | 2 |
| 23 | `tf_dialog_filters_exclude_peers` → `TfDialogFilterExcludePeers` | `gap:245` | 9 | `(account_id,id,position)` | 2 |
| 24 | `tf_dialog_filters_include_peers` → `TfDialogFilterIncludePeers` | `gap:231` | 9 | `(account_id,id,position)` | 2 |
| 25 | `tf_dialog_filters_pinned_peers` → `TfDialogFilterPinnedPeers` | `gap:217` | 9 | `(account_id,id,position)` | 2 |
| 26 | `tf_dialog_filters_title` → `TfDialogFilterTitle` | `gap:185` | 3 | `(account_id,id)` | 2 |
| 27 | `tf_dialog_filters_color` → `TfDialogFilterColor` | `gap:267` | 3 | `(account_id,id)` | 2 |
| 28 | `tf_dialog_filters_emoticon` → `TfDialogFilterEmoticon` | `gap:259` | 3 | `(account_id,id)` | 2 |

**Decision needed before #21:** does `TfDialogFilter` attach to `TfDialog` (a dialog-filter
is a *dialog* concept) or stand alone? A filter references *peers*, not dialogs, so
`TfDialog::filters()` is not obviously the right edge. This is decision **(e)** in §4.

### 1d. Group C — the 3 `tf_store` tables (attach point: **new** `TfStoreBucket`)

`src/Laravel/Migrations/2026_09_20_000100_create_tf_store_tables.php`. This family has a
**genuine three-level PK chain**, which is the only such chain in the gap and the reason
R1 is load-bearing here.

| # | table → class | `create` at | cols | PK | unlocks |
|---|---|---|---|---|---|
| **29** | `tf_store_buckets` → `TfStoreBucket` (**root, new**) | `store:23` | 6 | `(account_id,bucket)` | 3 — 2 children gated on it (**R1**) |
| 30 | `tf_store_objects` → `TfStoreObject` | `store:34` | 9 | `(account_id,bucket,name)` | 2 |
| 31 | `tf_store_media` → `TfStoreMedia` | `store:52` | 11 | `(account_id,bucket,msg_id)` | 2 |

Note both children carry `bucket` in the PK, so both are scoped by the bucket key and
neither is reachable by `TfMessage`. `msg_id` in `tf_store_media` is a **soft** link of the
kind the gap document records as deliberately un-FK'd elsewhere.

### 1e. Group D — the 6 singular `tf_message_*` tables (attach points: **new** roots)

`src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php` (and
`2026_09_29_000300` for the last). **All six are blocked behind decision (d)** — they are
the orphaned duplicate family of §0a.

> ⛔ **CLASS-NAME COLLISION — READ BEFORE NAMING ANY OF THESE.** The obvious class name for
> three of these tables is **already taken by a class bound to the *plural* table.** Verified
> this pass:
> - `TfMessageMedia` **exists** → `$table = 'tf_messages_media'`
> - `TfMessageEntity` **exists** → `$table = 'tf_messages_entities'`
> - `TfMessageAction` **exists** → `$table = 'tf_messages_service_action'` (note: it carries
>   the `Service` table, and is a child of `TfMessageService`, not `TfMessage`)
>
> Building the naive names would be a **fatal redeclare** — the exact failure the gap
> document's own correction warns about ("Do not rebuild what exists… would have re-created
> `TfMessageFromId` and hit a fatal redeclare"). The names below are disambiguated with a
> `Singular` infix. **This is also why decision (d) should be answered first: if the singular
> family is dropped, these six class names never need to exist at all.**

| # | table → class to create | `create` at | cols | PK | note |
|---|---|---|---|---|---|
| 32 | `tf_message_medias` → **`TfMessageMediaSingular`** (**root, new**) | `gap:132` | 5 | `(account_id,message_media_id)` | own id space; **not** `tf_messages.id`; naive name taken |
| 33 | `tf_message_medias_ttl_seconds` → `TfMessageMediaTtlSecondsSingular` | `gap:142` | 3 | `(account_id,message_media_id)` | child of #32 (**R1**) |
| 34 | `tf_message_entities` → **`TfMessageEntitySingular`** (**root, new**) | `gap:122` | 5 | `(account_id,message_entity_id)` | duplicates `tf_messages_entities`; naive name taken |
| 35 | `tf_message_actions` → **`TfMessageActionSingular`** (**root, new**) | `gap:150` | 4 | `(account_id,message_action_id)` | own id space; naive name taken |
| 36 | `tf_message_actions_users` → `TfMessageActionUserSingular` | `gap:159` | 4 | `(account_id,message_action_id,position)` | child of #35 (**R1**) |
| 37 | `tf_message_search` → `TfMessageSearch` (**root, new**) | `2026_09_29_000300:77` | 3 | **no PK declared** | `TfMirrorModel`; the only one of the six whose naive name is free |

### 1f. Group E — the 4 remaining no-PK / small-family tables

| # | table → class | `create` at | cols | PK | unlocks |
|---|---|---|---|---|---|
| 38 | `tf_accounts` → `TfAccount` (**root, new**) | `2026_09_22_000100:24` | 8 | **no PK** | 2 |
| 39 | `tf_backfill_cursors` → `TfBackfillCursor` (**root, new**) | `2026_09_25_000300:36` | 7 | **no PK** | 2 |
| 40 | `tf_store_backup_runs` → `TfStoreBackupRun` (**root, new**) | `2026_09_24_000100:29` | 12 | **no PK** | 2 |
| 41 | `tf_saved_reaction_tags` → `TfSavedReactionTag` (**root, new**) | `gap:106` | 3 | `(account_id,saved_reaction_tag_id)` | 3 — 1 child gated on it (**R1**) |
| 42 | `tf_saved_reaction_tags_title` → `TfSavedReactionTagTitle` | `gap:114` | 3 | `(account_id,saved_reaction_tag_id)` | 2 — child of #41 |
| 43 | `tf_wallpapers` → `TfWallpaper` (**root, new**) | `gap:76` | 9 | `(account_id,id)` | 3 — 1 child gated on it (**R1**) |
| 44 | `tf_wallpapers_settings` → `TfWallpaperSettings` | `gap:90` | 11 | `(account_id,id)` | 2 — child of #43 |
| 45 | `tf_reactions` → `TfReaction` (**root, new**) | `gap:51` | 4 | `(account_id,reaction_id)` | 2 |
| 46 | `tf_forum_topics` → `TfForumTopic` (**root, new**) | `gap:60` | 3 | `(account_id,id)` | 2 |
| 47 | `tf_story_items` → `TfStoryItem` (**root, new**) | `gap:68` | 3 | `(account_id,id)` | 2 |

**Tally: 47 = 20 (A) + 8 (B) + 3 (C) + 6 (D) + 10 (E).** Of these, **11 are brand-new root
entity models** and **36 are companions**.

### 1f-bis. A naming rule, because table→class derivation is NOT safe on its own

The class name for 46 of the 47 is derivable from the table name by the project's own
convention (`tf_messages_edit_date` → `TfMessageEditDate`, `tf_users_first_name` →
`TfUsersFirstName`, and so on across all 218 existing models). **For the 6 tables in Group D
it is not**, because three of the naive names are already bound to the *plural* tables
(§1e). Verified this pass — see the collision box there.

**Rule for whoever executes the waves: grep the class name before writing the file, not
after.** One bounded command per lane:

```bash
LC_ALL=C test -e "src/Teleframe/Mirror/Models/<ClassName>.php" && echo "TAKEN — pick another name"
```

A collision is a **fatal redeclare at autoload time**, not a soft failure, and 45 of the 47
lanes will never hit it. The one lane that does (Group D) is the lane that must check.

### 1g. Lane status: `TfMessageFromId` is in flight — do not rebuild, do not touch

| model | status | evidence |
|---|---|---|
| `TfMessageFromId` | **lane-IN-PROGRESS — not a clean lane-completion** | `src/Teleframe/Mirror/Models/TfMessageFromId.php`; a committed baseline exists (`1e113af5`) and a **sibling lane is editing it right now** — `git status --short` reports it ` M` with a **+15-line** uncommitted delta, and the same lane rewrote `docs/schema/model-relation-gap.md` (71 lines) |

It is **not** in the 47 and **no lane may create, edit, or re-create it** — that collides
with a live writer. It is the message→author *pair holder*; the +15-line delta is
**consistent with** that lane adding the author resolution §3 row 1 needs, but **what it
added was not read and is UNVERIFIED here.** Two consequences:

- **§3 row 1 and §4(a) are in flight, not green.** Re-read this document against the
  finished `TfMessageFromId` **before** building the §4(a) resolver. Two resolvers is the
  likely failure mode here, not a missing file.
- **§2a's collision finding is unaffected**: that lane edits `TfMessageFromId.php`, not
  `TfMessage.php`, so wave 0 still has exactly one owner per file.

---

## 2. COLLISION ANALYSIS — how many of the 47 can be built at once

**Answer: all 36 companion models are file-independent. 11 attach-point files are the only
serialisation points, and each has exactly one owner.**

### 2a. The collision, stated exactly

The 47 models land in **one directory**, `src/Teleframe/Mirror/Models/`, as **47 distinct
files** — so they do **not** collide with each other. What collides is the **attach point**:
36 of the 47 require a new relation method appended to an *existing* model, and **19 of them
require it on the same file**, `src/Teleframe/Mirror/Models/TfMessage.php`. That is 19
writers on one file.

The other 17 spread across attach points that are either **brand-new files** (Group B, C, D,
E roots) or small existing ones. This is why grouping by attach point is the right axis, and
why the 47 is far more parallelisable than "47 models" sounds.

### 2b. The groups

| group | positions | attach-point file(s) edited | model files created | may run parallel with |
|---|---|---|---|---|
| **W0-a** | *stubs only* | `TfMessage.php` **only** | none | W0-b…W0-e |
| **W0-b** | *stubs only* | `TfMessageService.php` | none | W0-a, W0-c…e |
| **W0-c** | *stubs + new roots* | **new** `TfDialogFilter.php` | none | W0-a, W0-b, W0-d, W0-e |
| **W0-d** | *stubs + new roots* | **new** `TfStoreBucket.php`, `TfMessageMedia.php`, `TfMessageAction.php`, `TfAccount.php`, `TfBackfillCursor.php`, `TfStoreBackupRun.php`, `TfSavedReactionTag.php`, `TfWallpaper.php`, `TfReaction.php`, `TfForumTopic.php`, `TfStoryItem.php`, `TfMessageSearch.php` | none | W0-a, W0-b, W0-c, W0-e |
| **W0-e** | *stubs* | `TfMessageSearch.php` / any other existing attach point not listed above — **UNVERIFIED: I did not audit every candidate parent** | none | W0-a…d |
| **W1** | all 47 | none | **47 files, one per table** | every other W1 lane |
| **M1** | *migration* | new migration: `parent_position` → buttons PK | none | W1 (see 2c) |

**W0 (5 lanes) → W1 (47 lanes, or fewer if grouped).** W1 lanes are mutually independent:
disjoint files, no shared symbol, and the class name of every model is derivable from its
table name by the stated rule, so there is **no read-after-write between any two W1 lanes** —
including `TfMessageReplyMarkupRows` and `TfMessageReplyMarkupRowsButtons`, which can be
written concurrently precisely because the naming rule makes the reference resolvable
without reading the sibling.

**No group needs a shared base-class or shared-trait change.** `TfMirrorModel`,
`TfChildModel`, `MirrorChildModel` and `AccountScoped` all already exist and are read-only
for this work. That is the single most important fact for parallelisability: **the usual
"shared base class" serialisation does not apply here.**

**Critical path: W0 → W1.** Depth 2. Width 5, then 47.

### 2c. What must NOT run in parallel, and why

1. **W0-a vs everything else in W0.** `TfMessage.php` is one file, 19 writers. Serial by
   necessity; single owner.
2. **W0-a vs the §4(a) peer resolver.** The resolver adds `peer()` to the *same file* as the
   19 stubs. One owner writes both in wave 0, or the resolver is a second wave. Never two
   lanes.
3. **The §4(b) `AccountScope` fix vs any W0/W1 lane.** The fix changes what **every**
   relation means, retroactively, for **182** models that `use AccountScoped`. Running it
   concurrently with lanes that are *adding* relations produces a wave whose result depends
   on interleaving. Serialise it: decide first (§4), fix once, globally.
4. **M1 vs W1 position #5.** #5 cannot be written honestly before `parent_position` is in
   the PK. Today `parent_position` is a plain column (`:398`) outside a PK of
   `['account_id','id','position']` (`:412`), so two buttons in one row with equal
   `position` and different `parent_position` **collide on the primary key**, and no FK
   connects buttons to their row. #5 against the current migration is a knowingly broken
   model. **All other 46 W1 lanes are unaffected and may run in parallel with M1.**
5. **The §4(c) `tf_messages_reply_to` disambiguation vs any lane touching reply.**
   `200010` declares `reply_to_peer_id_type`/`reply_to_peer_id_id` (`:214-215`) **and** a
   second `peer_type`/`peer_id` (`:221-222`), both `tinyInteger`+`bigInteger` defaulting to
   `0`/`0`. Nothing says which is the reply peer. Decision before code — §4(c).
6. **Group D (6 singular tables) vs anything, until §4(d) is decided.** They are an
   orphaned duplicate family (§0a). Building all 6 optimistically may build 6 models for a
   representation the project intends to drop. **This is the only group I would hold
   entirely.**
7. **Group B (`tf_dialog_filters`) vs `TfDialog.php`, until §4(e) is decided.**

---

## 3. THE RELATION GRAPH THAT MUST EVENTUALLY EXIST

The eight relations the owner named, as listed in `model-relation-gap.md:251-253`. **Only
one is expressible today, and that one already exists.** These verdicts are unaffected by
the 19→47 correction: they are about *relations between entities*, not about model count.

| # | relation | model pair | plain Eloquent relation? | the honest reason |
|---|---|---|---|---|
| 1 | **message → author** | `TfMessage` ↔ `TfUser` | **NO** | Link is a 2-column polymorphic pair `tf_messages_from_id.from_id_type`/`.from_id_id`, defaulting `0`/`0`. `_type` is a **`tinyInteger` 1=user 2=chat 3=channel** (`200010:23-24`), not a morph-name string. Eloquent `morphTo` needs a real `_type` column and a target declaring `morphMany`, and the three candidate targets disagree in key column count, so no single `morphTo` fits. `TfMessage::from()` returns the **holder** (`TfMessage.php:58`), never `TfUser`. **Needs §4(a).** Holder is in flight (§1g). |
| 2 | **message → peer** | `TfMessage` ↔ `{TfUser,TfChat,TfChannel}` | **NO** | `tf_messages.peer_type` (`:91`) + `.peer_id` (`:92`), `tinyInteger`+`bigInteger`, default `0`/`0`. Same pair problem, at the message *header*. `TfMessage` declares **no** relation for it; deliberately un-FK'd. **Needs §4(a).** |
| 3 | **message → reply target** | `TfMessage` ↔ `TfMessage` (self) | **NO — blocked on a decision** | `tf_messages_reply_to.reply_to_msg_id` (`:213`) is a bare id, no FK, and the table carries **two** peer pairs (`:214-215` and `:221-222`) with nothing declaring which is the reply peer. Even a self-join needs that pair resolved first. **§4(c) is a hard prerequisite.** |
| 4 | **message → forward source** | `TfMessage` ↔ `{TfUser,TfChat,TfChannel}` | **NO** | `tf_messages_fwd_from` carries `from_id_type`/`from_id_id`, `saved_from_peer_type`/`_id`, `saved_from_id_type`/`_id` — three separate polymorphic pairs. One resolver cannot disambiguate three pairs in a single call without three named entry points. **Needs §4(a) plus three named methods.** |
| 5 | **message → media** | `TfMessage` ↔ `TfMessageMedia` | **YES — already done** | FK `CASCADE`, `tf_messages_media.id` = parent id, relation declared `hasOne` (`TfMessage.php:78`). The one fully-wired vertical edge in the message family. **Nothing to build.** |
| 6 | **dialog → peer** | `TfDialog` ↔ `{TfUser,TfChat,TfChannel}` | **NO** | `tf_dialogs`' **primary key is the peer triple** — `primary(['account_id','peer_type','peer_id'])` at `200004:71` — making it the natural anchor for peer resolution, and it is the table with zero relations. `TfDialog` exposes only its own 6 companions, as raw `Builder` accessors, deliberately, because of the composite PK (`TfDialog.php:27`). **Needs §4(a).** |
| 7 | **chat → participants** | — | **NO SUCH TABLE** | **Verified:** `2026_09_14_200002_create_tf_chats_tables.php` contains exactly **5** `Schema::create` — `tf_chats` (`:48`), `_photo` (`:70`), `_migrated_to` (`:84`), `_admin_rights` (`:97`), `_default_banned_rights` (`:122`). None is a participants table. A repo-wide grep for `tf_chats_participants` / `tf_chat_participants` across `src/Laravel/Migrations/` returns **no match**. → **§5.** |
| 8 | **channel → full record** | — | **NO SUCH TABLE** | **Verified:** no `tf_channels_full` / `tf_channel_full` anywhere in `src/Laravel/Migrations/`. `2026_09_14_200003` is exactly **19** `Schema::create` — the bare channel plus 18 companions, none a full record. `channelFull` carries **67 fields** (map §3), stored nowhere. → **§5.** |

**Cross-entity honesty.** Of these eight, **one** (row 5) is a plain Eloquent relation today.
Three (1, 2, 4) are blocked on the *same* missing primitive — a peer-type resolver — which is
why that single resolver is the highest-leverage object in stage 2. One (6) needs the same
resolver plus a non-`belongsTo` shape because the triple is the PK. One (3) needs a decision
before code. Two (7, 8) need a **migration before a model can exist at all**.

**And none of the 47 changes this.** Adding 47 companion models makes 47 *vertical* edges
expressible — each a `hasOne`/`hasMany` to its own subject's companion. **Zero of the 47
advances any of the eight cross-entity relations above.** The 47 and the eight are almost
entirely disjoint workstreams, and the 47 is the cheap one.

---

## 4. THE DECISIONS THAT BLOCK STAGE 2

Recommendations, not decisions. Nothing here was implemented. **(a), (c) and (d) must be
settled before W0**, because each changes the body of methods the lanes write.

### (a) How to express a polymorphic `(type, id)` pair

| option | meaning | cost |
|---|---|---|
| **A1 — one model with a type column** | a single `Peers` model over a table/VIEW/UNION of the three | needs a **table or VIEW**; no `tf_peers` exists (verified), and a `UNION` view is invisible to `AccountScope` and to the composite-PK convention every companion follows → **migration first** |
| **A2 — separate per-type models** | `TfUser`, `TfChat`, `TfChannel` already exist; add a relation to each | the `_type` branch still has to be read at every call site — it is duplicated three times rather than removed |
| **A3 — one resolver over the existing three** | one class switching on the `1/2/3` int | one shared class; **no migration**; branch lives in one place |

**Recommendation: A3, with a backed `int` enum for the discriminator (1=user, 2=chat,
3=channel, `200010:23-24`).**

A1 is disqualified on cost: a schema change to answer a question the schema already answers.
A2 does not remove the branch, it triples it. A3 costs **one new class and zero migrations**,
and it is the only option serving all four blocked relations in §3 (rows 1, 2, 4, 6, and 3
once (c) lands) with the same code.

**A3's honest cost, and why it belongs in wave 0:** it is a *shared* class — one owner, must
exist before the lanes whose casts and future `peer()` call sites must agree with it, and it
cannot be split. That is the second reason W0 is a wave and not a task. **And it may already
be arriving** — see §1g.

### (b) The cross-account leak: `AccountScope` before or after the models?

**The defect, verified.** `src/Schema/Eloquent/AccountScope.php:12-14` states it in the
docblock: "When no context is active the scope is a no-op (returns all rows)" (`:14`). The
implementation agrees — `apply()` (`:18`) reads `AccountContext::current()` (`:20`) and only
adds the `where` inside a `!== null` guard (`:22-25`). Every relation in this family joins on
`id` **alone**, while every one of these tables has a composite PK `(account_id, id)`.
Outside an active account context that fans out across accounts.

**Recommendation: fix it BEFORE the 47 models, and fix it in the model layer rather than in
the scope.**

1. **Before, not after.** All 36 companions inherit the same bare-`id` join key. Building
   first multiplies the blast radius by 36 and leaves 36 new call sites to re-audit; fixing
   first means the audit is *one* shared class. The fix is one file
   (`AccountScope.php`), so "before" is cheap.
2. **In the model layer, not by making the scope throw.** `AccountScoped` is used by
   **182** models (verified by count). Turning the scope itself into a hard error on a
   missing context is a **behavioural breaking change for every one of them** — and the gap
   document records that `TfDialog` and `TfUpdate` already abandoned Eloquent relations
   *precisely because* the composite-PK/account-scope coupling makes them awkward
   (`TfDialog.php:27`). Recommended shape: **leave the scope no-op for raw queries; make the
   *relation* methods require an active context** — an unguarded `->find()` stays possible
   (as today), while a relation traversal, where the fan-out actually happens, fails loudly.

**The honest counter-argument to "before":** the new lanes' tests may currently rely on the
no-op behaviour (a test building a companion without an `AccountContext` starts failing). If
"before" is chosen, an `AccountContext` test helper is a **prerequisite of the fix, not a
consequence of it** — otherwise the lanes will report the leak fix as a regression. Decide
the test-harness question at the same time as the fix.

### (c) `tf_messages_reply_to`: which of the two peer pairs is the reply peer?

`200010:213-222` declares `reply_to_msg_id` (`:213`),
`reply_to_peer_id_type`/`reply_to_peer_id_id` (`:214-215`) — and, in the same
`Schema::create` block, a **second** `peer_type`/`peer_id` (`:221-222`). Both are
`tinyInteger`+`bigInteger` **defaulting to `0`/`0`**. Nothing in the migration says which is
the reply peer and which is the header's own peer, and neither is FK'd. A query author who
picks wrong gets `0`/`0` and **no error**.

**Recommendation: settle it in the `.tl` source before any lane writes
`replyToMessage()`, and encode the answer as a `const` on the model** so the two pairs can
never be silently interchanged. The gap document records that resolving it requires the
`.tl` source, which it did not read; **I did not read it either, so I am not recommending a
side — UNVERIFIED which pair is authoritative.** What I recommend is that the lane not
guess.

### (d) The 6 singular `tf_message_*` tables: build them, or drop them?

The 6 tables of §0a have **no parent table** and duplicate the plural `tf_messages_*` domain
under a second naming scheme, keyed on their own id space. This is the one place where
"make a model" may be the wrong deliverable.

**Recommendation: do not build these 6 until the duplication is resolved.** Two honest
readings, and the choice is not mine: either they are the **newer canonical** representation
(they come from a later migration, `2026_09_29_000100`, and carry richer shapes — 19 columns
in `tf_dialog_filters_title_entities` vs 3 in the plural equivalents) and the plural family
should be retired; or they are a **generated-schema artefact** that duplicates the
hand-authored plural surface and should be dropped. Six models built on the wrong answer are
six models to unpick. **The cheap question to answer first: which family does the ingest
writer actually populate?** That is one grep, and it settles the decision.

### (e) Does `TfDialogFilter` attach to `TfDialog`, or stand alone?

Group B needs a new root model. A dialog filter references *peers*, not dialogs
(`tf_dialog_filters_include_peers`, `_exclude_peers`, `_pinned_peers`), so
`TfDialog::filters()` is not obviously the right edge, and `TfDialog`'s own relations are
already `Builder` accessors by design (`TfDialog.php:27`).

**Recommendation: build `TfDialogFilter` as a standalone root and add `TfDialog::filters()`
only if a real query needs it.** Rationale: the 8 tables are self-contained under
`(account_id, id)`, so the root model is useful with no cross-entity edge at all. Adding
`TfDialog::filters()` speculatively means editing `TfDialog.php` — an existing, deliberately
awkward file — for a relation no requirement demands. **UNVERIFIED:** I did not read
`TfDialog.php` in full.

---

## 5. WHAT IS DELIBERATELY OUT OF SCOPE — needs MIGRATIONS, not models

**Do not build a model for either of these. There is no table. A model whose table does not
exist fails at first query, not at author time.**

### 5a. `chat → participants` — no table exists at all

**Verified this pass:** `2026_09_14_200002_create_tf_chats_tables.php` contains exactly
**5** `Schema::create` — `tf_chats` (`:48`), `tf_chats_photo` (`:70`),
`tf_chats_migrated_to` (`:84`), `tf_chats_admin_rights` (`:97`),
`tf_chats_default_banned_rights` (`:122`). A repo-wide grep across
`src/Laravel/Migrations/` for `tf_chats_participants` and `tf_chat_participants` returns
**no match**.

`chatFull.participants` is a fetchable return type per the map (`chats.getFullChat`) and has
**nowhere to land** for a basic group. The asymmetry that makes this a real gap: channel
participants **do** exist (`tf_channel_participants`, FK'd to `tf_channels`), so channel
participants are storable and chat participants are not.

**A MIGRATION lane** — create `tf_chats_participants` with an FK to `tf_chats`, *then* a
model. Out of scope for stage 2.

### 5b. `channel → full record` — no table, 67 fields stored nowhere

**Verified this pass:** no `tf_channels_full` / `tf_channel_full` anywhere in
`src/Laravel/Migrations/`. `2026_09_14_200003` is exactly **19** `Schema::create` — the bare
`tf_channels` plus 18 `tf_channels_*` companions of the *bare* channel. Per the map,
`channelFull` carries **67 fields** and is fetchable via `channels.getFullChannel`.

**A MIGRATION lane** — create `tf_channels_full` (67 fields, 1:1 with `tf_channels`), *then*
a model. Out of scope for stage 2.

### 5c. What this means for the build order

Both belong in the **same separate lane as M1** (the `parent_position` PK fix) — a
migrations lane — and must be **sequenced after** stage 2's 47 models, not interleaved. If
someone briefs a lane as "finish chat→participants", the correct response is that the first
deliverable is a **migration**; only the second is a model.

---

## 6. DEFECTS found, not fixed

Reported, not touched, per the brief. Each carries a `file:line`.

1. **The 19-list was presented as a total and was also incomplete.** Withdrawn by its own
   author; superseded by §0. Two independent errors: scope-as-total (missed 28 tables) *and*
   an omission within its own slice (`tf_messages_rich_message`,
   `2026_09_29_000200_create_tf_messages_rich_message_table.php:74`).
2. **Six gap tables are an orphaned duplicate family.** `tf_message_medias` (`gap:132`),
   `tf_message_entities` (`gap:122`), `tf_message_actions` (`gap:150`),
   `tf_message_actions_users` (`gap:159`), `tf_message_medias_ttl_seconds` (`gap:142`),
   `tf_message_search` (`2026_09_29_000300:77`) have **no parent table** — there is no
   singular `tf_message` anywhere — and duplicate the plural `tf_messages_*` domain in a
   second id space. Decision (d).
3. **`tf_messages_reply_markup_rows_buttons` — `parent_position` outside the PK.**
   `200010:398` declares `parent_position`; the PK at `200010:412` is
   `['account_id','id','position']`. Two buttons in the same row with equal `position` and
   different `parent_position` **collide on the primary key**, and no FK connects buttons to
   their row. Blocks build position #5.
4. **`tf_messages_reply_to` has two near-identical peer pairs.** `200010:214-215` and
   `200010:221-222`, both `tinyInteger`+`bigInteger` default `0`/`0`, nothing declaring which
   is the reply peer. Decision (c).
5. **Cross-account relation join omits `account_id`.** The family joins on `id` **alone**
   against composite PKs `(account_id, id)`, safe only because of `AccountScope` — which is a
   no-op without an active context (`AccountScope.php:14`, `:22-25`). Decision (b).
6. **Four gap tables declare no primary key at all** — `tf_accounts` (`2026_09_22:24`),
   `tf_backfill_cursors` (`2026_09_25_000300:36`), `tf_message_search`
   (`2026_09_29_000300:77`), `tf_store_backup_runs` (`2026_09_24:29`). Each needs its own
   `$primaryKey` choice, and `TfMirrorModel`'s default `id` is a guess for all four. Not
   verified whether `id` exists in each.
7. **Three of the six singular `tf_message_*` tables have class names already taken** by
   classes bound to the *plural* tables: `TfMessageMedia` → `tf_messages_media`,
   `TfMessageEntity` → `tf_messages_entities`, `TfMessageAction` → `tf_messages_service_action`
   (all three verified by reading each file's `$table`). Deriving a class name from a table
   name is unsafe here, and the consequence is a fatal redeclare. §1e, §1f-bis.
8. **`docs/schema/model-relation-gap.md` contradicts itself on the relation count.** Its
   rewritten §5a says the "142 call sites" figure "is stale … there are 3 references, not
   142", while its own §5b — in the same live file — still says "Of 284 relation
   declarations … and 142 `belongsTo` calls". **I recounted both independently: 142
   `belongsTo(` and 284 `(hasOne|hasMany|belongsTo)(` calls across
   `src/Teleframe/Mirror/Models/*.php`. The §5b figure is correct and the §5a correction of
   it is wrong.** Whoever wrote that correction appears to have matched a narrower pattern
   than intended. It does not change the 47, but it does mean the gap document's correction
   note should not be trusted without recounting.
9. **36 models carry no `AccountScoped` guard** (182 of 218 do). They are all `updates`/
   `dialogs`/`channel_updates` children that declare **zero** `public function` — no
   relations and no accessors — and are reached through their parents' hand-written
   `child()` Builder accessors instead. So account isolation for those 36 rests on
   hand-written `->where('account_id', …)` clauses, not on the trait. This widens §4(b):
   the trait is not the only isolation mechanism in the tree, and a scope-level fix would
   not reach them.

---

## 7. What this document does NOT establish

- **Live schema state.** Nothing was connected to. Every claim is about the **migrations**,
  which may have drifted from a real database in either direction.
- **The wire constructor behind most of the 47.** The "unlocks" column is derived from
  migration column sets and attach points, not from the shape map. Only
  `guestchat_via_from` and `saved_peer_id` → `Peer` are independently confirmed against the
  map's reference table. The `KeyboardButtonRow`/`KeyboardButton` attributions on positions
  1 and 5 are **UNVERIFIED**.
- **Which peer pair in `tf_messages_reply_to` is authoritative** (§4c). The `.tl` source was
  not read. **UNVERIFIED.**
- **`MirrorFactWriter fkClues` and `PeerShapeTool`** — the app-code enforcement cited as the
  substitute for the missing peer FKs, and the stated source of the `1/2/3` legend. **Not
  read in this pass.** If they already enforce or already centralise the peer pair, decision
  §4(a) may be partly pre-answered — and a lane is working on exactly this (§1g). **Check
  before building A3 from scratch.**
- **The complete attach-point map.** §2b lists the attach points I verified. `W0-e` is an
  explicit **UNVERIFIED** catch-all: I did not audit every one of the 47 against every
  candidate parent, so a further existing model may also need a stub.
- **Whether the 8 tables with a non-`id` PK column** (`message_media_id`, `message_entity_id`,
  `message_action_id`, `reaction_id`, `saved_reaction_tag_id`) should use `TfChildModel`'s
  virtual `parent_id`, `MirrorChildModel`'s `account_id`, or an explicit override. The three
  base classes disagree and I did not determine which is correct for these.
- **Whether the 4 no-PK tables have a usable `id` column** — see defect 6.
- **Row volumes.** No counts read, so the cost of the unconstrained-companion orphan window
  is unquantified.
- **Whether `tf_messages_rich_message` and the `gap` tables are populated by the ingest
  writer at all.** §4(d)'s cheap settling question is unanswered here.
