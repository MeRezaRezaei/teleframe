# TDLib Reverse Engineering & Teleframe Engine Improvement Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Study TDLib's production-proven algorithms (update state machine, auth key lifecycle, entity storage, error recovery) and use those insights to harden Teleframe's PHP MTProto engine to production reliability.

**Why TDLib is the reference:** TDLib is Telegram's own C++ MTProto implementation, battle-tested for years, handling every edge case the Telegram servers throw at clients. Its algorithms are the *authoritative* behavior specification. Teleframe's PHP engine is simpler but must match TDLib's robustness for the mirror-to-web UI pipeline.

**Approach:** Extract-algorithms-don't-copy-code. Study TDLib's *decisions* (what state it tracks, what it retries, when it gives up), translate those into PHP-native patterns, and improve Teleframe's engine in targeted phases.

**TDLib Source:** `github.com/tdlib/td` (GPL-3.0). Key directories:
- `td/mtproto/` — MTProto transport, auth key handshake, packet encryption
- `td/telegram/UpdatesManager.*` — update state machine (pts, qts, date tracking)
- `td/telegram/AuthKey.*` — auth key lifecycle, caching, rotation
- `td/telegram/Session.*` — session state persistence
- `td/telegram/StateManager.*` — connection state management
- `td/telegram/StorageManager.*` — SQLite entity storage schema
- `td/telegram/Scheme.*` — TL schema loading and versioning
- `td/generate/scheme/telegram_api.tl` — canonical TL definitions (Layer 229+)

**Tech Stack:** PHP 8.2+, our existing `src/Core/MTProto/` engine.

**Spec:** `docs/superpowers/specs/2026-08-28-mtproto-wire-path-design.md` (current engine state)

---

## Analysis Domain Map

| Domain | TDLib Source | Teleframe's Current State | Gap |
|--------|-------------|--------------------------|-----|
| **Update State Machine** | `UpdatesManager`, `DifferenceHandler`, `PartialServerPts` | `UpdatePollerService` basic `updates.getDifference` | No gap handling, no out-of-order, no pts/qts/date tracking |
| **Auth Key Lifecycle** | `AuthKeyFactory`, `AuthKey`, `AuthKeyHandshake` | `AuthKeyFactory::generate()` works, `ensureAuthKey()` basic | No key rotation, no re-auth on error, no multi-DC auth |
| **Session Persistence** | `Session`, `SessionManager` | `SessionData` DTO exists, files to disk | No graceful serialization, no state sync |
| **Entity Storage** | `StorageManager`, SQLite schema | `UpdateIngestor` → Eloquent `tl_*` tables | Incomplete entity normalization, missing fields |
| **Error Recovery** | `StateManager`, retry logic | `Client::call()` rethrows on transport error | No retry, no backoff, no connection recovery |
| **Multi-DC Handling** | `DCManager`, file references | Hardcoded DC IPs, no file ref handling | DC migration, file download routing |

---

## Phase A: TDLib Update State Machine Deep Dive (PITA: 10/10)

This is the highest-impact analysis. The update state machine is what makes or breaks a mirror.

**What to study in TDLib:**
- `td/telegram/UpdatesManager.h` — state fields: `pts`, `qts`, `date`, `seq`
- `td/telegram/DifferenceHandler.*` — `updates.getDifference` loop logic
- `td/telegram/PartialServerPts.*` — gap detection and fill
- `td/telegram/UpdateManager.*` — update ordering, deduplication, flush

**Key algorithms to extract:**
1. **Gap detection**: when `received_pts - stored_pts > 1`, there's a gap → call `updates.getDifference`
2. **Difference processing**: `DifferenceInfo` contains `newMessages`, `newEncryptedMessages`, `otherUpdates`, `users`, `chats` — process in order
3. **State sync**: after processing difference, snapshot `(pts, qts, date)` to local store
4. **updatesTooLong**: server says "too many queued updates" → force full resync
5. **updateShort / updatesCombined**: inline updates without full wrapper → extract and merge

**Deliverable:** Algorithm spec document + PHP implementation in `UpdatePollerService`

### Tasks

- [ ] **A1: Clone TDLib source, map UpdateManager header files**
  - Clone `tdlib/td` repo to `/tmp/tdlib-analysis/`
  - Read `td/telegram/UpdatesManager.h`, `td/telegram/DifferenceHandler.h`
  - Document: what state fields exist, what each transition means
  - Output: `docs/superpowers/analysis/tdlib-update-state-machine.md`

- [ ] **A2: Trace the gap-fill algorithm**
  - Read `DifferenceHandler::on_difference` implementation
  - Document: sequence of RPC calls, what happens on each difference type
  - Map to Teleframe's current `UpdatePollerService::pollDifference()`
  - Output: gap analysis with specific improvement points

- [ ] **A3: Implement pts/qts/date tracking in Teleframe**
  - Add `UpdateState` model/table for `(account_id, pts, qts, date, seq)`
  - Modify `UpdatePollerService` to read/write state after each poll
  - Add gap detection: `if (received_pts - stored_pts > 1) { resync }`
  - Gate: tests pass with simulated gap scenarios

- [ ] **A4: Implement difference processing loop**
  - Parse `updatesDifference` response properly (handle all 4 response types)
  - Process `newMessages` → ingest, `otherUpdates` → dispatch, `users`/`chats` → merge
  - Handle `updatesTooLong` by triggering full resync
  - Gate: `composer verify` + unit tests with mocked difference responses

---

## Phase B: Auth Key Lifecycle Hardening (PITA: 8/10)

**What to study in TDLib:**
- `td/mtproto/Handshake.*` — DH key exchange, key verification
- `td/telegram/AuthKey.*` — key caching, reuse, rotation
- `td/mtproto/Api.*` — how auth key is bound to session

**Key algorithms to extract:**
1. **Key caching**: persist generated auth keys to disk (256-byte key + DC info)
2. **Key reuse**: same key = same session, don't regenerate on reconnect
3. **Error → re-auth**: if server returns `-404 auth_key_unknown`, regenerate key on the same socket
4. **Multi-DC auth**: each DC needs its own auth key, managed independently
5. **Key lifetime**: no explicit expiry, but stale keys (no traffic for days) may be evicted by server

**Deliverable:** Auth key management improvements + persistence

### Tasks

- [ ] **B1: Study TDLib auth key lifecycle**
  - Read `td/mtproto/Handshake.cpp`, `td/telegram/AuthKey.cpp`
  - Document: key generation, storage format, reuse policy, error handling
  - Output: `docs/superpowers/analysis/tdlib-auth-key-lifecycle.md`

- [ ] **B2: Implement auth key persistence**
  - Store generated keys in encrypted DB table (`teleframe_auth_keys`)
  - On reconnect: load existing key, skip DH handshake
  - On `-404`: delete stale key, regenerate on existing socket
  - Gate: reconnect after key eviction re-authenticates cleanly

---

## Phase C: Error Recovery & Connection Resilience (PITA: 7/10)

**What to study in TDLib:**
- `td/telegram/StateManager.*` — connection state machine
- `td/mtproto/NetQuery.*` — request lifecycle, timeout handling
- Error codes: `400` (bad request), `401` (unauthorized), `420` (flood), `440` (ban), `500` (server error)

**Key algorithms to extract:**
1. **Flood wait**: server returns `FLOOD_WAIT_X` → sleep exactly X seconds before retry
2. **Server errors**: `500 Internal Server Error` → retry with exponential backoff (1s, 2s, 4s, max 5 retries)
3. **DC migration**: `PHONE_MIGRATE_X`, `USER_MIGRATE_X`, `FILE_MIGRATE_X` → reconnect to DC X
4. **Auth errors**: `401 Unauthorized` → clear auth key, re-authenticate
5. **Transport errors**: socket timeout/disconnect → reconnect with backoff, don't evict key
6. **State resync**: after extended disconnect, call `updates.getState` to get current `(pts, qts, date, seq)`

**Deliverable:** Error handling matrix + retry logic in TeleframeClient

### Tasks

- [ ] **C1: Study TDLib error handling patterns**
  - Read `td/mtproto/NetQuery.cpp`, `td/telegram/StateManager.h`
  - Document: error → action mapping, backoff schedule, state transitions
  - Output: `docs/superpowers/analysis/tdlib-error-recovery.md`

- [ ] **C2: Implement retry logic in TeleframeClient**
  - Add `RetryPolicy` value object: max retries, backoff strategy, error whitelist
  - Apply to `call()` and `callBatch()`: retry on 420/500, re-auth on 401, migrate on PHONE_MIGRATE
  - Gate: unit tests with mocked DC migration, flood wait, server error

---

## Phase D: Entity Storage Schema Learning (PITA: 6/10)

**What to study in TDLib:**
- `td/telegram/StorageManager.*` — SQLite schema design
- `td/telegram/scheme/td_api.tl` — high-level TL schema (not raw Telegram API)
- Entity normalization patterns (how TDLib decomposes Telegram objects into storage rows)

**Key patterns to extract:**
1. **Lazy loading**: entities stored by ID, relationships resolved on read
2. **Versioning**: schema version in DB, migrations on upgrade
3. **Merge strategy**: new data overwrites old, missing fields stay as-is
4. **Peer resolution**: `Peer` type → concrete table (`user`, `chat`, `channel`) → ID lookup
5. **File reference handling**: entities carry file references, must be refreshed periodically

**Deliverable:** Schema improvement proposals for `tl_*` tables

### Tasks

- [ ] **D1: Study TDLib storage schema**
  - Read `td/telegram/StorageManager.cpp`, SQLite schema definitions
  - Document: table structure, indexing strategy, versioning approach
  - Output: `docs/superpowers/analysis/tdlib-entity-storage.md`

- [ ] **D2: Compare with Teleframe's tl_* schema**
  - Map TDLib's entity decomposition to Teleframe's per-constructor tables
  - Identify missing fields, wrong indices, schema improvements
  - Output: `docs/superpowers/analysis/telframe-schema-gap-analysis.md`

---

## Phase E: Multi-DC & File Reference Handling (PITA: 5/10)

**What to study in TDLib:**
- `td/telegram/DCManager.*` — DC info, migration, file download routing
- `td/telegram/FileReferenceManager.*` — file reference lifecycle
- `td/telegram/DownloadManager.*` — chunked download, resume

**Key patterns:**
1. **DC info caching**: `help.getNearestDc` → cache DC IP/port/auth_key per DC
2. **File reference routing**: files live on specific DCs, need per-DC auth
3. **File reference expiry**: references expire, must be refreshed via `upload.getFileReference`
4. **Chunked download**: large files split into 512KB-1MB chunks, resume from offset

**Deliverable:** DC management + file reference protocol

### Tasks

- [ ] **E1: Study TDLib DC management**
  - Read `td/telegram/DCManager.*`, `td/telegram/FileReferenceManager.*`
  - Document: DC discovery, caching, migration triggers, file reference refresh
  - Output: `docs/superpowers/analysis/tdlib-dc-management.md`

- [ ] **E2: Implement DC migration in Teleframe**
  - Add DC info cache (Redis-backed)
  - Handle `PHONE_MIGRATE_X` → extract DC X, reconnect with migrated session
  - Gate: simulated DC migration test passes

---

## Phase F: Wire Protocol Edge Cases (PITA: 4/10)

**What to study in TDLib:**
- `td/mtproto/Dispatcher.*` — packet dispatch, acknowledgments
- `td/mtproto/TransportType.*` — intermediate/abridged/custom
- MTProto message types: `msg_new_session_info`, `msg_ack`, `msg_container`, `gzip_packed`

**Key patterns:**
1. **Acknowledgment**: client must ACK received messages (`msg_ack`), server drops unACK'd clients
2. **Message ordering**: server sends by seqno, client tracks `out_seq_no` for ordering
3. **Container messages**: multiple messages packed in `msg_container` for batch efficiency
4. **gzip_packed**: large responses gzip-compressed, must decompress before TL decode
5. **Quick ACK**: intermediate protocol supports quick ACK for fast round-trips

**Deliverable:** Wire protocol hardening

### Tasks

- [ ] **F1: Study TDLib MTProto dispatcher**
  - Read `td/mtproto/Dispatcher.*`, `td/mtproto/SessionInfo.*`
  - Document: seqno tracking, ACK flow, container handling, gzip
  - Output: `docs/superpowers/analysis/tdlib-wire-protocol.md`

- [ ] **F2: Implement msg_ack + seqno tracking**
  - Add `out_seq_no` counter to `EncryptedConnection`
  - Send `msg_ack` for received message IDs
  - Handle `gzip_packed` wrapper transparently
  - Gate: long-running connection doesn't get server-dropped

---

## Execution Order

```
Phase A (Update State Machine) ← PITA 10, foundation for mirror reliability
  ↓
Phase B (Auth Key Lifecycle) ← PITA 8, needed for reconnection
  ↓
Phase C (Error Recovery) ← PITA 7, needed for production
  ↓
Phase D (Entity Storage) ← PITA 6, schema improvements
  ↓
Phase E (Multi-DC) ← PITA 5, needed for file/media
  ↓
Phase F (Wire Protocol) ← PITA 4, hardening
```

**WIP = 1:** Start with Phase A. Each phase produces a spec doc + implementation.

---

## Acceptance Gates

| Gate | What | Command |
|------|------|---------|
| Analysis | Each phase produces a markdown analysis doc in `docs/superpowers/analysis/` | — |
| Unit tests | New test classes for each improvement | `vendor/bin/phpunit tests/MTProto/` |
| Static analysis | phpstan level 5 | `vendor/bin/phpstan analyse src/Core/MTProto/` |
| Full gate | All tests + static analysis | `composer verify` |
| Live gate | Real Telegram DC connection with improved engine | `TELEFRAME_LIVE=1 php artisan teleframe:doctor` |

---

## Notes

- **Don't copy TDLib's C++ code.** Extract the *algorithm* (what state it tracks, what it does on each condition), then implement idiomatically in PHP.
- **Each phase is independently shippable.** Don't block Phase C on Phase B if the auth key stuff is already good enough.
- **TDLib is GPL-3.0.** We study algorithms, not copy code. Our PHP implementation is independently written.
- **TDLib's TL schema is authoritative.** When Teleframe's `schema/methods-mtproto.json` disagrees with `td/generate/scheme/telegram_api.tl`, TDLib wins (but our Layer 229 catalog is intentionally ahead of our L227 wire).
