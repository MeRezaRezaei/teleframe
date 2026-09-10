# TDLib Wire Protocol Edge Cases Analysis

**Date:** 2026-09-10
**Source:** TDLib September 2026 snapshot (`tdlib/td@latest`, layer 227-era wire / 229 schema)
**Scope:** msg_ack batching, seqno semantics, container packing, gzip_packed, quick ACK, server-sent service messages, two-connection model
**Phase:** 0-F of the TDLib reverse-engineering series

---

## Overview

MTProto 2.0 is more than "encrypt a TL object, send it over TCP." The wire
carries its own service layer: acknowledgments, containers, gzip wrappers,
quick acknowledgments, salt rotations, and session notifications. Every one
of these has a correct behavior that, if missed, silently degrades the
connection or causes an outright drop after a timeout. This document
reverse-engineers each edge case from TDLib's C++ source, maps it to
Teleframe's PHP implementation, and produces a numbered recommendation
table for hardening.

The machinery spans these files (no single "Dispatcher" or "WireManager"):

| Component | TDLib file(s) | Responsibility |
|---|---|---|
| Session state machine | `SessionConnection.h` / `.cpp` | Packet dispatch, ACK queuing, container assembly, ping/salt lifecycle |
| Auth data + seq_no | `AuthData.h` / `.cpp` | `next_message_id()`, `next_seq_no()`, salt management, message-id duplicate check |
| Message ID type | `MessageId.h` | Strong uint64 wrapper with ordering operators |
| Packet serialization | `PacketStorer.h`, `CryptoStorer.h` | Template-based TL storer for encrypted transport |
| Raw transport I/O | `RawConnection.h` / `.cpp` | Socket read/write, quick ACK token resolution, MTProto error dispatch |
| Transport framing | `TcpTransport.h` / `.cpp`, `HttpTransport.h`, `IStreamTransport.h` | Abridged / intermediate / obfuscated / HTTP framing; quick ACK bit |
| Connection lifecycle | `ConnectionManager.h` / `.cpp` | Token-based connection counting, mode 1 = main, mode 2 = proxy |
| Ping keepalive | `PingConnection.h` / `.cpp`, `Ping.cpp` | Ping-pong RTT measurement, req_pq probe |

---

## 1. Acknowledgment Flow (msg_ack)

### 1.1 TDLib's delayed-batch ACK algorithm

`SessionConnection::send_ack()` (SessionConnection.cpp:897-910) queues
acknowledgment IDs with a 30-second delayed timer:

```
ACK_DELAY = 30;                                    // header:33
void send_ack(MessageId message_id) {
  if (to_ack_message_ids_.empty()) {
    send_before(Time::now_cached() + ACK_DELAY);   // line 900: timer start
  }
  // deduplicate consecutive identical IDs (line 903)
  if (to_ack_message_ids_.empty() || to_ack_message_ids_.back() != message_id) {
    to_ack_message_ids_.push_back(message_id);
    constexpr size_t MAX_UNACKED_PACKETS = 100;     // line 907
    if (to_ack_message_ids_.size() >= MAX_UNACKED_PACKETS) {
      send_before(Time::now_cached());              // emergency flush
    }
  }
}
```

Trigger condition: only content-related messages (odd `seq_no`) are ACKed
(on_slice_packet, line 512-513):

```
if (info.seq_no & 1) { send_ack(info.message_id); }
```

At flush time (flush_packet, line 1015-1022), up to 8192 IDs are cut from
the queue and packed into a single `msgs_ack#62d6b459` service message,
which is serialized into the *same* encrypted container as queries, pings,
and salt requests — never as a standalone packet.

`force_ack()` (line 891-894) can externally reset the timer to zero, causing
the next flush to immediately include any pending ACKs.

### 1.2 Server behavior for unACKed messages

When the server delivers messages (updates, push notifications) to the client,
it monitors whether those messages were acknowledged. The server tracks this
via `msgs_state_req` / `msgs_state_info` / `msgs_all_info` /
`msg_detailed_info` / `msg_new_detailed_info` service messages (SessionConnection.cpp
comments, lines 150-170). Each info byte encodes: received (4), already ACKed
(+8), doesn't require ACK (+16), RPC being processed (+32), content response
generated (+64), other party knows received (+128).

If the client consistently fails to ACK, the server stops delivering updates
and eventually closes the connection. The exact timeout is server-side and
not documented, but TDLib empirically observes drops after ~60 seconds of
silence.

### 1.3 Teleframe gap

Teleframe **skips incoming `msgs_ack`** (correctly — these acknowledge
client-sent messages), but **never sends `msgs_ack`** for received server
messages. The `transientConstructorIds()` array (EncryptedConnection.php:536)
lists `msgs_ack` as transient, meaning it is silently consumed and ignored.

For Teleframe's current synchronous `call()` / `callBatch()` model where each
request blocks for its response, the server sees the RPC reply as implicit
acknowledgment. **Gap is zero for CLI/short-job use.** For persistent
connections (update polling, daemon mode), missing ACKs will cause server-side
drops.

---

## 2. Sequence Number (seqno) Tracking

### 2.1 TDLib's seqno counter

`AuthData::next_seq_no()` (AuthData.h:267-274):

```cpp
int32 next_seq_no(bool is_content_related) {
    int32 res = seq_no_;
    if (is_content_related) {
        res |= 1;         // make odd
        seq_no_ += 2;     // increment by 2
    }
    return res;
}
```

Rules:
- Content-related messages (queries that expect a response): odd seq_no (1, 3, 5, ...)
- Non-content messages (containers, ACKs, pings): even seq_no (0, 2, 4, ...)
- Counter starts at 0, monotonically increasing within a session
- The container's seq_no must be >= every inner message's seq_no

Server-enforced error codes (bad_msg_notification):
- **32**: seq_no too low — messages out of order
- **33**: seq_no too high
- **34**: even seq_no received where odd expected (content-related)
- **35**: odd seq_no received where even expected (non-content)

### 2.2 Teleframe implementation

`nextContentSeqNo()` returns `contentCounter += 2` (odd, strictly increasing).
`nextContainerSeqNo()` returns `contentCounter + 1` (even, counter not consumed).

**Verdict: Correctly implemented.** Matches TDLib exactly.

---

## 3. Container Messages (msg_container)

### 3.1 TDLib's flush_packet multiplexer

`SessionConnection::flush_packet()` (SessionConnection.cpp:922-1075) is the
single point where all outgoing traffic is assembled into one encrypted
packet:

1. **Pending queries** — up to `MAX_QUERY_COUNT = 1000` queries, total
   payload up to 2^15 (32768) bytes (line 958-967).
2. **Pending ACK IDs** — up to 8192 IDs (line 1022).
3. **Ping** — one `ping_delay_disconnect` if keepalive timer has fired.
4. **Future salts request** — `get_future_salts` with up to 64 salts.
5. **Resend/cancel answer** and **message state info** service queries.
6. **Auth key destroy** — `destroy_auth_key` if requested.

Everything is serialized through `PacketStorer<CryptoImpl>` which creates a
`msg_container#73f1f8dc` wrapping individual TL objects. The container has
naked wire format:

```
msg_container#73f1f8dc count:int
  { msg_id:long seqno:int bytes:int body:bytes } * count
```

Inner msg_ids must be strictly increasing and divisible by 4. The outer
container msg_id must be greater than all inner IDs.

### 3.2 Teleframe's callBatch

`callBatch()` (EncryptedConnection.php:300-346) packs N RPC query bodies
into one `msg_container` (line 357-376). It allocates inner IDs first
(strictly increasing), outer ID last. Size bounds: `MAX_BATCH_MESSAGES =
1020`, `MAX_BATCH_CONTAINER_BYTES = 32768`.

Incoming containers are parsed by `parseNakedContainer()` (line 630-664) and
`parseBareContainerMessages()` (line 434-448).

### 3.3 Gap: single-purpose containers

Teleframe only packs RPC queries into containers. TDLib packs queries
**plus** ACKs, pings, future salt requests, and state info into the same
container. This means Teleframe sends separate encrypted packets for service
traffic that TDLib piggybacks for free.

---

## 4. gzip_packed Handling

### 4.1 TDLib's two decompression paths

**Path 1 — inside rpc_result** (SessionConnection.cpp:264-272):
```cpp
case mtproto_api::gzip_packed::ID: {
  mtproto_api::gzip_packed gzip(parser);
  BufferSlice object = gzdecode(gzip.packed_data_);
  return callback_->on_message_result_ok(MessageId(req_msg_id), std::move(object), info.size);
}
```
Server compresses large RPC responses (e.g. `messages.getHistory` with many
results). TDLib decompresses and passes the raw bytes upward.

**Path 2 — standalone message** (SessionConnection.cpp:408-412):
```cpp
Status SessionConnection::on_packet(const MsgInfo &info, const mtproto_api::gzip_packed &gzip_packed) {
  BufferSlice res = gzdecode(gzip_packed.packed_data_);
  auto guard = set_buffer_slice(&res);
  return on_slice_packet(info, res.as_slice());
}
```
A bare `gzip_packed` arrives as a push message (not inside rpc_result).
TDLib decompresses and re-dispatches through the full `on_slice_packet()`
handler, which can recursively parse containers, detect `rpc_result`, or
route updates.

### 4.2 Teleframe implementation

Teleframe handles Path 1 via `unwrapResultIfGzipped()` (line 112-123):
checks `_` === `gzip_packed`, calls `gzdecode()`, decodes via
`TLDecoder::decodeObject()`. Both `receiveDecodedResponse()` and
`absorbBatchBody()` invoke this.

Path 2 (standalone gzip push) is **not handled**. In `receiveDecodedResponse()`
the outer constructor ID is compared against the transient list, then
`msg_container` (0x73f1f8dc), then decoded as a generic TL object. If a
standalone `gzip_packed` arrives, it would fall through to the generic path
and likely fail to match `rpc_result`.

**Impact:** Low for CLI use (server rarely gzip-compresses push messages).
Would matter for persistent connections receiving large update batches.

---

## 5. Quick ACK / Intermediate Transport

### 5.1 How quick ACK works

Quick ACK is a **transport-level** (not MTProto-level) optimization. When the
server receives a client message on an intermediate-framed connection, it can
acknowledge receipt without sending a full encrypted packet:

**Client sends** (TcpTransport.cpp / RawConnection.cpp:62-86):
- When `use_quick_ack` is true, bit 31 of the intermediate transport's
  length field is set: `size |= 1u << 31`.
- The `quick_ack_token` is the parent container's `message_id`.
- Stored in `quick_ack_to_token_` map for later lookup.

**Server responds** (RawConnection.cpp:164-176):
- If the 4-byte frame header has bit 31 set (`data_size & (1u << 31)`), it
  is a quick ACK, not a length header.
- Lower 31 bits = the echoed `quick_ack_token`.
- `on_quick_ack()` resolves the token via `quick_ack_to_token_` map, then
  calls `on_message_ack()` (SessionConnection.cpp:757-759).

**Supported transports:** intermediate (OldTransport) and obfuscated TCP,
but **not** HTTP or abridged.

### 5.2 Teleframe gap

Teleframe uses abridged framing (`FrameCodec`), where the length prefix is
a varint with no spare bits for the quick ACK flag. Quick ACK is
architecturally incompatible with abridged framing.

**No action needed** for the current synchronous CLI model. If persistent
connections are added, switching to intermediate framing (4-byte length) or
obfuscated TCP would unlock quick ACK support.

---

## 6. Server-Sent Service Messages

### 6.1 bad_msg_notification

`bad_msg_notification#a7eff811` carries `bad_msg_id`, `bad_msg_seqno`, and
`error_code`. TDLib handles each code distinctly (SessionConnection.cpp:324-387):

| Code | Name | TDLib action | Teleframe action |
|---|---|---|---|
| 16 | MsgIdTooLow | Resend the failed message (time auto-corrects) | Throws RuntimeException |
| 17 | MsgIdTooHigh | Fail session (reset time difference, close) | Throws RuntimeException |
| 18 | MsgIdMod4 | Fatal: msg_id not divisible by 4 | Throws RuntimeException |
| 19 | MsgIdCollision | Fatal: container ID collides with older message | Throws RuntimeException |
| 20 | MsgIdTooOld | Resend the failed message | Throws RuntimeException |
| 32 | SeqNoTooLow | Fatal: session broken | Throws RuntimeException |
| 33 | SeqNoTooHigh | Fatal: session broken | Throws RuntimeException |
| 34 | SeqNoNotEven | Fatal: even seqno on irrelevant message | Throws RuntimeException |
| 35 | SeqNoNotOdd | Fatal: odd seqno on relevant message | Throws RuntimeException |
| 48 | InvalidSalt | Handled by bad_server_salt (separate message) | Handled via bad_server_salt path |
| 64 | InvalidContainer | Fatal: malformed container | Throws RuntimeException |

**Key gap:** Codes 16 and 20 are recoverable — TDLib resends the failed
message automatically. Teleframe throws on all non-salt codes, treating
recoverable errors the same as fatal ones. This matters for persistent
connections where the server may legitimately reject messages that are
slightly too old or have a stale msg_id after clock drift.

### 6.2 bad_server_salt

`bad_server_salt#edab447b` carries the new salt in `new_server_salt`. TDLib
updates the salt via `auth_data_->set_server_salt()` and then resends the
failed message (SessionConnection.cpp:389-397). Teleframe correctly handles
this: `refreshServerSalt()` updates both the connection and the session
data, and the `call()` loop retries with the fresh salt.

### 6.3 new_session_created

`new_session_created#9ec20908` contains `first_msg_id`, `unique_id`, and
`server_salt`. Server semantics:

- All messages with ID < `first_msg_id` should be resent.
- `unique_id` identifies the session uniquely (dedup guard).
- The salt is replaced.

TDLib (SessionConnection.cpp:309-322):
- Updates the salt.
- Deduplicates via `unique_id`.
- Maps `first_msg_id` back to container ID if it matches a service query.
- Notifies the session manager to trigger a difference resync.

Teleframe (EncryptedConnection.php:468-471, 566-576):
- Updates the salt correctly.
- Treats the message as transient (skipped).

**Gap:** No resend of pre-`first_msg_id` messages. No `unique_id` dedup.
For CLI use, this is fine because each connection sends immediately after
connecting. For persistent connections, a difference resync is required.

### 6.4 msgs_ack (server-to-client)

`msgs_ack#62d6b459 msg_ids:Vector long` — the server acknowledges receipt
of client messages. TDLib processes these via `on_packet(msgs_ack)` (line
399-406), calling `callback_->on_message_ack()` for each ID, which
removes the message from the "awaiting acknowledgment" tracking structure.

Teleframe skips these as transient (transientConstructorIds). This is
**correct** — acknowledging server ACKs is unnecessary for synchronous
CLI use.

### 6.5 http_wait

`http_wait` is a client-to-server message used only in HTTP long-poll
transport mode (SessionConnection.cpp:932-946). It tells the server to hold
the HTTP response open for up to `max_wait` milliseconds, delivering
messages as they arrive. Parameters: `max_delay` (max per-message delay),
`max_after` (delay after first message), `max_wait` (total wait time).

Teleframe does not use HTTP transport (abridged TCP only), so `http_wait`
is **not applicable**.

### 6.6 future_salts

`future_salts#ae500895` contains a vector of upcoming salt values with
validity windows. TDLib proactively fetches these (`get_future_salts`
with `num = 64`) every 60 seconds when the current salt is nearing expiry
(SessionConnection.cpp:949-956). The salts are stored sorted by
`valid_since` and automatically rotated via `update_salt()` (AuthData.cpp:169-175).

Teleframe does not proactively fetch future salts, relying entirely on
`bad_server_salt` for salt rotation. For short-lived connections this is
sufficient. For long-lived connections, proactive salt fetching avoids the
overhead of one round-trip per salt change.

### 6.7 msg_state_info / msgs_all_info / msg_detailed_info

These are server-initiated status reports about message delivery state:

- `msgs_state_info`: Response to a client `msgs_state_req` query. Contains
  one status byte per queried msg_id.
- `msgs_all_info`: Voluntary broadcast of message status for all unACKed
  messages.
- `msg_detailed_info`: Status of a specific message (one-at-a-time).
- `msg_new_detailed_info`: Same but without the original msg_id.

TDLib routes these through `on_msgs_state_info()` (line 462-472) and
`on_message_info()` callback. This feeds the "already ACKed" detection
so TDLib can avoid redundant ACKs.

Teleframe does not implement any of these. Not needed for synchronous CLI;
relevant only for persistent connections with update delivery guarantees.

### 6.8 Message ID duplicate checking

TDLib maintains a **1000-entry circular buffer** of recently-received message
IDs (`MessageIdDuplicateChecker<1000>` in AuthData.h:301). Before processing
any incoming packet, `check_message_id_duplicates()` (AuthData.cpp:19-46) is
called:

- If the ID is already in the buffer, the message is a duplicate and ignored
  (error code 1).
- If the ID is older than the oldest entry in a full buffer, it is too old
  to process (error code 2 — triggers session failure).
- Otherwise, the ID is inserted in sorted position. When the buffer fills,
  the lower half is discarded (compaction).

This prevents replay attacks and double-processing of server retransmissions.

Teleframe does **not** implement message ID deduplication. For synchronous
single-request connections this is unnecessary. For persistent connections
receiving updates, duplicate message IDs from server retransmissions could
cause double-processing.

---

## 7. Two-Connection Model

### 7.1 TDLib's session lanes

TDLib uses **four session lanes** per DC (ConnectionManager.h), distinguished
by `ConnectionToken` mode:

| Lane | Mode | Purpose |
|---|---|---|
| Main | 1 (regular) | RPC queries, updates, general traffic |
| Upload | 1 | File uploads (`upload.saveFilePart`) |
| Download | 1 | File downloads (`upload.getFile`) |
| Download Small | 1 | Small file fetches (< 1 MiB), lower latency |

Each lane has its own `SessionConnection` with independent:
- `seq_no` counter (in `AuthData`)
- `to_ack_message_ids_` queue
- `to_send_` pending query buffer
- Ping/pong state
- Salt (shared from `AuthData` but ACK buckets are per-connection)

The `ConnectionManager` tracks active connections via reference-counted
tokens (ConnectionManager.cpp:12-27). When a token is acquired
(`inc_connect`), the connection manager's event loop starts; when all
tokens for a mode are released, it stops.

**ACK partitioning:** Each `SessionConnection` maintains its own
`to_ack_message_ids_` vector. ACKs for messages received on the download
connection are sent back on the *same* download connection, not the main
connection. This means ACKs are naturally partitioned by lane.

Premium accounts get boosted lane counts (SessionInfo references in TDLib
dispatchers): main=4, upload=2, download=2 → premium: main=8, upload=8,
download=8.

### 7.2 Teleframe's single-connection model

Teleframe maintains one `EncryptedConnection` per `Client` instance
(Client.php:49). There is no lane separation, no connection pool, and no
ACK partitioning. The `Client` class manages the connection lifecycle:
`ensureConnection()` creates one connection, `close()` destroys it.

For CLI/short-job use, a single connection is sufficient because there is
no concurrent file transfer or background polling. For a production daemon
that simultaneously polls for updates AND transfers files, a single
connection creates head-of-line blocking: a large file download stalls
the update polling connection.

---

## Comparison Table

| Concern | TDLib | Teleframe | Gap severity |
|---|---|---|---|
| ACK sending (client->server) | Delayed 30s batch, max 100 before emergency flush, piggybacked onto outgoing packets, 8192 IDs per flush | Not implemented | **Low** (CLI); **High** (persistent) |
| ACK receiving (server->client) | Processes via `on_message_ack`, feeds dedup tracker | Skipped as transient | None (correct) |
| Seq_no tracking | Odd=content, even=non-content, monotonically increasing | Same (correct) | None |
| Container packing | Queries + ACKs + pings + salts + services in one packet | Queries only (callBatch) | Low (optimization) |
| gzip_packed (in rpc_result) | Decompress + pass upward | Same (unwrapResultIfGzipped) | None |
| gzip_packed (standalone push) | Decompress + re-dispatch through full handler | Not handled | Low (CLI); Medium (persistent) |
| Quick ACK | Transport-level bit 31, intermediate/obfuscated framing | Not supported (abridged) | None (CLI) |
| bad_msg_notification (16, 20) | Auto-resend recoverable messages | Throws on all codes | Medium |
| bad_msg_notification (17, 18, 19, 32-35, 64) | Fatal: fail session | Throws | None (correct) |
| bad_server_salt | Update salt + resend | Same (correct) | None |
| new_session_created | Salt + dedup + session notification + resend | Salt only | Low (CLI); Medium (persistent) |
| future_salts | Proactive fetch every 60s | Not implemented | Low (CLI); Medium (persistent) |
| msg_state_info / msgs_all_info | Full status tracking | Not implemented | None (CLI) |
| Message ID dedup | 1000-entry circular buffer, sorted insert, compaction | Not implemented | None (CLI); Medium (persistent) |
| Transport type | Obfuscated TCP + intermediate + abridged + HTTP | Abridged only | Low (works for handshake + early auth) |
| Two-connection model | 4 lanes per DC (main/upload/download/download_small), premium-boosted | Single connection per client | None (CLI); High (daemon) |
| Ping keepalive | RTT-adaptive (0.5x RTT may ping, 1x must ping, 2.5x/3.5x disconnect) | Fixed 45s idle threshold | Low (works, less adaptive) |

---

## Recommendations

### W1 -- Send msgs_ack for persistent connections
**Priority: P0 (critical for daemon mode) | Effort: M**

Implement delayed ACK batching in `EncryptedConnection`:
- Add `$pendingAckIds: SplFixedArray` (bounded to 100 entries) to
  `EncryptedConnection`.
- On each `on_slice_packet`-equivalent (odd seq_no received), push the msg_id.
- In `call()` / `callBatch()`, before sending: if pending ACKs exist and the
  30s timer has elapsed OR the queue exceeds 100, serialize a
  `msgs_ack#62d6b459` TL body and prepend it to the outgoing container.
- Piggyback onto outgoing traffic; never send standalone ACK packets.
- PHP note: The `TLSerializer` can encode `msgs_ack` as
  `packInt(0x62d6b459) . packInt(count) . implode(array_map('packLong', $ids))`.
  Append this body to the `encodeBatchContainer` call.

### W2 -- Handle recoverable bad_msg_notification codes (16, 20)
**Priority: P1 (hardening) | Effort: S**

In `EncryptedConnection::call()`, the `bad_msg_notification` handler (line
225-231) currently throws on all codes. Add a check:
```php
if (in_array((int)($result['error_code'] ?? 0), [16, 20], true)) {
    // MsgIdTooLow / MsgIdTooOld: resend with fresh msg_id
    $this->refreshServerSalt($this->serverSalt); // time auto-corrects
    continue; // retry loop (maxAttempts = 2 already covers this)
}
```
This matches TDLib's `on_message_failed` behavior for codes 16/20, where
the message is resent with a fresh msg_id derived from the updated time
difference.

### W3 -- Implement message ID duplicate checking
**Priority: P1 (hardening for persistent connections) | Effort: S**

Add a bounded `SplFixedArray`-backed circular buffer (1000 entries) to
`PacketCodec` or a new `MessageIdDedup` value object. After decrypting
each packet in `receiveDecodedResponse()`, check the decrypted `message_id`
against the buffer before processing. For CLI use, add it as a no-op-seam
(called but not yet blocking) so the infrastructure exists when persistent
connections are added.

PHP note: A sorted-insert `SplFixedArray` of size 2000 (matching TDLib's
`array<MessageId, 2*max_size>`) with binary search (`array_search` + manual
insert) and periodic compaction (discard lower half on overflow) is idiomatic.

### W4 -- Handle standalone gzip_packed push messages
**Priority: P2 (nice-to-have) | Effort: S**

In `receiveDecodedResponse()`, add a check before the generic decode path:
```php
if ($id === TLRegistry::id('gzip_packed')) {
    $offset = 0;
    $push = TLDecoder::decodeObject($payload, $offset);
    $inflated = gzdecode($push['packed_data']);
    $inflatedOffset = 0;
    $decoded = TLDecoder::decodeObject($inflated, $inflatedOffset);
    // Re-dispatch: if rpc_result, return; if container, parse; else skip
}
```
This matches TDLib's standalone `gzip_packed` handler (line 408-412) which
decompresses and re-dispatches through `on_slice_packet()`.

### W5 -- Consolidate service messages into containers
**Priority: P2 (optimization) | Effort: M**

When `call()` or `callBatch()` has pending ACKs, include a `msgs_ack`
body in the same `msg_container` as the RPC queries. This eliminates
separate round-trips for ACK-only traffic. TDLib's `flush_packet()` always
assembles one container per flush; Teleframe should do the same.

Implementation: modify `encodeBatchContainer()` to accept an optional
`$ackIds` parameter. If non-empty, prepend a `msgs_ack` body to the inner
message list (with even seq_no, as non-content-related). Also wire the
existing `ping_delay_disconnect` call into the same container when keepalive
is due, matching TDLib's ping-inside-container behavior.

### W6 -- Proactive future_salts fetching
**Priority: P2 (optimization for long sessions) | Effort: S**

Add a `get_future_salts` call to `Client::call()` (or a background task
in the daemon), triggered when the current salt's validity window is
nearing expiry. TDLib fetches every 60 seconds (SessionConnection.cpp:952)
when `auth_data_->need_future_salts()` returns true.

PHP note: Store future salts in `SessionData` (add `futureSalts` property
as `array<int, array{salt: int, valid_since: int, valid_until: int}>`).
The next `call()` checks salt validity and, if expired, fetches 64 future
salts and rotates automatically, avoiding one round-trip per `bad_server_salt`.

### W7 -- Two-connection model for daemon mode
**Priority: P0 (critical for daemon) | Effort: L**

For a production daemon that polls updates AND transfers files, introduce a
connection pool with two lanes:

- **Main lane:** RPC queries, update polling, general traffic.
- **I/O lane:** File uploads/downloads (future `upload.getFile` /
  `upload.saveFilePart`).

Each lane owns an independent `EncryptedConnection` with its own `seq_no`
counter and `to_ack_message_ids_` queue. The `Client` class gains a
`getConnection(string $lane): EncryptedConnection` factory that lazily
creates and caches per-lane connections. ACKs for messages received on
the I/O lane stay on the I/O lane (matching TDLib's per-connection ACK
partitioning).

PHP note: Extend `Client` with `$connections: array<string,
EncryptedConnection>` keyed by lane name. Each lane has its own
`SessionData` (same auth key, different session ID). The pool manages
ping/keepalive independently per lane.

### W8 -- Obfuscated transport with intermediate framing
**Priority: P2 (future-proofing) | Effort: L**

Implement `ObfuscatedTransport` as a new `FrameCodec` mode:
- Generate a 64-byte random header with transport marker (`0xdddddddd`
  for padded) and DC ID in bytes 56-63.
- Derive AES-CTR key/IV from the first 56 bytes.
- Frame messages using intermediate format (4-byte length), encrypted
  in-place by the AES-CTR stream.

This unlocks: (a) DPI resistance, (b) quick ACK support via bit 31,
(c) compatibility with modern production DCs that may deprecate raw
abridged framing. The `FrameCodec` class gains a static factory:
`createAbridged()` / `createObfuscated(string $secret)` returning the
appropriate framing implementation.

---

## Key Source Files Referenced

### TDLib (C++)
- `td/mtproto/SessionConnection.h` / `.cpp` — Wire protocol state machine: ACK queuing, container assembly, all service message handlers, ping lifecycle
- `td/mtproto/AuthData.h` / `.cpp` — Auth key, salt rotation, seq_no counter, message ID generation with randomization, duplicate checking (1000-entry circular buffer)
- `td/mtproto/MessageId.h` — Strong uint64 wrapper with ordering
- `td/mtproto/PacketStorer.h` / `CryptoStorer.h` — Template-based TL storer for encrypted transport serialization
- `td/mtproto/RawConnection.h` / `.cpp` — Socket I/O, quick ACK token resolution, MTProto error dispatch (-404, -429)
- `td/mtproto/TcpTransport.h` / `.cpp` — Intermediate framing, quick ACK bit manipulation
- `td/mtproto/ConnectionManager.h` / `.cpp` — Token-based connection lifecycle, mode 1 = main, mode 2 = proxy
- `td/mtproto/PingConnection.h` / `.cpp` — Ping-pong RTT measurement, req_pq probe
- `td/mtproto/MtprotoQuery.h` — Outgoing query structure (gzip_flag, quick_ack, invoke_after_message_ids)

### Teleframe (PHP)
- `src/Core/MTProto/Connection/EncryptedConnection.php` — Wire protocol: call/callBatch, container encode/decode, transient skipping, salt refresh
- `src/Core/MTProto/Client.php` — Connection lifecycle, call/callMany, keepalive, DC IPs
- `src/Core/MTProto/SessionData.php` — Session DTO (dcId, authKey, serverSalt, seqNo)
- `src/Core/MTProto/Crypto/PacketCodec.php` — AES-256-IGE encrypt/decrypt, msg_key computation
- `src/Core/MTProto/Transport/FrameCodec.php` — Abridged framing (send/receive)
- `src/Core/MTProto/Transport/StreamSocket.php` — Low-level socket I/O
