# TDLib Update State Machine Analysis

**Source:** TDLib `td/telegram/UpdatesManager.h` and `UpdatesManager.cpp` (5150 lines),
**TL Schema Layer:** 229 (`td/generate/scheme/telegram_api.tl`), **Date:** 2026-09-10

---

## Overview

TDLib's `UpdatesManager` is an Actor that maintains four independent
sequence counters (`pts`, `qts`, `date`, `seq`) and three separate gap
detection + repair paths (PTS, QTS, SEQ). The core design decisions are:

1. **Three parallel pending queues** (PTS, SEQ, QTS) each with independent
   gap-fill timers -- a PTS gap does not block SEQ updates and vice versa.
2. **Postpone-then-replay during getDifference** -- updates arriving while
   a `getDifference` RPC is in flight are held in `postponed_pts_updates_`
   and `postponed_updates_` (by seq), then replayed in order after the
   difference completes.
3. **Stateful dual PTS tracking** -- `PtsManager` separates `mem_pts`
   (in-memory speculative) from `db_pts` (persisted-to-disk), ensuring
   that only confirmed-on-disk state advances the persistent cursor.
4. **Timeout-based gap fill** -- after `MAX_UNFILLED_GAP_TIME` (0.7s),
   TDLib fires `getDifference` for PTS gaps; SEQ gaps use their own
   timer; QTS gaps likewise.
5. **Exponential retry backoff** -- `getDifference` failures double the
   retry interval up to 60-80 seconds.
6. **Automatic data reload on reconnect** -- after `getDifference`
   completes, a broad reload of animations, stickers, notification
   settings, etc. is triggered.

---

## State Fields (pts, qts, date, seq)

### Definition (TL Schema)

```
updates.state#a56c2a3e pts:int qts:int date:int seq:int unread_count:int = updates.State;
```

### What each counter tracks

| Counter | Scope | What it sequences | Used by |
|---------|-------|-------------------|---------|
| **pts** | Per-account | Message mutations (new/edit/delete, read history, folder moves) on the user's dialogs | `updateNewMessage`, `updateEditMessage`, `updateDeleteMessages`, `updateReadHistoryInbox/Outbox`, `updateReadMessagesContents`, `updateFolderPeers` |
| **qts** | Per-account | Encrypted (secret chat) message mutations | `updateNewEncryptedMessage`, `updateEncryptedChatTyping`, `updateEncryptedMessagesRead`, `updateEncryption` |
| **date** | Per-account | Timestamp of the last processed update | Used for `getDifference` requests and `updateShort` timestamp validation |
| **seq** | Per-account | Sequence of `updates`/`updatesCombined` bundles (not individual PTS updates) | Ordering of `updates` and `updatesCombined` responses |

**Critical distinction:** `pts` tracks individual *mutations* (each update
carries `pts` + `pts_count`), while `seq` tracks *batches* (each
`updates`/`updatesCombined` response carries `seq_start`/`seq`).

### Per-channel PTS

Channel updates (`updateNewChannelMessage`, `updateDeleteChannelMessages`,
etc.) carry their own PTS that is **independent** of the account-level PTS.
TDLib handles channel PTS through a separate path
(`MessagesManager::add_pending_channel_update`), not through `UpdatesManager`
directly. This is analogous to how Teleframe's `channelPts` map works.

### TDLib's PtsManager (dual cursor)

```cpp
class PtsManager {
  int32 db_pts_ = -1;   // persisted to binlog PMC
  int32 mem_pts_ = -1;  // in-memory speculative, advances on add_pts()
};
```

The `ChangesProcessor` ensures `db_pts` only advances when the corresponding
write is confirmed on disk. This prevents data loss on crash: if TDLib
crashes after processing an update but before persisting the new PTS, it
will re-fetch the update from the server.

---

## Gap Detection Algorithm

### When TDLib detects a PTS gap

A PTS gap exists when the next update in the sorted pending queue does not
connect to the current PTS:

```
current_pts + pts_count != next_pending.pts
```

Specifically, in `add_pending_pts_update()`:

1. If `new_pts <= old_pts`: the update is old -- skip it entirely.
2. If `old_pts > new_pts - pts_count`: the update overlaps current state -- it is
   a "partially applied" situation; the update is postponed with a very short
   gap timeout (0.001s).
3. If the pending queue is not empty and updates cannot be applied immediately
   because there is a gap, a gap timeout is set.

### Gap timeout logic

TDLib uses **two** gap-fill timers for PTS:

- `min_pts_gap_timeout_`: fires at `MIN_UNFILLED_GAP_TIME` (0.05s) -- a
  quick check (`check_pts_gap`) that sees if the gap can be filled by a
  single PTS query rather than a full `getDifference`.
- `pts_gap_timeout_`: fires at up to `MAX_UNFILLED_GAP_TIME` (0.7s) --
  triggers the full `fill_pts_gap` -> `get_difference("fill_gap")`.

**Key decision:** TDLib waits only 0.05-0.7 seconds before filling a gap.
It does not wait for "a few more updates to arrive." This is because on
modern Telegram, genuine gaps are rare and most gaps indicate a real missed
state that must be repaired immediately.

### Short-gap repair (single PTS query)

Before triggering a full `getDifference`, TDLib tries `repair_pts_gap()`:
if the pending queue starts exactly at `current_pts + 1` with a known
`pts_count`, it fetches just that single PTS update via `GetPtsUpdateQuery`
(which calls `updates.getDifference` with PTS limit flags). This avoids
the heavyweight full-difference path for single missing updates.

### SEQ gap detection

SEQ gaps are detected in `on_pending_updates()`: if `seq_begin != 0 && seq_begin != seq_ + 1`,
the batch cannot be applied directly. It goes to `pending_seq_updates_` with a
timeout. If no matching seq range arrives, `fill_seq_gap` fires and triggers
`getDifference`.

### QTS gap detection

Same pattern as PTS but for encrypted updates. If `new_qts > old_qts + 1`,
the update goes to `pending_qts_updates_` with a timeout, and `fill_qts_gap`
triggers `getDifference` if it is not filled.

### Forced getDifference

TDLib forces a `getDifference` in these additional situations:

- **`updatesTooLong` received:** The server says the gap is too large to
  describe inline. TDLib immediately calls `getDifference`.
- **Unacceptable update received:** An update references a dialog the client
  does not know about. TDLib calls `getDifference` to fetch missing state.
- **`updateShortSentMessage` received unexpectedly:** Triggers getDifference.
- **PTS jump > 100,000:** `FORCED_GET_DIFFERENCE_PTS_DIFF = 100000`. If the
  confirmed PTS and the received PTS diverge by more than this, getDifference
  is forced.
- **PERSISTENT_TIMESTAMP_INVALID error:** Resets PTS to max and triggers
  a full state re-fetch.
- **Server pong divergence:** If a ping/pong shows the server's PTS or SEQ
  is ahead, getDifference is triggered.

---

## Difference Processing Loop

### RPC sent

```cpp
GetDifferenceQuery::send(int32 pts, int32 date, int32 qts) {
    // flags=0: no pts_limit, no qts_limit, no pts_total_limit
    telegram_api::updates_getDifference(0, pts, 0, 0, date, qts, 0)
}
```

### Response types and processing

#### `updates.differenceEmpty` (date, seq)

The simplest case: no updates missed, just state confirmation.

**Algorithm:**
1. Set `date` and `seq` from the response.
2. Process any pending QTS updates (they may now fit).
3. Drop any remaining pending QTS updates (they reference stale state).
4. Process any pending SEQ updates (same logic).
5. Drop remaining pending SEQ updates.
6. Call `after_get_difference()`.

**Decision:** TDLib drops stale QTS and SEQ updates after an empty
difference rather than trying to reconcile them. This is safe because
those updates were held by timeout and the server says there is nothing
to fill.

#### `updates.difference` (new_messages, new_encrypted_messages, other_updates, users, chats, state)

The complete catch-up: all missed data in one response.

**Algorithm:**
1. Process `users` and `chats` (entity hydration).
2. Validate messages (up to 5 retries for invalid poll messages).
3. Call `process_get_difference_updates()`:
   - Process `other_updates` first (especially `updateMessageID` for
     sent-message mapping, `updateEncryption`, `updateFolderPeers`).
   - Null out `updateUser`, `updateChat`, `updateChannel` (redundant
     with the users/chats vectors).
   - Process `new_messages` (non-channel only; channel messages should
     not appear here).
   - Process `new_encrypted_messages` via `SecretChatsManager`.
   - Apply remaining `other_updates` through `process_updates()`.
4. Adopt the final `state` via `on_get_updates_state()`.
5. Call `after_get_difference()`.

**Processing order:** other_updates -> new_messages -> encrypted_messages.
This is intentional: `updateMessageID` (which maps random_id to server
message ID for sent messages) must be processed before the messages it
refers to.

#### `updates.differenceSlice` (new_messages, ..., intermediate_state)

A partial catch-up: the server returns a chunk and an intermediate state
for continuing.

**Algorithm:**
1. Process users, chats.
2. Validate messages (same retry logic).
3. Call `process_get_difference_updates()` (same as above).
4. Adopt the `intermediate_state` via `on_get_updates_state()`.
5. Process any postponed PTS updates that may now fit.
6. Process pending QTS updates.
7. **Decide whether to continue:** If PTS/date/qts all match what we had
   before, stop (we are caught up). If the state advanced, call
   `run_get_difference(true, ...)` recursively to fetch the next slice.

**Key decision:** TDLib loops `getDifference` recursively for
`differenceSlice` until the state stops advancing. The maximum loop
count is bounded by `get_difference_retry_count_` (reset to 0 after
each non-recursive completion) and the 5-retry invalid-message check.

#### `updates.differenceTooLong` (pts)

The server says the gap is too large to replay.

**Algorithm:**
1. Hard-reset PTS to the server's value via `set_pts(difference->pts_)`.
2. Immediately call `get_difference("on updates_differenceTooLong")` to
   re-fetch from the new position.

**Decision:** TDLib does not attempt to salvage any data from the gap.
The server's PTS is authoritative. After reset, it starts a fresh
getDifference from the new PTS, which will return a `difference` or
`differenceSlice` for the current state.

### After getDifference completes (`after_get_difference`)

1. Cancel retry timeout, reset retry_time to 1.
2. Set `skipped_postponed_updates_after_start_ = 0`.
3. Process pending QTS updates (they may now fit the updated state).
4. Process pending SEQ updates.
5. **Replay postponed seq updates** (`postponed_updates_`): iterate
   in sorted order by seq_begin, calling `on_pending_updates()` for each.
   If any triggers another getDifference, stop and return.
6. **Replay postponed PTS updates** (`postponed_pts_updates_`): iterate
   and re-inject through `add_pending_pts_update()`.
7. Notify download manager, inline queries, messages manager,
   notification manager.
8. Signal `StateManager::on_synchronized(true)`.
9. Trigger data reload (animations, stickers, notification settings, etc.).

---

## State Sync & Persistence

### When state is saved

TDLib persists PTS and QTS to the binlog PMC (key-value store) through
`save_pts()` and `save_qts()`. These are called from `PtsManager::on_ack()`
which only fires after the disk write confirms.

**Save timing:**
- `MAX_PTS_SAVE_DELAY = 0.05` (50ms) -- PTS is saved at most every 50ms,
  except for bots which save immediately on every advance.
- State is also saved during `on_get_updates_state()` (which is called
  when adopting a state from getDifference or getState).
- `init_state()` loads persisted PTS/QTS/date from the PMC on startup.

### Dual-cursor persistence strategy

```
mem_pts  <-- advances immediately on add_pts()
db_pts   <-- advances only after disk confirmation
```

This means:
- In-memory, PTS can be ahead of what is on disk.
- On crash, the persisted PTS is the recovery point.
- After recovery, TDLib calls `getDifference` from the persisted PTS,
  which re-fetches any updates between the persisted PTS and the
  current server state.

### Initial state

On first run (no persisted PTS):
1. Call `updates.getState()` to get the server's current (pts, qts, date,
   seq).
2. Store these as the initial state.
3. Call `getDifference()` to fetch any recent updates.

On subsequent runs:
1. Load PTS/QTS/date from persisted storage.
2. Call `getDifference()` from the loaded state to catch up.

---

## updateShort / updatesCombined Handling

### `updateShort` (update, date)

TDLib extracts the inner `update` and processes it directly via
`downcast_call()`. It sets `short_update_date_` for the duration of
processing so that the update's date is the wrapper's `date`, not the
server timestamp.

**Important:** TDLib validates the update is "acceptable" (the client
has the referenced dialog). If not, it triggers getDifference.

### `updateShortMessage` / `updateShortChatMessage`

These are compact wrappers for `updateNewMessage`. TDLib reconstructs the
full `telegram_api::message` object from the short fields and feeds it
into `on_pending_update()` as a normal PTS update. They carry `pts` and
`pts_count`, so they participate in the PTS gap detection system.

### `updatesCombined` (updates, users, chats, date, seq_start, seq)

TDLib processes users and chats first, then feeds the updates into
`on_pending_updates()` with `seq_begin = seq_start` and `seq_end = seq`.
The SEQ gap detection applies here.

### `updates` (updates, users, chats, date, seq)

Same as `updatesCombined` but with `seq_start == seq` (single seq value).

**Key decision:** Both `updates` and `updatesCombined` participate in the
SEQ ordering system. Individual PTS updates within them are extracted and
handled through the PTS path. Non-PTS updates are handled through the SEQ
path.

---

## updatesTooLong Handling

When TDLib receives `updatesTooLong`:

1. Immediately call `getDifference("updatesTooLong")`.
2. No data is saved or processed from the `updatesTooLong` itself -- it
   carries no payload, just signals "you are too far behind."

**Interpretation:** `updatesTooLong` is the server's way of saying "I
cannot fit the gap description in a single response." The only correct
action is to fetch the full difference from the current state.

**In Teleframe's handling:** Teleframe correctly treats this as a
hard-reset signal (resetting PTS to the server value and continuing).
TDLib does the same but more aggressively: it triggers getDifference
immediately rather than resetting PTS first.

---

## Comparison with Teleframe's Current State

### What Teleframe already handles well

1. **Core difference state machine:** `UpdatePollerService::fetchUserDifference()`
   correctly handles all four response types (`difference`, `differenceSlice`,
   `differenceEmpty`, `differenceTooLong`) with the right state transitions.
2. **Slice continuation:** On `differenceSlice`, Teleframe correctly loops
   immediately without sleeping (like TDLib).
3. **Hole detection:** Teleframe detects when a slice makes no forward
   progress and forces the window forward -- a defensive measure TDLib does
   not need because it validates PTS ranges more strictly.
4. **Gap/resync events:** `TelegramGapDetected` and `TelegramResynced` events
   provide hooks for consumers.
5. **Monotonic state adoption:** `adoptState()` ensures PTS/QTS/SEQ never
   move backwards.
6. **Per-channel PTS tracking:** `trackChannelPts()` maintains the channel
   PTS map.
7. **FloodWait handling:** Backoff with interruptible sleep.
8. **DC migration:** `AccountWorker` rebuilds the scope at the new DC.

### What TDLib does that Teleframe does not

| Feature | TDLib | Teleframe | Impact |
|---------|-------|-----------|--------|
| **Pending PTS update queue** | Maintains `pending_pts_updates_` (multiset sorted by PTS) with gap-fill timeouts; updates arriving while a gap exists are queued, not dropped | Updates are processed immediately or lost; no pending queue | **High:** Without a pending queue, out-of-order updates that arrive during gap-fill may be missed or processed incorrectly |
| **Postponed updates during getDifference** | `postponed_pts_updates_` and `postponed_updates_` (by seq) hold updates arriving during getDifference; replayed after completion | Updates arriving during getDifference may be lost if the connection delivers them while getDifference is in flight | **Medium:** Less critical in PHP's synchronous model, but relevant for multi-process workers |
| **Gap timeout with configurable delays** | Uses `MIN_UNFILLED_GAP_TIME` (0.05s) and `MAX_UNFILLED_GAP_TIME` (0.7s) timers before triggering getDifference | No configurable gap-fill delay; getDifference is triggered immediately on each poll cycle | **Low:** Teleframe polls continuously so this is less important, but a delay could reduce unnecessary getDifference calls |
| **Short-gap single-PTS repair** | `repair_pts_gap()` fetches a single missing PTS update via `GetPtsUpdateQuery` with PTS limit flags before trying full getDifference | No equivalent -- always does a full getDifference | **Low-Medium:** Single-PTS repair is more efficient for small gaps |
| **PTS/QTS persistence with dual cursor** | `PtsManager` with `db_pts`/`mem_pts` separation; PTS saved to binlog every 50ms with disk-confirmation gating | `sequenceState` is in-memory only; host app must persist it externally via `getSequenceState()`/`setSequenceState()` | **Medium:** Teleframe's approach is correct but relies on the host application to persist state between daemon runs |
| **SEQ gap handling** | Independent SEQ queue with timeout-based gap fill for `updates`/`updatesCombined` bundles | SEQ is tracked but not used for gap detection; seq is adopted from server state | **Low:** SEQ is primarily used for `updates`/`updatesCombined` ordering, which is less relevant for getDifference-based polling |
| **QTS gap handling** | Independent QTS queue with timeout-based gap fill for encrypted updates | QTS is tracked and passed to getDifference; no independent QTS gap detection | **Low:** Secret chat support is not a primary use case |
| **Retry backoff for getDifference** | Exponential backoff: 1s, 2s, 4s, ... up to 60-80s; retries on failure | Retry immediately on each poll cycle (1s sleep between terminal responses) | **Low:** Teleframe's 1s sleep is reasonable; exponential backoff would reduce server load during sustained errors |
| **PERSISTENT_TIMESTAMP_INVALID recovery** | Resets PTS to max, then re-fetches state from server | Not handled; an unrecognized response throws `UnexpectedValueException` | **Medium:** This error can occur when the server invalidates the session's PTS; without handling, the poller would crash |
| **Unacceptable update handling** | If an update references an unknown dialog, replaces channel updates with `updateChannelTooLong` or triggers getDifference | Not implemented; all updates are passed through | **Low:** Less relevant for PHP which does not maintain a full dialog cache |
| **Data reload after getDifference** | Reloads animations, stickers, notification settings, quick replies, reactions after each getDifference completion | Not implemented; getDifference only delivers updates, no supplementary data reload | **Low:** This is TDLib-specific client state; less relevant for a server-side engine |
| **Entity processing order in difference** | Processes users/chats BEFORE messages (entity hydration first), then other_updates, then new_messages | `streamUserPayload` processes other_updates, then encrypted, then new_messages (matching MadelineProto order) | **Low:** Both orders work; TDLib's order is slightly better for entity-dependent message processing |
| **PTS-based update filtering** | `check_pts_update()` validates that the update is a known PTS-bearing type; rejects malformed updates | No validation of update structure | **Low:** Malformed updates from the server are extremely rare |

---

## Recommendations for Teleframe Implementation

### Priority 1: Pending PTS Update Queue

**What:** Add a sorted pending queue for PTS updates that arrive when
there is a gap (current PTS + pts_count != new update's PTS).

**Why:** Without this, updates that arrive while a gap exists are either
processed out of order or lost. TDLib's `pending_pts_updates_` multiset
holds these updates until the gap is filled.

**PHP implementation sketch:**
- Add `PendingPtsUpdate` value object: `{update, pts, pts_count, receive_time}`
- Add `pending_pts_updates: SplPriorityQueue` (sorted by pts) to `UpdatePollerService`
- In `fetchUserDifference`, when a PTS update arrives with a gap:
  - Queue it in `pending_pts_updates`
  - Set a gap-fill timer (use `pcntl_alarm` or timestamp-based check)
- After getDifference completes, replay queued updates that now fit

### Priority 2: PERSISTENT_TIMESTAMP_INVALID Recovery

**What:** Handle the case where getDifference returns an error indicating
the PTS is invalid (PERSISTENT_TIMESTAMP_INVALID).

**Why:** Without this, the poller throws an exception and crashes. TDLib
handles this by resetting PTS to max, fetching fresh state, and
restarting.

**PHP implementation:**
- In `fetchUserDifference`, catch RPC error with message
  `PERSISTENT_TIMESTAMP_INVALID`
- Reset PTS to 0 (latest state)
- Call `updates.getState()` to get fresh (pts, qts, date, seq)
- Continue polling from the fresh state

### Priority 3: Exponential Retry Backoff for getDifference

**What:** On getDifference failure, double the retry delay up to a cap.

**Why:** TDLib doubles from 1s to 60-80s. Teleframe currently retries
immediately, which can hammer the server during sustained outages.

**PHP implementation:**
- Add `retryBackoff: int` field (default 1, max 60)
- On getDifference failure: sleep `retryBackoff`, then `retryBackoff = min(retryBackoff * 2, 60)`
- On successful getDifference: reset `retryBackoff = 1`

### Priority 4: updateShort / updateShortMessage Expansion

**What:** Expand `updateShortMessage` and `updateShortChatMessage` into
full `updateNewMessage` objects before passing to the sink.

**Why:** TDLib reconstructs the full message object from the short
fields. This is necessary for consistent downstream processing.
Teleframe currently passes updates through raw, so `updateShortMessage`
arrives as-is without the `_` constructor that handlers expect.

**PHP implementation:**
- In `fetchUserDifference` or `streamUserPayload`, detect
  `updateShortMessage` / `updateShortChatMessage` constructor names
- Reconstruct a normalized `updateNewMessage` with the same field
  structure as a full update
- This matches what TDLib does at lines 1231-1260 of UpdatesManager.cpp

### Priority 5: Incoming Update Validation

**What:** Validate that PTS updates have `new_pts > stored_pts` and that
`new_pts - pts_count` aligns with the current state.

**Why:** TDLib's `add_pending_pts_update` has extensive validation:
- Rejects updates with `pts_count < 0` or `new_pts <= pts_count`
- Skips updates where `new_pts <= old_pts` (already processed)
- Detects PTS jumps > 100,000 and forces getDifference
- Logs warnings for overlapping PTS ranges

**PHP implementation:**
- Add validation in `fetchUserDifference` after extracting PTS from
  `other_updates`:
  - Skip updates with `pts <= current_pts`
  - If `pts - current_pts > 100000`, force a full getDifference
  - Log warnings for overlapping ranges

### Priority 6: Server Pong-Based State Check

**What:** Periodically call `updates.getState()` (as a "ping") and
compare the returned PTS/SEQ with local state to detect silent
desynchronization.

**Why:** TDLib sends `updates.getState()` as a ping (via
`PingServerQuery`) and triggers getDifference if the server's PTS or SEQ
is ahead. This catches cases where updates are silently lost without a
gap being detected.

**PHP implementation:**
- Add an optional periodic `updates.getState()` call (e.g., every 60s)
- Compare returned PTS with local PTS
- If server PTS > local PTS, trigger getDifference
- This can be implemented as a separate timer in the daemon loop

---

## Summary of Key TDLib Design Decisions

1. **PTS is the primary ordering mechanism** for individual updates. SEQ
   is secondary and only used for batch ordering.
2. **Gaps are filled immediately** (0.05-0.7s timeout), not lazily.
3. **During getDifference, incoming updates are postponed**, not dropped.
4. **State persistence uses a dual-cursor approach** to survive crashes.
5. **All update types are normalized** to a consistent internal format
   before processing (short messages become full updateNewMessage).
6. **Retry backoff is exponential** with a 60-80s cap.
7. **Entity processing happens before message processing** in difference
   responses.
8. **Server state is authoritative** -- on disagreement, TDLib adopts the
   server's state (especially for differenceTooLong).
