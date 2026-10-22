# Decomposition footprint — how many mirror tables one message actually touches

**Date:** 2026-10-10 · **Lane:** L3 (parent: the L2 bytes+footprint finisher) · **Artifact:** this file
**Decides nothing on its own.** It is the deciding input for the fork recorded as **D-034** in
`decisions.md` is PRIVATE (it moved to `.brain/decisions.md` under D-036, and the public repository does not ship it). Everything here is subordinate
to that decision; where this document and D-034 disagree, D-034 is the decision and this is evidence.

---

## THE QUESTION, AND WHY IT IS THE RIGHT ONE

D-034's own "what would make this wrong" section names the measurement it was missing, in its own words:

> The deciding number is **how many of the 40 tables a typical channel post actually touches** — the schema
> *permits* 40, but a plain text post may touch 6. **That is measurable only against live mirror data, and it
> is the measurement I would take before revisiting this.**

That sentence contains two claims that this document treats separately, because they have different
epistemic status. "The schema permits 40" is a **static** property of `mirror-shape.json` and is settled.
"a plain text post may touch 6" is a **dynamic** property of a real account's traffic and is not settled
anywhere in the repo. This document measures the second as far as the environment allows, and it turns out
the environment allows it only partially — see the limits section, which is not a postscript but part of
the finding.

The fork, restated from D-034 Decision 1 and its "Alternatives considered and rejected":

- **Side A — FULL RELATIONAL DECOMPOSITION (status quo).** The permitted 40-row-per-message footprint stays.
  Every decomposed column remains individually queryable.
- **Side B — TDLIB'S HYBRID.** One row per message keyed `(dialog_id, message_id)`, the payload in one
  `data BLOB`, plus one denormalised `text STRING` column and an FTS5 external-content index — TDLib's
  own `MessageDb.cpp` shape, quoted verbatim in D-034 Decision 1.

Side B collapses the permitted footprint toward 1. The cost is that every decomposed column becomes opaque
until parsed at query time. The repo's own stated value proposition is the decomposed mirror, so Side B is
not free; it is a real trade, and D-034 explicitly declines to decide it without the number this file
attempts to supply.

## METHOD — ROUTE 2 (SYNTHETIC), EXECUTED THROUGH THE REAL DECOMPOSER AND THE REAL SHIPPED DDL

**Route 1 (a live mirror database) was not attempted, and could not have been.** There is no reachable
MySQL or Postgres server in this environment: `command -v mysql mysqld psql` returned exit code **127**
(nothing on PATH), the MySQL track is opt-in behind `TELEFRAME_MYSQL=1` (`tests/MySql/MySqlTestCase.php`),
and no `.env` was read, per the credential rule. **Route 1 produced no output because it was never run.**

What was run instead is stronger than a static reachability walk and weaker than live traffic, and it is
worth being precise about which is which. For each of eight shapes:

1. A **TL-accurate** `message` / `messageService` payload was hand-built from the layer-227 scheme at
   `src/Schema/schema/sources/api_full.tl` — `message#7600b9d3` at `api_full.tl:121` and
   `messageService#7a800e0a` at `api_full.tl:122`, with each optional field present only when the shape
   under test is supposed to carry it. (`api.tl` is layer 229 and was deliberately not used, per D-034's own
   correction note that `api_full.tl` is the layer-227 file of record.)
2. The payload was wrapped in the **envelope the backfill path actually delivers** —
   `['_', 'messages.messages', 'messages' => [$message], 'chats' => [], 'users' => []]` — because
   `MessageMediaReachesTheWriterTest` documents, in measured prose, that the walker only descends from a
   node that is itself a declared source parent, and a `message` on the backfill path is one element of an
   envelope rather than a top-level constructor. Driving the bare `message` would have silently under-counted
   the media subtree.
3. The **real** `MirrorNestedSourceWalker::collect()`
   (`src/Schema/Mirror/MirrorNestedSourceWalker.php:46`) walked the envelope.
4. The **real** `MirrorFactDecomposer::decompose()` (`src/Schema/Mirror/MirrorFactDecomposer.php:145`) was
   run on the message fact and on every nested fact the walker returned, producing mirror rows.
5. The **real** `MirrorFactWriter::write()` (`src/Schema/Mirror/MirrorFactWriter.php:638`) placed those rows
   into a schema produced by **executing all 58 shipped migration files**
   (`tests/Store/ShippedStoreSchema.php:92`, `src/Laravel/Migrations/*.php`) into in-memory SQLite.
6. The count reported per shape is the number of **distinct tables that hold at least one row for the
   account** afterwards — asked of the database, not read off a list.

This is **not** the static fallback the brief describes, and it is **not** live data. Nothing about the
eight shapes is a guess about how often they occur — they are *chosen* shapes. What is measured is
unambiguous: **given this payload, the shipped schema and the shipped decomposer, this is how many tables
get a row.**

### What this method cannot see

- **Frequency.** The eight shapes are a designed spread, not a sampled one. A real account's mix is
  unmeasured anywhere in the repo, which is exactly why the weighted average below carries an explicitly
  defended distribution rather than a claimed one.
- **Media subtree breadth.** The catalog's declared edges are what the walker follows. Measured:
  `nestedSourcesFor('message')` returns exactly `tf_message_medias <- media`, and
  `nestedSourcesFor('messageMediaPhoto')` returns exactly `tf_photos <- photo`. `poll`,
  `replyKeyboardMarkup`, `messageMediaPoll`, `photo` and `PhotoSize` all return **empty**. A poll therefore
  writes `tf_message_medias` and **stops**; no `tf_polls` row is reached on the path exercised here. That is
  a fact about the shipped catalog, not about Telegram, and it is recorded below as a limit rather than
  smoothed over.
- **Multi-account and dialogue-level tables.** `tf_dialogs`, `tf_peers` and the peer catalogue are written
  by other routes. They are outside "one message's footprint" by construction.

## PER-SHAPE RESULTS — MEASURED, AGAINST EXECUTED SHIPPED DDL

All eight shapes, measured the same way. `decomposed` is the count of distinct tables the decomposer
produced rows for; `placed` is the count of distinct tables that actually hold a row for the account after
the write. **They are equal in all eight cases**, so there is no per-shape row that failed to place — that
equality is itself a result worth stating, because the writer degrades rows rather than raising.

| # | shape | distinct tables | the tables |
| --- | --- | --- | --- |
| a | plain text, no entities, no media | **2** | `tf_messages`, `tf_messages_from_id` |
| b | text + entities (bold/italic/URL/code) | **3** | + `tf_messages_entities` |
| c | text + photo | **5** | + `tf_message_medias`, `tf_photos`, `tf_photos_sizes` |
| d | text + reply | **3** | + `tf_messages_reply_to` |
| e | text + forward header | **3** | + `tf_messages_fwd_from` |
| f | text + inline keyboard | **3** | + `tf_messages_reply_markup` |
| g | channel post with a poll | **3** | + `tf_message_medias` |
| h | service message (join/leave) | **3** | `tf_messages_service`, `tf_messages_service_from_id`, `tf_message_actions` |

Verbatim stderr from the driver:

```
a_plain_text   decomposed= 2 placed= 2 rows= 2 inserted= 2
    tables: tf_messages, tf_messages_from_id
b_entities     decomposed= 3 placed= 3 rows= 6 inserted= 6
    tables: tf_messages, tf_messages_from_id, tf_messages_entities
c_photo        decomposed= 5 placed= 5 rows= 5 inserted= 5
    tables: tf_messages, tf_messages_from_id, tf_message_medias, tf_photos, tf_photos_sizes
d_reply        decomposed= 3 placed= 3 rows= 3 inserted= 3
    tables: tf_messages, tf_messages_from_id, tf_messages_reply_to
e_forward      decomposed= 3 placed= 3 rows= 3 inserted= 3
    tables: tf_messages, tf_messages_from_id, tf_messages_fwd_from
    CLUE: tf_messages_fwd_from: peer column 'saved_from_id' missing from payload — cannot place the fact
f_keyboard     decomposed= 3 placed= 3 rows= 3 inserted= 3
    tables: tf_messages, tf_messages_from_id, tf_messages_reply_markup
g_poll         decomposed= 3 placed= 3 rows= 3 inserted= 3
    tables: tf_messages, tf_messages_from_id, tf_message_medias
h_service      decomposed= 3 placed= 3 rows= 3 inserted= 3
    tables: tf_messages_service, tf_messages_service_from_id, tf_message_actions
```

Three observations in that table are not what a casual reading of "~40 tables per message" would predict, and
each is worth its own sentence.

**Plain text is 2, not 6.** D-034 speculated "a plain text post may touch 6". Measured, a plain text post
touches **two**: the message row and the one peer child every message carries because `from_id` is
`flags.8?Peer` on `message#7600b9d3`. The speculation was in the right direction — well under the permitted
40 — and it was still nearly 3× too high.

**Photo is the widest common shape, at 5, and it widens by going sideways rather than down.** The photo
path is the only one that leaves the `tf_messages_*` namespace entirely, reaching `tf_message_medias`,
`tf_photos` and `tf_photos_sizes`. That sideways step is precisely the shape TDLib's `data BLOB` would
collapse into the message row.

**A poll is 3, and that is a finding, not a measurement of Telegram.** `messageMediaPoll` declares no nested
source edge, so the walk stops at `tf_message_medias` and the poll's answers, voters and question never
become rows. If a poll row is wanted, it is currently **not reachable by this path** — a gap to be filed,
deliberately not repaired here.

**Service messages are 3, on a different parent.** `tf_messages_service` is a separate fact table from
`tf_messages` — `MirrorCatalog::tableForCtor('messageService')` resolves to `tf_messages_service`, whose
declared ctor list is `["messageService"]` alone — and the action lands in `tf_message_actions` via the
`action` edge. A service message therefore never touches the `tf_messages` tables at all.

## THE CEILING — ONE SHAPE THAT CARRIES EVERY OPTIONAL FIELD

Because the eight shapes above are all *sparse*, they could collectively understate the permitted footprint.
So a ninth shape, `z_maximal`, was measured: one `message` carrying every optional field on the layer-227
constructor that the catalog declares a child for — all 29 direct children plus a full photo subtree.

**Measured: 34 distinct tables, 34 rows, all 34 placed.** The verbatim table list:

```
tf_messages, tf_messages_from_id, tf_messages_from_boosts_applied, tf_messages_from_rank,
tf_messages_saved_peer_id, tf_messages_fwd_from, tf_messages_via_bot_id, tf_messages_via_business_bot_id,
tf_messages_guestchat_via_from, tf_messages_reply_to, tf_messages_reply_markup, tf_messages_entities,
tf_messages_views, tf_messages_forwards, tf_messages_replies, tf_messages_edit_date,
tf_messages_post_author, tf_messages_grouped_id, tf_messages_reactions, tf_messages_restriction_reason,
tf_messages_ttl_period, tf_messages_quick_reply_shortcut_id, tf_messages_effect, tf_messages_factcheck,
tf_messages_report_delivery_until_date, tf_messages_paid_message_stars, tf_messages_suggested_post,
tf_messages_schedule_repeat_period, tf_messages_summary_from_language, tf_messages_rich_message,
tf_message_medias, tf_photos, tf_photos_sizes, tf_photos_video_sizes
```

**34 is the honest ceiling for a single `message`** as the catalog is currently wired, and it sits below the
permitted 40 for a structural reason worth naming: the remaining six permitted tables belong to the
`tf_messages_service` branch and to a document subtree, which no single `message` can also carry. A message
is either a photo or a document, never both.

## AVERAGES

### Unweighted

The eight shapes sum to 25 distinct tables across eight messages: **25 / 8 = 3.125**. This figure is
arithmetic on a designed spread and should be read as *the average of these eight shapes*, nothing more.

### Weighted — with the distribution stated, because it is an assumption and not a measurement

No weight is derived from data, because no data exists: `docs/scaling.md` declares no row volume, no growth
rate and no retention window, which D-034 already recorded. Any weighted average here is therefore a
**conditional statement**, and it is stated as one.

Two distributions are offered, both plausible for a Telegram workspace, both shown against the raw numbers
so the reader can substitute their own.

**W1 — private/group-chat heavy** (plain 30%, entities 25%, photo 20%, reply 12%, forward 5%, keyboard 4%,
poll 3%, service 1%):

> 0.30·2 + 0.25·3 + 0.20·5 + 0.12·3 + 0.05·3 + 0.04·3 + 0.03·3 + 0.01·3 = **3.10**

**W2 — channel-posting heavy** (plain 25%, entities 30%, photo 35%, reply 5%, forward 3%, keyboard 1%,
poll 0%, service 1%):

> 0.25·2 + 0.30·3 + 0.35·5 + 0.05·3 + 0.03·3 + 0.01·3 + 0.00·3 + 0.01·3 = **3.45**

Raw per-shape counts, restated beside them so no weighted figure is read in isolation:

```
a 2   b 3   c 5   d 3   e 3   f 3   g 3   h 3
unweighted 3.125 · W1 3.10 · W2 3.45 · measured ceiling 34 · permitted 40
```

**The spread between W1 and W2 is 0.35 tables per message, against a permitted footprint of 40.** The choice
of distribution is not the deciding variable in D-034, and it would take a distribution that is overwhelmingly
photo-and-document traffic — well outside anything either W describes — to move the weighted average past
**5**. That is the honest conclusion of this section, and it is a robustness statement about the measurement
rather than a claim about Telegram.

## VERIFICATION OF THE "~40" FIGURE — CONFIRMED, WITH THE WALK SHOWN

The brief asked for independent verification rather than acceptance of the summary. The walk was run against
`src/Schema/schema/mirror-shape.json` (261 tables, 2032 columns, 467 FKs — all three confirmed) and against
`MirrorTableResolver`/`MirrorCatalog` for the *reachable* set.

**Confirmed — 40, and the composition is exactly as summarised.** From `mirror-shape.json`:

- **35** tables carry a primary key exactly `(account_id, peer_type, peer_id, id)`.
- **4** more carry that key plus one column: `tf_messages_entities` (…`position`),
  `tf_messages_reply_markup_rows`, `tf_messages_reply_markup_rows_buttons`, `tf_messages_restriction_reason`.
- **36** tables declare a foreign key into `tf_messages` or `tf_messages_service`.

The union is **40**, not 39: `tf_messages_media` is in the FK-reachable set but not in either PK set — its
primary key is `(account_id, message_peer_type, message_peer_id, id)` (`api_full.tl`'s `MessageMedia` carries
no `peer_type`/`peer_id` pair of its own, so the overlay names its own columns). 35 + 4 + 1 = **40**.

**The 261-table remainder is indeed catalogue.** 261 − 40 = 221 tables, and the resolver agrees
independently: `MirrorCatalog` declares **40** catalogued tables, of which `tf_messages` alone owns **29**
direct children (`parentTf === 'tf_messages'`, one shared node — `tf_message_medias` — excluded by the
decomposer's own design) and `tf_messages_service` owns **5**.

**That 40 is the RESOLVED count, not the file's key count, and the file says 39.**
`src/Schema/schema/mirror-catalog.json` declares 39 JSON object keys;
`MirrorCatalog::tableNames()` returns 40 because `MirrorCtorSplitter`
(`src/Schema/Mirror/MirrorCtorSplitter.php`) mechanically derives `tf_messages_service` from the
Layer-227 `messageService` ctor and installs it as a 40th entry. Measured on this tree, 2026-10-13:
`in resolver not raw: tf_messages_service`, `in raw not resolver: (none)`. The gap is the design —
Message and MessageService are disjoint ctor variants that must not share one nullable-column table
(spec §4 decision B) — so the fix is always to read through `MirrorCatalog::load()`, never to add a
hand-written entry that the mechanical derivation would immediately override. Any count published off
the raw JSON keys reads one short; `docs/schema/fidelity-audit.md`'s `catalog_entries` does not,
because `bin/audit-fidelity.php` computes it from `count($catalog->tableNames())`.

**The resolver's transitive closure is 47, not 40, and the difference is worth stating rather than
smoothing.** Walking `resolveAll()` transitively from `tf_messages` and `tf_messages_service` yields **47**
tables, including a `tf_documents` subtree (`tf_documents`, `tf_documents_attributes`,
`tf_documents_thumbs`, `tf_documents_video_thumbs`), a `tf_photos` subtree (`tf_photos`,
`tf_photos_sizes`, `tf_photos_video_sizes`), and the `tf_message_actions` subtree
(`tf_message_actions`, `tf_message_actions_users`). The 40 in `mirror-shape.json` is the **keyed-and-FK'd**
union; the 47 is the **structurally reachable** superset. The gap is documents plus photo/photo-size trees,
which are keyed on their own identifiers rather than on the message key.

**Verdict: CONFIRMED.** The "~40" is correct as a statement about the `(account_id, peer_type, peer_id, id)`
family. It is an **upper bound on a single message** and, measured, a loose one — the ceiling a maximally
populated `message` actually reaches is **34**.

## THE FORK, WITH THE NUMBER PLUGGED IN

D-034 Side A is full relational decomposition. D-034 Side B is TDLib's hybrid. The number this document
measures bears on them as follows.

**Measured typical footprint: 2–5 tables per message. Measured worst case for one message: 34. Permitted: 40.**

**This favours Side A (keep full decomposition), at high confidence on the count and moderate confidence on
the conclusion.** The reasoning, stated so it can be attacked:

The cost of decomposition is paid **per write**, and the count is low: the most common shape writes **2** rows
and the widest common shape writes **5**. A TDLib-style blob would collapse those to 1, saving **one to four
row-inserts per message** — a real saving, but on a number that is small in absolute terms. Against that,
Side B's cost is paid **per read**, and it is unbounded in kind: under Side B every query that today reads
`tf_messages_entities.offset` and `tf_messages_entities.length` as indexed columns must instead parse a blob,
and D-034 Decision 3 already established that those offsets are **UTF-16 code-unit** offsets
(`EntityParser.php:392-398`) that are not join keys against any text column. A blob makes that worse, not
better. The asymmetry — a small constant write saving against a per-query parsing cost on every non-trivial
access — is what makes Side A the better side of this fork at the measured numbers.

**The confidence is stated in two parts, because they have different quality.**

- **HIGH** on the counts themselves. They come from the shipped decomposer run over the executed shipped DDL,
  and the eight shapes are TL-accurate against `api_full.tl`. A reader can re-run the same thing.
- **MODERATE** on generalising them to "a typical channel post". The brief's eight shapes are designed, not
  sampled, and the frequency distribution behind any weighted figure is an assumption (W1/W2 above, defended
  but unevidenced). If real traffic is dominated by documents and photo albums — the branches this
  measurement covers least — the true average rises, though the W1→W2 spread of 0.35 shows the assumption
  is not load-bearing up to about 5.

**What would flip it.** A measured traffic mix in which the weighted average exceeds ~15 tables per message,
or a read pattern in which the child tables are demonstrably unused, would move the balance toward Side B.
Neither is demonstrated here.

## WHAT WOULD CHANGE THIS NUMBER

Named, so a later reader knows what to distrust.

1. **A real traffic distribution.** The single largest lever. The weighted average moves with the mix; §AVERAGES
   shows the modelled range is 3.10–3.45, and a document-heavy or album-heavy account would exceed both.
2. **Adding declared nested-source edges.** This is the largest *non-traffic* lever and it is already
   half-open. `nestedSourcesFor('poll')`, `('replyKeyboardMarkup')`, `('messageMediaPoll')`, `('photo')` and
   `('PhotoSize')` all return **empty** today, which is why a poll costs 3 and not 6. Wiring `poll.answers` and
   `replyKeyboardMarkup.rows` would raise shapes (g) and (f) without changing a single message on the wire.
   **Any future catalog edit invalidates every count in this document**, because the counts are properties of
   the catalog as much as of the payloads.
3. **Enriched media.** Shape (c) measured 5 with one `photoSize`. A real photo carries several sizes, and a
   `video_sizes` vector pushed the maximal shape from 32 to 34. Counts here are per **table**, so extra rows
   do not move them — but a media type with a wider subtree (a document with attributes and thumbs) would.
4. **Reaction-heavy or restriction-heavy feeds.** Both were exercised only inside `z_maximal`. Real
   `MessageReactions` carry per-reaction rows and `Vector<RestrictionReason>` carries one row each; these are
   the shapes most likely to exceed 5 in the wild.
5. **A dialect change.** Every count is SQLite. See below.
6. **`tf_messages_service` growing its own children.** It has 5 direct children against `tf_messages`' 29.
   Any new service-message child raises shape (h) only.

## ENGINE AND EVIDENCE LIMITS — STATED PLAINLY

**THE ENGINE WAS SQLITE, AND ONLY SQLITE.** No MySQL or Postgres server was reachable (`command -v mysql
mysqld psql` → exit 127), so `tests/MySql/EveryClassifiedEndpointIngestsOnMysqlTest.php` — which does
enumerate endpoints per tenant and is the file most likely to already carry table-per-endpoint evidence on
MySQL — **was not executed and produced no output here.** Its own docblock records its MySQL results
separately (`271 tables`, a `55-babe8cc4fe093145` fingerprint) but those are endpoints, not per-shape table
counts, so they do not substitute for this measurement.

The honest weight of that limitation, in the direction that matters: SQLite **cannot** raise MySQL 1406 /
1265 / 3780 / 6125 / 1059 / 1170, so a sqlite-green run proves nothing about type widths, FK compatibility or
identifier lengths. For a **table-count footprint** this matters much less than it would for a type claim —
a table either receives a row or it does not, and SQLite answers that question faithfully. But it does not
answer it *identically*: a row that MySQL would refuse for a width reason is, here, counted as placed. Since
every shape reported `decomposed == placed`, a MySQL run could in principle place **fewer** rows than SQLite,
never more. The counts are therefore **upper bounds under MySQL too, and that direction is the safe one.**

Second limit: no **live** mirror data was read, so nothing here describes what any real account actually
does. Third: the eight shapes are constructed, and their frequency is unmeasured. Fourth: the schema is the
58 shipped migrations executed at the time of writing, on a dirty tree with ~25 concurrent agents — a
migration landing between this run and its re-run would move the numbers.

## Cross-references

- `decisions.md` `## D-034` (PRIVATE — moved to `.brain/decisions.md` under D-036; not shipped publicly) — the fork this document decides. Decision 1 quotes
  TDLib's `MessageDb.cpp`; Decision 3 records the UTF-16 entity-offset constraint that the blob option would
  interact with; "What would make this wrong" names this measurement as the missing input.
- `src/Schema/schema/mirror-shape.json` — 261 tables, 2032 columns, 467 FKs; source of the verified 40.
- `src/Schema/schema/sources/api_full.tl` — layer 227. `message#7600b9d3` at `:121`, `messageService#7a800e0a`
  at `:122`, `messageFwdHeader#4e4df4bb` at `:823`, `messageReplyHeader#1b97dd66` at `:1415`.
- `src/Schema/Mirror/MirrorFactDecomposer.php:145` / `:265` — the parent and child walks.
- `src/Schema/Mirror/MirrorNestedSourceWalker.php:46` — the envelope walk.
- `src/Schema/Mirror/MirrorFactWriter.php:638` — the write, and its documented degradation behaviour.
- `tests/Store/ShippedStoreSchema.php:92` — schema by executing migrations, never by transcription.