# Teleframe End-to-End Implementation Goals

> **For agentic workers:** This is the master goal set extracted from session analysis. Execute in WIP=1 order. Each phase produces spec/plan → execute → gate.

**Date:** 2026-09-10
**Source:** Analysis of sessions `fcef9905`, `f47edb21`, current working tree state
**Goal:** Full production pipeline: Telegram → MTProto engine → Postgres mirror → Centrifugo realtime → Laravel web UI showing any account live

---

## Current State (Extracted from Sessions)

### What's Done
- Teleframe MTProto wire engine: auth key handshake, `call()`/`callBatch()`, keepalive, Layer 227 wire
- Schema pipeline: `teleframe:schema-update` (diff + regenerate + stamp)
- Module merge: teleclient absorbed (Ingest, Bus, Daemon, Backfill, Backup)
- Handler substrate: registry, middleware, `Teleframe` facade, `FakeDispatcher`
- Laravel bridge: provider, routes, facades, config
- Framework layers 5a-5g: routing, identity, keyboards, templates, stages, bot map, mini-app hosting
- Live-readiness docs, credential vault
- IdentityLockTest: now passes (was pre-existing failure)

### What's Mid-Flight (Uncommitted Working Tree)
- Mirror schema migration: global-ID types flipped to `bigInteger('id')->primary()` (Telegram ID IS the PK), shared across tenants
- Ingestor/aggregator updated: skip `account_id` filter for global types (shared anchors)
- `TlPeerPeerChannel::sole()` 2-record bug: unresolved
- `composer verify` gate: last run had 5 phpstan errors (4 pre-existing + 1 introduced), fixed but uncommitted
- Laravel 13 app skeleton scaffolded inside repo (untracked): `app/`, `routes/`, `config/`, `bootstrap/`, `database/`, `artisan`, `vite.config.js`

### The Open Decision
- **PK ruling**: HEAD = surrogate PKs for global types. Working tree = Telegram ID as PK (shared anchors). This must be locked before anything else — it drives schema, ingestor, and all tests.

---

## End-to-End Goal Phases

### Phase 0: Learn from TDLib (Algorithms, Not Code)
**PITA: 10/10 — foundational**
**Plan:** `docs/superpowers/plans/2026-09-10-tdlib-reverse-engineering-analysis.md`

Study TDLib's production algorithms to harden Teleframe's engine before building on top of it.

| Sub-Phase | Focus | Output |
|-----------|-------|--------|
| A | Update state machine (pts, qts, gap handling, difference processing) | Algorithm spec + PHP implementation |
| B | Auth key lifecycle (persistence, reuse, re-auth on error) | Auth key persistence + management |
| C | Error recovery (flood wait, DC migration, backoff, state resync) | Retry logic matrix + implementation |
| D | Entity storage schema (normalization, indexing, merge strategy) | Schema improvement proposals |
| E | Multi-DC management (DC info caching, file references) | DC migration implementation |
| F | Wire protocol edge cases (ACK, seqno, containers, gzip) | Wire hardening |

**Gate:** Each phase produces analysis doc + improved code + tests ✅ (6/6 docs committed)

---

### Phase 1: Lock the Mirror Schema Ruling
**PITA: 10/10 — blocks everything**

**Decision to lock:** Global-ID types (User, Chat, Channel) — Telegram ID as PK (shared across tenants) vs surrogate PK (per-tenant).

**Arguments for Telegram ID as PK (shared):**
- Matches TDLib's approach (one entity, many accounts reference it)
- Simpler schema, no duplicate data
- Natural: a Telegram user IS the same user regardless of which account sees them
- Fewer rows, better query performance

**Arguments for surrogate PK (per-tenant):**
- Simpler multi-tenant isolation (account_id filter everywhere)
- No shared state between tenants
- Old tests already encode this model

**Recommendation:** Telegram ID as PK (shared). The shared-anchor model in the working tree is correct. A Telegram entity is a global object — it exists once, regardless of how many accounts observe it. `account_id` goes on child tables (messages, participants) where multi-tenant isolation matters.

**Tasks:**
- [x] Lock decision: global-ID types = Telegram ID as PK, shared anchors
- [x] Fix `TlPeerPeerChannel::sole()` 2-record bug (entity aggregation issue)
- [x] Regenerate all migrations with final schema
- [x] Pass `composer verify` (phpunit + phpstan + regeneration idempotence)
- [x] Pass `standalone-smoke.php` (30/30)
- [x] Pass `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`
- [x] Commit: `fix(schema): lock global-ID shared-anchor model + gates green` (d54eadd, c4266ea)

**Gate:** All 3 test suites green, committed ✅

---

### Phase 2: Laravel App Shell
**PITA: 8/10 — needed for UI**

The untracked Laravel skeleton exists but needs integration.

**Tasks:**
- [ ] Decide: monorepo (app lives here) vs separate consumer app
- [ ] Wire `merezarezaei/teleframe` as Composer path repository for development
- [ ] Configure `config/teleframe.php` with live mode, Redis, Postgres
- [ ] Set up database migrations (`php artisan migrate`)
- [ ] Configure authentication (Laravel Breeze or similar)
- [ ] Verify `teleframe:login` connects to real Telegram account
- [ ] Gate: `php artisan tinker` → `Teleframe::user()->call('help.getNearestDc')` succeeds

---

### Phase 3: Mirror Proof (No UI)
**PITA: 9/10 — core value**

Prove the mirror pipeline works end-to-end: real account → updates → Postgres → queryable.

**Tasks:**
- [ ] `teleframe:login` with real phone number (opt-in live gate)
- [ ] `teleframe:poll` running, receiving real updates
- [ ] Updates hitting `tl_*` tables in Postgres
- [ ] `teleframe:backfill` populating historical messages
- [ ] Query mirror: `TlMessageMessage::where('chat_id', X)->latest()->get()` returns real messages
- [ ] Gate: real account → poll → ingest → query → data visible

---

### Phase 4: Realtime Layer (Centrifugo)
**PITA: 8/10 — needed for web UI**

Connect the mirror to browser via Centrifugo WebSocket push.

**Tasks:**
- [ ] Install Centrifugo (Docker or baremetal)
- [ ] Create `UpdateStored → Centrifugo` bridge: listen to `UpdateStored` events, publish to per-account channels
- [ ] Channel naming: `account:{account_id}:updates`, `account:{account_id}:messages:{chat_id}`
- [ ] Centrifugo config: authentication, channel permissions
- [ ] Laravel SDK for Centrifugo (`centrifuge/laravel-centrifugo`)
- [ ] Gate: push `UpdateStored` event → Centrifugo → WebSocket client receives it

---

### Phase 5: Account Routing
**PITA: 7/10 — multi-account support**

Per-account prefixed routes for the web UI.

**Tasks:**
- [ ] Route: `/accounts/{slug}/` → account resolution middleware
- [ ] Account model: `TeleframeAccount` (name, slug, phone, status, connected_at)
- [ ] Account management: connect/disconnect/list accounts
- [ ] Per-account API: `/accounts/{slug}/chats`, `/accounts/{slug}/messages/{chat_id}`
- [ ] Gate: two accounts connected, switching between them via URL

---

### Phase 6: Web UI
**PITA: 6/10 — the user-facing product**

Telegram-like web interface showing live mirror.

**Tasks:**
- [ ] Vue 3 + Vite setup (leverage Phase 5g mini-app hosting foundation)
- [ ] Centrifugo WebSocket client integration
- [ ] Chat list component (left panel, real-time updates)
- [ ] Message view component (right panel, message history + live new messages)
- [ ] Account switcher (top bar or sidebar)
- [ ] Responsive design (desktop-first, mobile-friendly)
- [ ] Gate: open browser → see account → chats list → click chat → see messages → send test message → appears in real-time

---

### Phase 7: Control Plane (Stretch)
**PITA: 5/10 — management features**

Admin capabilities for managing accounts and monitoring.

**Tasks:**
- [ ] Dashboard: account status, message counts, connection health
- [ ] Account health monitoring (connection status, last update, error rate)
- [ ] Message search across accounts
- [ ] Export functionality (chat history, media)
- [ ] API rate limiting and abuse prevention

---

## Execution Order (WIP=1)

```
Phase 0: TDLib Analysis (foundation)
    ↓
Phase 1: Lock Schema Ruling (unblocks everything)
    ↓
Phase 2: Laravel Shell (needed for Phase 3-7)
    ↓
Phase 3: Mirror Proof (core value - real account → data)
    ↓
Phase 4: Centrifugo (realtime push to browser)
    ↓
Phase 5: Account Routing (multi-account support)
    ↓
Phase 6: Web UI (the product)
    ↓
Phase 7: Control Plane (stretch goals)
```

**Critical path:** Phase 0 → Phase 1 → Phase 3 → Phase 4 → Phase 6

---

## Open Questions (Must Answer Before Starting)

1. **PK ruling:** Telegram ID as PK (shared) or surrogate PK (per-tenant)? (See Phase 1 arguments)
2. **App layout:** Monorepo or separate consumer app?
3. **Centrifugo hosting:** Docker or baremetal?
4. **Auth for web UI:** Laravel Breeze, Fortify, or custom?
5. **Telegram login flow:** Phone number → SMS code → `teleframe:login` or web-based?

---

## Success Criteria

**MVP (Phases 0-4):**
- [ ] TDLib algorithms analyzed and documented
- [ ] Mirror schema locked and gates green
- [ ] Real Telegram account connected
- [ ] Updates flowing to Postgres in real-time
- [ ] Centrifugo pushing updates to WebSocket clients
- [ ] Browser shows live Telegram mirror

**Full Product (Phases 5-7):**
- [ ] Multiple accounts connected simultaneously
- [ ] Per-account routing working
- [ ] Telegram-like web UI in browser
- [ ] Real-time updates appearing without refresh
- [ ] Account management dashboard

---

## Notes

- **Each phase is independently shippable.** Don't block Phase 4 on Phase 0 if auth is already good enough.
- **Live gates are opt-in.** Real Telegram credentials never required for unit tests.
- **TDLib analysis is study, not copy.** Extract algorithms, implement in PHP idiomatically.
- **The Laravel skeleton exists but is uncommitted.** Decide layout before wiring anything.
