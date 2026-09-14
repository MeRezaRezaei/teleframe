# NF5 mirror + ingest truth model — plan (verbatim-driven)

**Date:** 2026-09-14 · **Source of truth:** `docs/superpowers/specs/2026-09-14-teleframe-nf5-mirror-ingest-verbatim.md`
(owner's exact words; when a decision is unclear, return to that document and re-read it).

## The truth model (from the verbatim)

1. **The mirror is the core.** `tf_*` tables = the Telegram NF5 relational mirror (facts
   Telegram persists). Everything else we build links its data to this core and nothing
   else defines truth.
2. **Telegram updates are one half of the absolute truth.** Whatever arrives as a Telegram
   update has been processed on Telegram's side; it is a fact and **must** be stored — never
   dropped, never edited. The other half is *our* app watching for specific updates to act
   on (driven by settings stored in our DB + a hot-reload channel).
3. **Full-schema chunk sync validates the mirror.** At session register, ask Telegram to send
   all updates as a chunk (`updates.getState` → `updates.getDifference` /
   `updates.getChannelDifference`, filled by `pts/qts/seq` gap recovery). If we can ingest the
   big update, the schema is correct. **Every FK failure is a clue pointing at a wrong ingest
   path** — fix the path, re-ingest, repeat until full integrity.
4. **Telegram-reliance scopes must be spotted and mirrored as-is.** Auto-increment is the
   obvious one: Telegram's DB already assigns IDs, so the mirror never auto-increments — it
   stores Telegram's values and keys off them. Spot these scopes; encode them as invariants.
5. **NF5 is for structural validity.** The relational shape lets us connect Telegram facts to
   our data with real FKs. Once integrity is reached, the exact dependency graph lets us derive
   the **tiny schema** (dev-friendly models/DTOs) cached in Redis — the mirror stays relational
   storage; the tiny shape is the dev-facing read surface.
6. **Two Redis.** (a) default: observe updates → push to Redis → an observer keeps the
   relational DB updated → data comes back as Eloquent models; (b) hot-reload settings: DB
   settings changed → observer emits the change as an event to the defined event path, and
   can tell the ingester in real time what to listen to / ignore.
7. **Loop prevention is the payoff.** Telegram sends an update inside a channel; that update
   becomes a new input to our app. Without knowing which updates to act on vs. store-only, we
   loop until flood-wait. Notify/log channels must store the final truth WITHOUT re-firing as
   app events. Mechanism (Telegram's own docs, `core.telegram.org/api/updates`): apply only
   when `local_pts + pts_count === pts` (ignore `>`, gap-recover `<`); use the `out` flag and
   `updateMessageID`/`random_id` to recognize our own sends and not re-act on them.
8. **Fact classification.** Two groups: (a) facts from other people — out of our control,
   store only; (b) facts Telegram produced as a reflection of *our* behavior (we sent the
   message) — NOT inputs by default, unless a chain-effect flow (e.g. backup) needs them.
   Also: sometimes the fact is *absence in time* (backup did not arrive) — logic independent
   of Telegram, but only makes sense against the core's integrity.

## Current repo state (verified 2026-09-14)

- Full mirror committed + deterministic: 37 parents / 400 tables / 25 migrations /
  400 models / 400 factories (additive stage in `SchemaRegenerator`, manifest `mirror` section).
- Mirror invariants already hold in generated artifacts: `account_id` first; zero nullable;
  PK `(account_id, key[, position])`; **no auto-increment anywhere**; `TfMirrorModel`
  `incrementing=false` (IDs come from Telegram).
- Schema exposes the chunk-sync methods: `updates.getState#edd4882a`,
  `updates.getDifference#19c2f763`, `updates.getChannelDifference#3173d78`.
- Legacy JSONB ingest (`UpdateIngestor` writing `tl_data` into tf_* tables) is still the
  shipped consumer — it is the P3 rewrite target (not yet started; net-new design).
- `EchoEliminator` exists in the handler layer (send-time PSR-16 registry, `onOwn` escape
  hatch) — the loop-prevention seam is present; needs wiring into the truth model.

## The loop (per the verbatim: plan → do → return to verbatim → replan)

Each cycle: pick the smallest increment the verbatim can decide; do it green; commit;
re-read the verbatim; if it still decides the next step, plan it; else ask the owner.

### Cycle 1 — lock telegram-reliance invariants (DONE next)
The verbatim says "first you should spot these things and making sure you make a mirror
about them." Spot: **auto-increment / ID assignment is Telegram's job**. Encode as tests:
- mirror migrations contain no `autoIncrement` / `increments`;
- every mirror table PK starts with `account_id` and continues with Telegram-supplied keys;
- `TfMirrorModel` is `incrementing=false`, `keyType=int`, `primaryKey='id'`.
Gate: `vendor/bin/phpunit tests/Schema/Mirror` + full suite + phpstan + standalone-smoke.

### Cycle 2 — write-path truth store (planned after cycle 1)
Replace the legacy JSONB ingest with a mirror write-path: one TL update → parent row +
child rows, composite PK, no JSONB; FK failures logged as ingest-path clues (the verbatim's
"whatever foreign key fails gives us a clue"). This is the net-new design; requires the
mirror table→field mapping (already in `MirrorTableResolver`) and an update→rows decomposer.

### Cycle 3 — chunk sync (full-update ingest gate)
Wire `updates.getState` at session register + gap recovery via `getDifference`/
`getChannelDifference`; ingest the full chunk against the mirror; FK-failure report = schema
completeness signal.

### Cycle 4 — two-redis topology + loop prevention
Default redis observer keeps the relational DB updated; hot-reload settings redis emits
settings-change events and live listen/ignore control; notify/log channels store-only path
via the `out` flag + `updateMessageID` recognition (loop prevention), reusing `EchoEliminator`.

### Later — tiny schema projection
Derive dev-facing models/DTOs (the "tiny not NF5 structure, inside Redis cache") from the
integrity-complete mirror: exact dependency graph → tiny schema.