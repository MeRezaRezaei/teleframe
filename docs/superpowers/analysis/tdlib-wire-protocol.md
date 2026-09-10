# TDLib Wire Protocol Edge Cases Analysis

## Overview

This document analyzes how TDLib (the official Telegram client library) handles
MTProto wire protocol edge cases that can cause connection drops or data loss
when mishandled. Each section describes TDLib's approach, identifies gaps in
Teleframe's current implementation, and provides actionable recommendations.

Source: TDLib HEAD (shallow clone, September 2026).
Teleframe reference: `src/Core/MTProto/Connection/EncryptedConnection.php`,
`Client.php`, `PacketCodec.php`, `FrameCodec.php`.

---

## Message Acknowledgment (msg_ack)

### TDLib behavior

TDLib implements **delayed, batched acknowledgment** of received messages:

- When a content-related message arrives (odd `seq_no`), its `message_id` is
  appended to `to_ack_message_ids_` (SessionConnection.cpp:512-513, 897-910).
- ACKs are NOT sent immediately. A 30-second timer (`ACK_DELAY = 30`) is set on
  the first queued ACK (line 899-900).
- Deduplication: consecutive identical message IDs are not re-queued (line 903).
- **Emergency flush**: when the unACKed queue exceeds `MAX_UNACKED_PACKETS`
  (100), `force_send()` is called immediately (line 907-908).
- At flush time, up to **8192 message IDs** are packed into a single
  `msgs_ack` service message, piggybacked onto the same encrypted packet
  containing queries, pings, and other service messages (line 1022).
- `force_ack()` can be called externally to flush pending ACKs immediately
  (line 891-894).

Key design principle: ACKs are batched for efficiency and piggybacked onto
outgoing traffic, never sent as standalone packets.

### Teleframe gap

Teleframe **does not send `msgs_ack` at all**. It correctly recognizes and
skips incoming `msgs_ack` from the server (transient constructor in
`transientConstructorIds()`), but the client never acknowledges received
server messages. For Teleframe's current synchronous CLI/short-job use case
where each `call()` sends one request and waits for one response, this is
low-risk because the server sees the response as implicit acknowledgment.
However, for any future persistent connection (update polling, long-lived
sessions), missing ACKs will cause the server to drop the connection after
a timeout.

### Recommendation

**For CLI/short-job use (current):** No action needed. Implicit acknowledgment
via rpc_result suffices.

**For persistent connections (future):** Implement delayed ACK batching:
1. Accumulate received content-related message IDs in a bounded queue.
2. Send `msgs_ack` after 30 seconds or when the queue exceeds ~100 entries.
3. Piggyback ACKs onto outgoing traffic (queries, pings) to avoid standalone
   packets.

---

## Sequence Number (seqno) Tracking

### TDLib behavior

Sequence numbers are managed by `AuthData::next_seq_no()` (AuthData.h:267-274):

```cpp
int32 next_seq_no(bool is_content_related) {
    int32 res = seq_no_;
    if (is_content_related) {
        res |= 1;        // make odd
        seq_no_ += 2;    // increment by 2 for next content message
    }
    return res;
}
```

Rules enforced by the server (bad_msg_notification error codes):
- Code 32: seq_no too low (messages must arrive in order)
- Code 33: seq_no too high
- Code 34: even seq_no on a content-related message (odd expected)
- Code 35: odd seq_no on a non-content message (even expected)

TDLib tracks the counter as `seq_no_` starting at 0. Content messages get odd
values (1, 3, 5, ...); non-content messages (containers, acks) get even values
(0, 2, 4, ...). The seq_no is monotonically increasing within a session.

### Teleframe implementation

Teleframe matches TDLib exactly:

- `nextContentSeqNo()` returns `contentCounter += 2` (odd, strictly increasing).
- `nextContainerSeqNo()` returns `contentCounter + 1` (even, not consumed).
- Containers use even seq_no; individual messages inside use odd seq_no.

**Verdict: Correctly implemented.** No gap.

---

## Container Messages (msg_container)

### TDLib behavior

TDLib uses `msg_container` (constructor `0x73f1f8dc`) as the **universal
multiplexing envelope**. The `flush_packet()` method (SessionConnection.cpp:922-
1075) packs multiple outgoing items into a single encrypted packet:

1. **Pending queries** (up to `MAX_QUERY_COUNT = 1000`, total size up to 2^15
   bytes).
2. **Acknowledgment IDs** (up to 8192 IDs).
3. **Ping** (one ping_delay_disconnect if keepalive is due).
4. **Future salts request** (up to 64 salts).
5. **Resend/cancel answer requests** (service queries).
6. **Message state info requests**.

All of these are serialized into one `PacketStorer<CryptoImpl>` which
internally creates a `msg_container` wrapping individual service messages
(msgs_ack, ping, get_future_salts, etc.) alongside query messages.

Container structure on the wire:
```
msg_container#73f1f8dc count:int
  [msg_id:long seqno:int bytes:int body:bytes] * count
```

Inner message IDs must be strictly increasing and divisible by 4. The outer
container message ID must be greater than all inner IDs.

### Teleframe implementation

Teleframe's `callBatch()` (EncryptedConnection.php:300-346) sends multiple
request bodies inside a single `msg_container`:

- Outer container gets even seq_no via `nextContainerSeqNo()`.
- Each inner message gets odd seq_no via `nextContentSeqNo()`.
- Inner IDs are allocated first (strictly increasing); outer ID last.
- Size bounds: `MAX_BATCH_MESSAGES = 1020`, `MAX_BATCH_CONTAINER_BYTES = 32768`.

Parsing incoming containers: `parseNakedContainer()` (line 630-664) correctly
handles `msg_container` payloads, iterating inner messages and routing
`rpc_result` bodies by `req_msg_id`.

### Gap: Single-purpose containers

Teleframe only packs **RPC queries** into containers. TDLib packs queries
**plus** ACKs, pings, future salt requests, and state info requests into the
same container. This means Teleframe sends more separate encrypted packets
for the same logical workload.

### Recommendation

**Low priority for CLI use.** For long-lived connections, consider
consolidating outgoing service messages (ACKs, pings) into the same container
as query messages to reduce round-trips.

---

## gzip_packed Handling

### TDLib behavior

GDLib handles `gzip_packed#3072cfa1` in two contexts:

1. **Inside `rpc_result`** (line 264-272): When a server response to an RPC
   query is gzip-compressed, TDLib decompresses it and passes the raw bytes
   to `on_message_result_ok()`. This handles the case where the server
   compresses large API responses (e.g., messages.getHistory with many
   results).

2. **As a standalone message** (line 408-412): When a bare `gzip_packed`
   arrives (not wrapped in `rpc_result`), TDLib decompresses it and
   re-processes the resulting bytes through `on_slice_packet()`, which
   dispatches to the appropriate handler (including recursive container
   parsing).

Both paths use `gzdecode()` from `td/utils/Gzip.h`.

### Teleframe implementation

Teleframe handles both cases:

1. Inside `rpc_result`: `unwrapResultIfGzipped()` (line 112-123) checks for
   `gzip_packed` and decompresses via `gzdecode()`, then decodes via
   `TLDecoder::decodeObject()`.

2. Inside `receiveBatchResults()` -> `absorbBatchBody()` (line 501): also
   calls `unwrapResultIfGzipped()` for each decoded rpc_result body.

**Verdict: Correctly implemented.** Both decompression paths are covered.

Note: Teleframe does not handle the **server-initiated gzip** case where a
raw `gzip_packed` arrives as a push message (not inside `rpc_result`). For
CLI/short-job use this is acceptable since the server only compresses
responses to specific queries; push messages (updates) are typically small
enough to not be gzip-compressed.

---

## Quick ACK Protocol

### TDLib behavior

Quick ACK is a **transport-level optimization** that lets the server
acknowledge receipt of a client message without sending a full encrypted
packet:

**Sending (client -> server):**
- The client sets bit 31 of the intermediate transport length field
  (`size |= 1 << 31`) when sending a packet that should trigger quick ACK
  (TcpTransport.cpp:50-57).
- The `use_quick_ack` flag on individual queries propagates to the transport
  layer (SessionConnection.cpp:1025-1037).
- The `quick_ack_token` is the parent container's `message_id` packed into
  the flag.

**Receiving (server -> client):**
- When reading from the transport, if the 4-byte header has bit 31 set
  (`data_size & (1u << 31)`), it is a quick ACK, not a length header
  (TcpTransport.cpp:30-36).
- The lower 31 bits contain the `quick_ack_token` (the echoed message_id).
- `on_quick_ack()` calls `on_message_ack()` to mark the original message
  as acknowledged (SessionConnection.cpp:757-759).

Quick ACK is supported by both `OldTransport` (abridged without padding) and
`ObfuscatedTransport` (obfuscated TCP), but NOT by HTTP transport.

### Teleframe implementation

Teleframe uses abridged transport only (`FrameCodec`), which does not support
quick ACK. The abridged frame format uses varint length/4 with no spare bits
for the quick ACK flag.

### Recommendation

**No action needed.** Quick ACK primarily benefits long-lived polling
connections where reducing encrypted round-trips matters. Teleframe's
synchronous CLI model sends one request and blocks for the full encrypted
response anyway. The additional latency of a full response vs. quick ACK is
negligible for single-call workloads.

If persistent connections are added later, consider switching to intermediate
framing (4-byte length header) which supports quick ACK via bit 31.

---

## New Session Info

### TDLib behavior

`new_session_created#9ec20908` is a server notification sent when the server
creates a new session (typically after a reconnect or session timeout):

- Contains `first_msg_id`, `unique_id`, and `server_salt`.
- TDLib updates the server salt from this message (SessionConnection.cpp:309-
  322).
- The callback `on_new_session_created()` notifies the session manager.
- TDLib checks for duplicate notifications using the `unique_id` field.
- If `first_message_id` matches a pending service query, the container's
  message_id is used instead (for correct resend routing).
- The server implicitly requests resend of all messages with IDs less than
  `first_msg_id`.

### Teleframe implementation

Teleframe correctly handles `new_session_created`:

1. In `receiveDecodedResponse()` (line 566-576): when the constructor ID
   matches, the salt is updated via `refreshServerSalt()` and the message
   is treated as transient (skipped, not served as the RPC response).

2. In `receiveBatchResults()` -> `absorbBatchBody()` (line 468-471): same
   behavior — salt updated, body absorbed as transient.

### Gap: No resend of pre-first_msg_id messages

When `new_session_created` arrives, the server expects messages older than
`first_msg_id` to be resent. TDLib handles this through its session manager
callback. Teleframe's `call()` does not maintain a history of unsent/failed
messages, so if the server rejects older messages with `bad_msg_notification`
code 20 ("message too old"), they are simply lost.

### Recommendation

**Low priority for CLI use.** In practice, a fresh Teleframe connection sends
one request immediately after connecting, so it is unlikely to have old
unsent messages. For robustness, `bad_msg_notification` code 20 could trigger
automatic resend of the failed query (which `call()` already does for
`bad_server_salt` but not for "too old").

---

## Transport Type Selection

### TDLib behavior

TDLib supports three transport types (TransportType.h):

| Type | Init byte | Frame format | Quick ACK | Random padding |
|---|---|---|---|---|
| `Tcp` (OldTransport) | `0xef` | 4-byte length/4 | Yes | No |
| `ObfuscatedTcp` (ObfuscatedTransport) | 64-byte random | AES-CTR encrypted, then intermediate frame | Yes | Depends on secret |
| `Http` | Custom | HTTP POST bodies | No | No |

ObfuscatedTcp wraps an intermediate frame inside AES-CTR encryption using a
key derived from the random 64-byte header. The first 56 bytes of the header
are sent in the clear; bytes 56-63 contain the transport marker (`0xdddddddd`
for padded, `0xeeeeeeee` for unpadded) and the DC ID.

Transport selection is driven by the connection parameters and proxy
configuration. The obfuscated header prevents DPI (Deep Packet Inspection)
from identifying MTProto traffic.

### Teleframe implementation

Teleframe uses abridged framing exclusively:

- Init byte: `0xef` (FrameCodec::writeInit).
- Frame: varint(length/4) prefix, no encryption at the transport layer.
- No obfuscation, no TLS emulation.

The commented note in FrameCodec (line 16-17) states: "intermediate framing
was observed being silently dropped (2026-08)." This is expected — production
Telegram DCs expect obfuscated transport for regular connections. The abridged
transport with `0xef` init works for the **unencrypted handshake** phase and
for **old-style** connections, but modern DCs prefer obfuscated transport.

### Recommendation

**Current abridged transport works for handshake + early auth.** If
connections are being dropped by modern DCs, consider implementing
obfuscated transport with intermediate framing. This would also enable quick
ACK support.

---

## Comparison with Teleframe

| Feature | TDLib | Teleframe | Gap |
|---|---|---|---|
| **ACK sending** | Delayed 30s batch, piggybacked | Not implemented | Significant for persistent connections |
| **ACK receiving** | Processes incoming msgs_ack | Recognizes but skips | Acceptable (server ACKs for client queries) |
| **Seq_no tracking** | Odd/even, monotonically increasing | Same | Correct |
| **Container packing** | Queries + ACKs + pings + services | Queries only | Optimization opportunity |
| **gzip_packed** | In rpc_result + standalone push | In rpc_result + batch bodies | Minor: standalone push not handled |
| **Quick ACK** | Transport-level bit 31 | Not supported | Acceptable for CLI use |
| **new_session_created** | Salt update + resend + dedup | Salt update only | No resend of old messages |
| **Transport** | Obfuscated TCP + intermediate | Abridged | Works for handshake; may fail on production DCs for regular traffic |
| **bad_msg_notification** | Full error code handling (16-64) | Code 48 only (bad_server_salt) | Codes 16/20 could auto-resend; others terminate |
| **pong handling** | RTT tracking, time delta reset | ping_id verification | Teleframe is simpler but correct |
| **msg_state_info** | Full implementation | Not implemented | Not needed for CLI |
| **future_salts** | Proactive fetching | Not implemented | Minor: salt validity handled by bad_server_salt |
| **destroy_auth_key** | Full lifecycle | Not implemented | Not needed for CLI |
| **Message dedup** | 1000-entry circular buffer | Not implemented | Not needed for synchronous calls |

---

## Recommendations

### Priority 1: No changes required

The current Teleframe wire implementation is **correct and sufficient** for
its designed use case: synchronous CLI calls, short batch jobs, and schema
operations. The blocking `call()` / `callBatch()` model means:

- Each request gets exactly one response (no multiplexing needed).
- The server sees the response as implicit acknowledgment.
- Container messages work correctly for batch operations.
- gzip_packed is handled for all server responses.
- bad_server_salt triggers automatic resend.
- ping/pong keepalive prevents idle connection drops.

### Priority 2: Hardening for production use

If Teleframe is used for **persistent connections** (update polling, long-
running daemons), these gaps should be addressed:

1. **Send `msgs_ack`**: Implement delayed ACK batching (30s timer, max 100
   before flush, piggyback onto outgoing traffic). Without this, the server
   will drop idle connections that receive updates but never acknowledge them.

2. **Handle `bad_msg_notification` code 20 ("message too old")**: Auto-resend
   the failed query rather than throwing. Codes 16 ("msg_id too low") could
   also benefit from auto-resend.

3. **Consider obfuscated transport**: If abridged connections are being
   dropped by modern DCs, implement `ObfuscatedTransport` with intermediate
   framing. This also unlocks quick ACK.

### Priority 3: Nice-to-have optimizations

1. **Consolidate service messages into containers**: When sending queries,
   piggyback pending ACKs and pings into the same `msg_container` to reduce
   round-trips.

2. **Message dedup buffer**: For update polling, maintain a circular buffer
   of recently-seen message IDs to detect and skip duplicate deliveries.

3. **`future_salts` fetching**: Proactively request future salts to avoid
   `bad_server_salt` errors during long sessions.

---

## Key Source Files Referenced

### TDLib (C++)
- `td/mtproto/SessionConnection.h` / `.cpp` -- Main wire protocol state machine
- `td/mtproto/AuthData.h` -- Auth key, salt, seq_no, message_id management
- `td/mtproto/Transport.h` -- Transport read/write with quick ACK support
- `td/mtproto/TcpTransport.h` / `.cpp` -- Intermediate framing, obfuscated TCP
- `td/mtproto/PacketInfo.h` -- Packet metadata (session_id, msg_id, seq_no)
- `td/mtproto/MtprotoQuery.h` -- Outgoing query structure (gzip_flag, quick_ack)
- `td/mtproto/PacketStorer.h` -- Container serialization template
- `td/mtproto/MessageId.h` -- MessageId value type
- `td/mtproto/TransportType.h` -- Transport type enum
- `td/mtproto/IStreamTransport.h` -- Transport interface (read_next, quick_ack)

### Teleframe (PHP)
- `src/Core/MTProto/Connection/EncryptedConnection.php` -- Wire protocol
- `src/Core/MTProto/Client.php` -- Connection lifecycle, call/callMany
- `src/Core/MTProto/Crypto/PacketCodec.php` -- AES-IGE encrypt/decrypt
- `src/Core/MTProto/Transport/FrameCodec.php` -- Abridged framing
- `src/Core/MTProto/Transport/StreamSocket.php` -- Socket I/O
