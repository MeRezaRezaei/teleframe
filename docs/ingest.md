> **Merged from teleclient (archived).** This package (teleframe) absorbed the teleclient
> consumer-capability layer in the Phase 2 module merge. Namespace is
> `MeRezaRezaei\Teleframe\*`; the wire keys (`tg:*`, consumer group `teleclient`)
> are unchanged and move with the data.
><br>

# Ingest — raw TL payloads into the P1 truth

The ingest surface turns raw teleproto arrays (snake keys, `_` constructor
name, raw flag ints) into the P1 Postgres truth — anchor / instance / child
rows — idempotently and tenant-scoped, with Laravel events out. Pure
transform + storage: no fetching, no handler logic.

## API

Resolve the wrapper from the container (singleton, bound by the service
provider):

```php
use MeRezaRezaei\Teleframe\Teleclient;

$client = app(Teleclient::class);

// 1. Updates (updateNewMessage, updates containers, ...) — ALWAYS become
//    instances; they never touch routes.
$root = $client->ingest($update, $accountId);

// 2. Method responses — route-deduped (see below); returns the stored
//    instance when the route was already answered.
$messages = $client->ingestResponse('messages.getHistory', $params, $response, $accountId);

// 3. Entity aggregation — anchor with its CURRENT instance loaded as the
//    `currentInstance` relation; null when the tenant never saw the entity
//    or its current instance is deleted.
$user = $client->user($accountId, 501558149);
$user?->currentInstance->first_name; // fields readable through the instance
```

The underlying classes are singletons too (`UpdateIngestor`,
`EntityAggregator`) and can be used directly. `EntityAggregator` also offers
`chat()` / `channel()` — channels are Chat-namespace anchors in TL truth
(`channel` and `channelForbidden` construct `Chat`), so both spellings
resolve the same rows, `channel()` being the intent-revealing one.

`UpdateIngestor::ingest()` returns the root constructor's instance model
after the write transaction commits; nested nodes, refs and vectors land in
their own tables (bottom-up write order satisfies immediate FKs).

Sidecar entities: schema v227 `Peer` fields carry bare ids only — the
`user`/`chat`/`channel` objects themselves arrive via the difference
vectors `updates.getDifference` delivers alongside an update
(`other_updates`, `new_messages`, and the `users`/`chats` vectors of
method responses). `ingest()` handles them as delivered: the sidecar
payloads create the entity anchors, while peers keep only their id
references.

## Tenancy model

Every anchor row carries `account_id`; **every ingest call requires it** —
there is no global mode. Identities (a telegram id) resolve per tenant:
ingesting the same user under account 7 and account 8 produces two anchors,
two instance rows, two child-row sets — no cross-tenant reads are possible
through the ingest or aggregation surfaces (lookups pin the anchor through
its `account_id`; route ids are namespaced by account).

## Concurrency

Two workers ingesting payloads that resolve the same (account, identity)
concurrently could both miss the existing-anchor lookup and each mint an
anchor — the identity value lives in per-constructor instance tables,
so no single DB unique constraint can back the anchor row itself.
`Ingest\IdentityLock` serializes resolution per (account, identity) key
in two layers:

- **in-process**: a static depth-counted key map. PHP workers are
  single-threaded, so the map never blocks — it exists to make nested
  (reentrant) acquires of the SAME key legal: a region re-entered inside
  one execution (`ingestResponse` → `ingest`, or two nodes of one
  payload carrying the same identity) must not wedge on its own key.
- **cross-worker (PostgreSQL)**: a transaction-scoped
  `pg_advisory_xact_lock(hashtext(key))` taken with raw SQL on the
  caller's connection INSIDE the caller's transaction — it
  auto-releases at commit or rollback, so there is no release path to
  forget and no orphaned lock after a crash. Advisory locks are
  reentrant per session, so nested acquires within one transaction are
  safe too.

sqlite (single-writer) and mysql (no advisory locks) are a documented
no-op: callers there rely on the anchor discriminator updates and
content aggregation for eventual convergence.

## Events

`MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored` fires after the
root transaction commits, carrying the committed root instance model and
the account id. It fires **once per actual write pass** — a response that
dedup-skips (route seen) does not re-fire it; re-ingesting an identical
update still runs the ingest pass and fires again (the pass is what's
observed, the rows stay byte-stable).

## Route semantics (method responses)

`ingestResponse(method, params, response, accountId)` decides per payload:

1. **Update-kind payloads** (top constructor prefixed `update`/`updates`)
   branch FIRST and always become instances — even for methods that have a
   route table (e.g. `updates.getDifference` sideband updates).
2. **Methods with a generated route table** (stable single return type —
   the generator skips generic/vector returns such as `users.getUsers`):
   the route key is a deterministic sorted-JSON of the params, namespaced
   by method and account. Unseen → ingest + mark (the route row PK points
   at the stored response instance). Seen → return the stored instance, no
   writes, no event.
3. **Methods without a route table** (or whose route tables are not
   migrated) ingest unconditionally.

Getters benefit most (`messages.getHistory`, `users.getFullUser`,
`channels.getFullChannel`, ...): identical requests dedup naturally. If the
same byte-identical response is ever reached via two different param sets,
the shared instance is stored once and only the first route is recorded.

## Idempotency guarantees

- **Update re-ingest is a no-op**: identity-bearing constructors anchor per
  (tenant, telegram id); identity-less constructors (entities, paramless
  shapes, update roots) aggregate by content — re-ingesting an identical
  payload leaves every row, uuid and child-row link byte-stable. Absent
  conditional columns are not cleared (fill-only upsert).
- **Response route dedup**: a seen (method, params, account) combination
  resolves to the stored instance — row counts and route rows stay stable.
- **Anchor families**: a constructor transition (user → userEmpty for the
  same telegram id) reuses the anchor; the anchor discriminator follows
  the current constructor, and both instance rows survive (the aggregator
  resolves the current one).
- `EntityAggregator` resolves the **current** instance: the anchor's
  latest constructor, skipping instances flagged deleted (e.g.
  `User.deleted`, flags.5) — a user whose only instance is deleted
  resolves null.

## sqlite vs Postgres

Logic tests run on in-memory sqlite (plan Tech Stack). On sqlite,
DEFERRABLE FKs are ignored — the ingestor writes bottom-up so immediate
FKs suffice on both drivers. Postgres (the production truth) additionally
gets native `uuid` columns and deferrable constraints from the same
generated migrations.

PG-only behavior is exercised by the opt-in PG test track: `tests/Pg`
runs the full 637-migration generated mirror (plus a deferrable-FK
proof) on a disposable schema. `TELEFRAME_PG=1` (with the
`TELEFRAME_PG_*` connection env) forces the track on — an
unreachable server then fails rather than skips; unset, it runs only
when the configured local Postgres answers, else skips. CI runs it in a
dedicated `pg-tests` job against a postgres:17 service.
