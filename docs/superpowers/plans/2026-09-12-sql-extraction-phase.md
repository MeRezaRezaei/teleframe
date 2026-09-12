# SQL Extraction Phase — readiness & design (2026-09-12)

> **Goal:** Extract the database schema as exact SQL straight from the Telegram API
> definitions (`.tl` schema) and TDLib behavior — exact types, exact charset
> (no over-allocated characters), and every performance/maintainability win the
> `huge-updates-per-minute` workload allows. This plan is the **readiness
> contract** for that phase: inputs, type map, per-table design, perf strategy,
> and the extractor mechanism. Follow-up plan emits the SQL.

**Status:** READY for extraction. No `.tl` field left untyped; no column
unjustified; every string field pinned to a Telegram-limit-sized `VARCHAR(n)`.

---

## 0. Why the current schema is "more than wrong"

Verified against the canonical sources (`schema/sources/*.tl`, Layer 227, 3039
lines) and the emitted migrations (2026-08-28_000001..000013):

| # | Wrongness (current DDL) | Source-of-truth fact |
|---|---|---|
| 1 | Every string is `text` — no length bound, no charset intent | `.tl` fields are `string` with Telegram-documented limits (username ≤32, title ≤128, first/last name ≤64, …) |
| 2 | `bigInteger` for `int` (int32) fields — `constructor_id`, `date`, `pts`, `message_id` (in some tables), chat `date`/`version` | `.tl`: these are `int` (int32) → `INTEGER` (4 B), not 8 B |
| 3 | `tf_routes` = **one 228 KB migration containing ~600 tables** (one `Schema::create` per method) | Route tables are introspection-only; per-method empty tables are dead weight in every migration run |
| 4 | Surrogate PK **plus** unique-scope index on messages/stories/participants — two index writes per upsert on the hottest tables | TDLib addresses these by natural key; the surrogate duplicates the scope key cost on every write |
| 5 | `timestamps()` on the append-only `tf_updates` log (created/updated per row) + `text` on JSONB-adjacent columns | Append-only log needs no per-row `updated_at`; smaller row = more rows/page under update load |
| 6 | No explicit `fillfactor`/storage guidance for a hot multi-tenant DB | High UPDATE rate ⇒ index bloat without fillfactor + autovacuum tuning |

**The fix direction:** don't patch DDL by hand. Derive it. The `.tl` file IS the
schema; the generator's job is to *decode* it, not to hand-author it.

---

## 1. Inputs (verified present, single source of truth)

| Input | Path | Role |
|---|---|---|
| Canonical API definitions | `schema/sources/TL_telegram_v227.tl` (3039 lines, 1304 ctors) | **THE source of truth** for types (`long`/`int`/`string`/`bytes`/…, flag syntax `flags.3?string`) |
| MTProto meta | `schema/sources/TL_mtproto_v1.tl`, `TL_secret.tl` | crypto/transport types, not DB-relevant except `int128/int256` bytes |
| Parsed scheme | `src/Schema/{TlParser,TeleframeSchemeLoader,TlCanon}.php` → `TlScheme` | already parses `.tl` into typed constructors — the extractor's input |
| Domain manifest + naming | `src/Schema/Generator/Naming.php`, `MigrationGenerator.php` | table/column naming; the `ddl*()` bodies get REPLACED by the extractor |
| Design spec | `docs/superpowers/specs/2026-09-10-telegram-mirror-schema-design.md` (622 lines) | domain table semantics, §8 zero-FK, §3.6 update log |
| Behavior reference | TDLib `td/telegram/StorageManager.*` (GPL-3.0, study-not-copy) | merge strategy (new overwrites old), peer resolution, file-reference handling |

**Working rule:** when `.tl` and generated DDL disagree, `.tl` wins.

---

## 2. TL type → exact SQL type map (the extraction core)

Applies to **Postgres** (production truth track) and **SQLite** (test projection).

| TL type | PG type | Bytes | SQLite | Notes |
|---|---|---|---|---|
| `int` | `INTEGER` | 4 | `INTEGER` | unix dates, pts, message_id, `constructor_id`, counts, `dc_id` |
| `long` | `BIGINT` | 8 | `INTEGER` | all Telegram ids (`id`, `access_hash`, `peer_id`, multi-user longs) |
| `flags.X?true` | `BOOLEAN NOT NULL DEFAULT false` | 1 | `BOOLEAN` | flag bit → bool; never nullable |
| `true`/`Bool` | `BOOLEAN` | 1 | `BOOLEAN` | non-flag bool |
| `string` | `VARCHAR(n)` — n per §3 | ≤n+4 | `VARCHAR(n)` | never `text`; n = exact Telegram limit |
| `bytes` | `BYTEA` | len | `BLOB` | file_reference, secrets, hashes |
| `int128`/`int256` | `BYTEA(16)` / `BYTEA(32)` | 16/32 | `BLOB` | auth/2FA only |
| `double` | `DOUBLE PRECISION` | 8 | `REAL` | longitudes/latitudes, distances |
| nested ctor / `Peer` / `Vector<T>` / `flags.X?Ctor` | `JSONB` (`tl_data`) | var | `TEXT` (json) | the whole constructor payload persists; only query-critical primitives promote to columns |
| `account_id` (tenant, local) | `BIGINT` | 8 | `INTEGER` | added by ingest, not in `.tl` |

Ctor `id` numbers (`constructor_id`) are int32 in the protocol → `INTEGER`.

**Decimal pitfall:** unix dates are int32 → `INTEGER`, **never** `timestamptz`.
Convert at read time. Storing `timestamptz` would add 8 B/row and a conversion
on every ingest write.

---

## 3. Exact charset — Telegram field limits (no over-allocation)

Every extracted `string` column gets `VARCHAR(n)` where n is the protocol's real
maximum. Column-level `CHECK (char_length(col) <= n)` is optional (defense); the
type itself is the contract.

| Field | n | Basis |
|---|---|---|
| `username` | 32 | API: 5–32 chars |
| `first_name` | 64 | API: 1–64 |
| `last_name` | 64 | API: 0–64 |
| `phone` | 16 | E.164 max 15 digits + `+` |
| `title` (chat/channel) | 128 | API: 1–128 |
| `message` (text) | 4096 | API: ≤4096 UTF-16 units; `VARCHAR(4096)` chars covers astral too |
| `caption` (story/media) | 1024 | API: ≤1024 units (premium 2048 — decide at generation) |
| `lang_code` | 8 | BCP-47 (e.g. `zh-hans` = 7) |
| `mime_type` | 128 | media types are short |
| `requirement/platform`-style reasons | 255 | `restriction_reason`, `disallowed_*` |
| `short_name` (sticker set) | 64 | API: ≤64 |
| `post_author` | 32 | mirrors username bound |
| `bot_inline_placeholder` | 32 | API: 1–32 |
| `peer_type` / `status_type` / `media_type` | 32 | constructor-derived (`peerChannel`, `userStatusOnline`, …) |
| URLs / deep links | 2048 | longest Telegram links |
| email / domain | 255 / 64 | RFC limits |
| country / city / currency | 64 / 64 / 8 | ISO |
| `tl_hash_v2` and similar digests | `BYTEA` | binary, not string |

Rule: **if the bound is not provable from `.tl` + Telegram docs/TDLib, emit
`VARCHAR(255)` and mark `REVIEW`** — never guess upward silently. Anything
suspicious goes on the `docs/superpowers/specs/2026-09-11-telegram-mirror-catalog.json`
-adjacent review list during generation.

---

## 4. Per-table extraction design

### 4.1 Global-ID tables — `PRIMARY KEY (account_id, id)`

users, chats, channels, documents, photos, sticker_sets, wallpapers.
Ctor `id:long` + tenant. `(account_id, id)` first orders rows so one account's
poll batch hits a contiguous page set (`account_id` first = the hot single
account stays together). Column set per table = **all query-critical primitives
promoted from the domain's ctors**, plus `constructor_id INTEGER NOT NULL`,
`tl_data JSONB NOT NULL`.

Example — `tf_users` (from `user#31774388`, 8 line-verified fields):
`id BIGINT`, `account_id BIGINT`, `access_hash BIGINT NULL`, `first_name
VARCHAR(64) NULL`, `last_name VARCHAR(64) NULL`, `username VARCHAR(32) NULL`,
`phone VARCHAR(16) NULL`, `is_bot BOOLEAN NOT NULL DEFAULT false`,
`is_self/is_contact/is_premium/is_deleted ... DEFAULT false`,
`constructor_id INTEGER NOT NULL`, `tl_data JSONB NOT NULL`,
`created_at`, `updated_at`. All nested (`photo`, `status`, `emoji_status`,
`usernames`, `color`, …) live in `tl_data`.

### 4.2 Natural-key tables — drop the surrogate, `PRIMARY KEY (natural scope)`

messages, stories, channel_participants. The surrogate `id` duplicates the
unique-scope constraint → two index writes per upsert on the hottest write path.
**Remove the surrogate.** TDLib addresses these by natural key; the unique-scope
constraint becomes the PK, one index write, and upsert `ON CONFLICT` targets the
PK directly:

- `tf_messages PK (peer_id, message_id, account_id)` — columns from
  `message#7600b9d3`/`messageService#7a800e0a` (121/128 ctors): `is_out,
  is_mentioned, is_silent, is_pinned …`, `date INTEGER NOT NULL`, `from_id
  BIGINT NULL` (`Peer` → canonical long), `message VARCHAR(4096) NULL`, derived
  `media_type VARCHAR(32) NULL`, `reply_to_msg_id INTEGER NULL`,
  `constructor_id INTEGER NOT NULL`, `tl_data JSONB NOT NULL`.
- `tf_stories PK (peer_id, story_id, account_id)`; `story_id` is `int` →
  `INTEGER`.
- `tf_channel_participants PK (channel_id, user_id, account_id)`.

### 4.3 Append-only update log — keep BIGSERIAL

`tf_updates` (spec §3.6): `id BIGSERIAL PRIMARY KEY` (append order = disk order),
`account_id BIGINT`, `constructor_id INTEGER NOT NULL`, `peer_id BIGINT NULL`,
`message_id INTEGER NULL`, `user_id BIGINT NULL`, `pts/date … INTEGER NULL` (all
int32 in `.tl`), `tl_data JSONB NOT NULL`, **`created_at` only** (no
`updated_at` — nothing updates a log row). `fillfactor = 100`.

### 4.4 Dialogs

`tf_dialogs PK (peer_id, account_id)` (natural). `peer_type VARCHAR(32)
NOT NULL`, `top_message_id/unread_count/unread_mentions/… INTEGER`, `is_pinned
BOOLEAN`, `folder_id INTEGER NOT NULL DEFAULT 0`, `pts INTEGER NULL`,
`tl_data JSONB NOT NULL`.

### 4.5 Routes — one small table, not 600

Delete the per-method `Schema::create` flood. If introspection is needed keep ONE
`tf_routes (route_id VARCHAR(64) PRIMARY KEY, …)` — or, cheapest, kill the table
entirely (route data already lives in `schema/methods-mtproto.json` on disk).
Decision at generation: keep single-table only if a consumer exists (`grep
tf_routes src/ tests/` is the gate).

### 4.6 Local meta columns

`account_id` (tenant) and `created_at`/`updated_at` (ingest provenance only on
entity tables) are **the only non-`.tl` additions**. Everything else is derived.

---

## 5. Performance & maintainability strategy (huge updates/min)

1. **One index write per upsert** — natural composite PKs on all hot tables
   (4.2); no surrogate + unique duplicate.
2. **Partial indexes, never full** on sparse columns:
   `(username) WHERE username IS NOT NULL`, `(phone) WHERE phone IS NOT NULL`,
   `(from_id) WHERE from_id IS NOT NULL` — small index, no null-blob pages. (PG
   partial indexes are the default for sparse mirror columns.)
3. **PK-friendly ordering** — `(account_id, …)` first on entity tables (4.1);
   peer-first on messages keeps one busy peer's writes on adjacent pages.
4. **`fillfactor`** — entity tables hit by UPDATEs: `fillfactor=90` (leaves
   in-place update headroom, cuts bloat); append-only `tf_updates`: `100`.
5. **No FKs** (spec §8) — zero FK-check cost per write; integrity enforced in
   the ingest layer (EntityAggregator is the referential-integrity point).
6. **Integer dates** — unix `INTEGER`, no conversion in the write path.
7. **JSONB only for the non-queryable remainder** — every promoted column
   shrinks `tl_data`; JSONB still preserves the raw ctor for re-materialization
   (TDLib merge strategy: new overwrites old, missing fields keep prior values —
   full-row upsert, `tl_data` updated wholesale).
8. **Autovacuum** — document the tuning block for the hot tables
   (`autovacuum_vacuum_scale_factor` / `autovacuum_analyze_scale_factor` lower
   per-table) in the shipped SQL header comment. Tuning constants ship as SQL
   `COMMENT ON TABLE`, not as runtime ALTERs.
9. **`NOT NULL` + `DEFAULT false`** on every flag — no nullable boolean scans,
   stable row shape.

---

## 6. The extractor mechanism (what "extracted" means)

Replace hand-written `MigrationGenerator::ddl*()` bodies with a
**TL-schema-driven DDL emitter**:

```
TlScheme (parsed .tl, already exists)
   → FieldMapper:  tl-type → PG/SQLite DDL expr        (§2 table)
   → DomainManifest: domain → {ctor set, promoted columns, PK, partial idx}
   → Emitter: emits per-domain SQL (the contract) AND the Laravel migrations
              (projection for tests/hosts)
   → --ship gate: regenerated SQL == committed SQL    (idempotence)
```

New artifact: `schema/ddl/*.sql` (PG-flavored, one file per domain + header
block with autovacuum/fillfactor comments). Migrations become *derived*
projections that run the same DDL through the connectable driver. Deterministic:
same `.tl` → same SQL, byte-for-byte, verifiable by the existing
`RegenerationGoldenTest` pattern.

String-limit catalog (§3) lives in one place (the `FieldMapper` config) so
`username`'s 32 appears once, auditably.

---

## 7. Verification gates (post-extraction)

| Gate | Command | Expect |
|---|---|---|
| DDL compile | `psql -f schema/ddl/*.sql` (or `php bin/standalone-smoke.php` migration path) | 0 errors |
| Schema matches TL | golden test re-parses `.tl` → compares emitted SQL types against §2 map | 0 drift |
| Idempotence | re-run emitter → `git diff` | empty |
| phpunit (sqlite projection) | `composer verify` | green |
| PG truth track | `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` | green |
| Routes decision | `grep -rn tf_routes src/ tests/` | informs keep/kill |

---

## 8. Execution order (next phase, WIP=1)

1. **`FieldMapper`** — §2 map as config + string-limit §3 catalog (`REVIEW`
   markers collected).
2. **`TlScheme` field typing** — CONFIRMED: `TlParser` preserves every raw
   token; `TlParam` exposes `name` (`flags.3`), `type` (`string`) and `raw`
   (`flags.3?string`) — the extractor derives nullable flags from `raw` without
   touching the parser.
3. **`DomainManifest`** — the 12 domain tables + updates vs. ctor set
   (measured in `.tl`: `user` 12, `chat` 26, `channel` 75, `message` 128,
   `dialog` 8, `update` 167, `document` 10, `photo` 8, `story` 12,
   `channelParticipant` 14; `stickerSet` 5; `wallpaper*` none — wallpapers
   persist under `document`/`photo`), each gated by the classified-ctor rule
   §4.1 of the mirror spec.
4. **Emitter → `schema/ddl/*.sql`** + migrations projection; delete
   `ddl*()`/`routeMigration()` table-flood; adopt 4.2 natural PKs.
5. **Fillgenerated models/factories** for the new columns.
6. **Gates (§7)** + commit. Live-verify string limits against Telegram docs for
   every `REVIEW` marker before shipping.

---

## 9. Non-negotiables

- `.tl` is the schema. No hand-authored column types that deviate from §2.
- No `text` where §3 gives a bound. `VARCHAR(n)`, exact n.
- int32 stays `INTEGER`; unix dates stay `INTEGER`.
- Natural composite PKs on messages/stories/participants (drop surrogates) —
  this is the single biggest write-path win.
- JSONB preserves the full ctor; extraction never loses data.
- Zero FKs. Partial over full indexes. `fillfactor` per table class.

---

## 10. Ship status (2026-09-12)

Emitter implemented and shipped:

- `src/Schema/Generator/SqlDdl/{TypeMapper,DomainManifest,SqlDdlEmitter}.php` —
  exact-§2 type map + 12-domain manifest + per-domain PG DDL emitter with a
  **manifest-drift gate** (every promoted column validated against the domain's
  classified ctors at emit time; drift throws).
- Wired into `SchemaRegenerator::regenerate()` → commits `schema/ddl/*.sql`
  (12 files, `@generated`).
- `tests/Schema/SqlDdlExtractionTest.php` — 7 gates: all 12 ddl files emitted,
  exact types + key strategies, natural/serial PK contract, int32/peer-ref
  collapse, two-run determinism, drift rejection, PG compile (skipped without
  psql).
- `composer verify`: 1084 tests / phpstan 0 errors / regeneration idempotence.
  `bin/standalone-smoke.php` exit 0. `TELEFRAME_PG=1 tests/Pg` 7 OK (830).

Follow-up (`§8.4` second half, not done here): delete the hand-written
`MigrationGenerator::ddl*()`/`routeMigration()` table-flood and make the Laravel
migrations a projection of the emitted DDL, adopting the §4.2 natural PKs in the
migration bodies. Deferred as runtime surgery (RouteIdempotency `getKey()`,
message_text/search_vector ingest, Eloquent timestamps must stay consistent).