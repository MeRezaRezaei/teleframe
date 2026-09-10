# TDLib Auth Key Lifecycle Analysis

Phase 0-B of the TDLib counter-measures study. Source: `tdlib/td` master
(analyzed 2026-09-10; key files: `td/mtproto/{Handshake,AuthKey,AuthData,DhHandshake,KDF,SessionConnection}.*`,
`td/telegram/net/{Session,AuthDataShared,AuthKeyState,TempAuthKeyWatchdog}.*`).

Telegram MTProto auth keys are **permanent, per-DC 256-byte DH shared
secrets**. They do not expire on their own; they are rotated only on explicit
error signals (`-404 auth_key_unknown`, `ENCRYPTED_MESSAGE_INVALID`) or
logout. TDLib additionally supports **temporary (PFS) auth keys** bound to the
permanent one — these DO expire and are rotated proactively.

---

## Overview

| Aspect | TDLib behavior |
| --- | --- |
| Key size | 256 bytes (2048-bit), key_id = lower 64 bits of SHA1(key) |
| Lifetime | Permanent, no proactive expiry; only resigned on `-404` / logout / explicit destroy |
| Persistence | Per-DC, binary-serialized in the binlog PMC (key-value store): `authkey<dc_id>` |
| Metadata stored | key_id, 256-byte key, flags (auth_flag, created_at, expires_at + wall-clock epoch) |
| Reuse | Eagerly reused on every reconnect; never regenerated while present and valid |
| Regeneration trigger | Transport-level `-404 auth_key_unknown`, `ENCRYPTED_MESSAGE_INVALID`, `destroy_auth_key` flow |
| Multi-DC | One key per DC, stored under per-DC binlog keys, shared via `AuthDataShared` |
| Temp (PFS) keys | Short-lived keys bound to the permanent key via `auth.bindTempAuthKey`; refreshed from `need_tmp_auth_key()` |

The lifecycle forms a **state machine** with three auth-key states
(`td/telegram/net/AuthKeyState.h`):

```
Empty (no key)  ->  NoAuth (key exists, not yet bound/authorized)  ->  OK (usable)
   ^                    |  ^                                                  |
   |   gen_key()        |  |  bind/authorize                                 |
   +--------------------+  +------------------------------------------------+
```

- `Empty`: `AuthKey` has no material (`auth_key.empty()`).
- `NoAuth`: key bytes exist but `auth_flag_ == false` (temp key not yet bound,
  or permanent key not yet proven).
- `OK`: `auth_flag_ == true` — the key is usable for normal traffic.

---

## Key Generation (DH Exchange)

Full implementation: `td/mtproto/Handshake.cpp` (`AuthKeyHandshake`),
`td/mtproto/DhHandshake.cpp`, `td/mtproto/KDF.cpp`.

### Flow

```
req_pq_multi(nonce)                     -> resPQ{nonce, server_nonce, pq, fingerprints}
req_DH_params(nonce, server_nonce, p, q, fingerprint, encrypted_data)
                                        -> server_DH_params_ok{nonce, server_nonce, encrypted_answer}
set_client_DH_params(nonce, server_nonce, encrypted_data)
                                        -> dh_gen_ok{nonce, server_nonce, new_nonce_hash1}
```

The state machine has 5 states (`State::Start → ResPQ → ServerDHParams →
DHGenResponse → Finish`); any processing error calls `clear()` and the actor
retries from `Start`.

### Detailed steps

1. **Client generates** `nonce_` (16 bytes secure random) — `on_start()`.
2. **resPQ validation** (`on_res_pq`): echoes `nonce_`, captures `server_nonce_`,
   selects the RSA public key whose fingerprint the server listed, factorizes
   `pq` (Pollard-Rho-from-Brent in `pq_factorize`).
3. **`new_nonce_`** (32 bytes secure random) is generated client-side.
4. **RSA-PAD encryption of `p_q_inner_data_dc`** (or `p_q_inner_data_temp_dc`
   for temp keys, carrying `expires_in`):
   - inner data padded to 192 bytes, reversed,
   - `data_with_hash = transformed || SHA256(temp_aes_key || padded_data)`,
   - AES-IGE encrypted with a random 32-byte temp key + zero IV,
   - header `temp_key XOR SHA256(aes_encrypted)` prepended (256 bytes total),
   - raw RSA `payload^e mod n`, retried while `payload >= n`.
   - The `dc_id` is baked into `p_q_inner_data_dc` — **the key is bound to the
     DC it was negotiated with**.
5. **`server_DH_inner_data`** (`on_server_dh_params`):
   - decrypted with `tmp_aes_key/iv = tmp_KDF(server_nonce, new_nonce)`
     (`KDF.cpp:52`, exactly the recipe Teleframe mirrors in
     `AuthKeyFactory::tmpAesKey/tmpAesIv`),
   - verified by `SHA1(answer) + answer + pad` structure,
   - nonce/server_nonce echo checked,
   - **server time diff captured** (`server_time_diff_ = server_time - now`),
   - `DhHandshake` verifies the prime/generator and computes `g_b = g^b mod p`
     with a random 2048-bit `b`;
   - **auth key computed** as `auth_key = g_a^b mod p`, `key_id =
     calc_key_id(key)` = `int64` loaded from `SHA1(key)[12..20]` —
     (`DhHandshake.cpp:225`).
   - `server_salt = int64(new_nonce) XOR int64(server_nonce)`.
6. **`dh_gen_ok` verification** (`on_dh_gen_response`): recomputes
   `new_nonce_hash1 = SHA1(new_nonce || 0x01 || SHA1(auth_key)[0..8])[4..20]`
   and hard-fails the handshake on mismatch (only then transition to `Finish`).

### Security checks TDLib performs (vs Teleframe)

| Check | TDLib | Teleframe `AuthKeyFactory` |
| --- | --- | --- |
| nonce echo | Yes, every message | Yes (`assertNonceEcho`) |
| RSA fingerprint match | Yes | Yes |
| prime = 2048-bit safe prime | Runtime primality proof (`p` and `(p-1)/2` both prime) via `DcId`-cached `DhCallback`, plus quadratic-residue checks on `g` | **Hardcoded constant equality** with the one official 2048-bit prime |
| `g_a` range | `2^(2048-64) <= g_a <= p - 2^(2048-64)` (`dh_check`) | Only `1 < g_a < p-1` — weaker bound |
| new_nonce_hash1 | Yes | Yes |
| key_id derivation | `SHA1(key)[12..20]` | n/a (not needed for wire yet) |

---

## Key Storage & Persistence Format

### In-memory container: `mtproto::AuthKey` (`td/mtproto/AuthKey.h`)

Fields:
- `auth_key_id_` (uint64) — derived, never stored in the key stream itself.
- `auth_key_` (string, 256 bytes).
- `auth_flag_` (bool) — main-key proven / temp-key bound.
- `have_header_` / `header_expires_at_` — the `invokeWithLayer+initConnection`
  header is sent only until the first API response (`remove_header()` on
  `on_api_response`), with a 3-second grace (`header_expires_at_ = now + 3`).
- `expires_at_` (double) — **only set for temp keys**.
- `created_at_` (double) — server time when the key was born
  (`set_created_at(dh_inner_data.server_time_)`); used for the
  `ENCRYPTED_MESSAGE_INVALID` immunity window.

### Serialized on-disk format (`AuthKey::store`, used by `AuthDataShared`)

```
auth_key_id   : int64
flags         : int32   (bit 1 = AUTH_FLAG, bit 4 = HAS_CREATED_AT, bit 8 = HAS_EXPIRES_AT)
auth_key      : string  (length-prefixed, 256 bytes)
[created_at   : double] (only if HAS_CREATED_AT)
[time_left    : double] (only if HAS_EXPIRES_AT; expires_at - now)
[server_epoch : double] (wall-clock Clocks::system() at save time)
```

On parse, `expires_at_` is reconstructed as `now + (time_left - elapsed)` using
the stored `server_epoch`, making expiration portable across restarts.
On load, `have_header_ = true` unconditionally ("just in case").

### Persistence backend: binlog PMC

`td/telegram/net/AuthDataShared.cpp`:

```cpp
string get_auth_key_binlog_key(DcId dc_id) { return "authkey" + dc_id; }
// get:  G()->td_db()->get_binlog_pmc()->get(authkey<dc_id>)
// set:  G()->td_db()->get_binlog_pmc()->set(authkey<dc_id>, serialize(auth_key))
```

Key insight: the **key_id is NOT stored inside the key bytes**; it is always
recomputed from the 256-byte key. This makes persistence self-consistent —
corruption can be detected by re-deriving and comparing the id.

The binlog is an append-only journal that TDLib replays on startup — auth keys
survive restarts, and the PMC abstraction gives atomic per-key sets.

---

## Key Reuse Policy

TDLib's policy is **maximize reuse**; only generate when there is proof the
current key is gone.

1. At session start (`Session::start_up`), `AuthDataShared` loads the
   persisted per-DC key from the binlog. If present (even with
   `auth_flag_ == false`), it is reused — no regeneration.
2. `mtproto::AuthData::need_main_auth_key()` returns true **only** when the key
   is `empty()` (`AuthData.h:84`).
3. The key is shared across ALL sessions to the same DC via
   `std::shared_ptr<AuthDataShared>`; a newly generated key is written to the
   binlog once and every session picks it up.
4. A freshly generated main key has `auth_flag_ = false` until the first
   successful API round-trip proves it. Until then, PFS sessions route traffic
   through the `bindTempAuthKey` dance; non-PFS mode just uses it directly.
5. On reconnect, the same `AuthKey` object is re-serialized into every packet
   header (auth_key_id only) — there is no re-handshake for reconnect.

The one reuse caveat is the **handshake-socket binding**: a key generated on a
given TCP connection is known to the server on that connection until first
use; TDLib keeps the handshake connection alive and promotes it into the first
encrypted session (Teleframe mirrors this exactly — see
`Client::ensureAuthKey` promoting `$plain->socket`).

---

## Error-Driven Re-Authentication

### Transport-level `-404 auth_key_unknown`

Handled in two places:

**a) `ConnectionCreator::client_add_connection` (`ConnectionCreator.cpp:995`):**
when a raw connection fails to initialize with error code `-404` AND the DC's
`auth_data` generation still matches, the DC's auth data is dropped
(`client.auth_data = nullptr; auth_data_generation++`), forcing the next
session creation to start from `Empty` and re-handshake.

**b) `Session::on_closed` (`Session.cpp:629`)** — the main re-auth state machine:

```cpp
if (status.is_error() && status.code() == -404) {
  if (auth_data_.use_pfs()) {
    // drop ONLY the temp key, keep the permanent one
    auth_data_.drop_tmp_auth_key();
    on_tmp_auth_key_updated();
  } else if (is_cdn_) {
    auth_data_.drop_main_auth_key(); on_auth_key_updated();
    on_session_failed(status.clone());
  } else if (need_destroy_auth_key_) {
    auth_data_.drop_main_auth_key(); on_auth_key_updated();
  } else {
    // main permanent key path
    if (!use_pfs_) { auth_data_.set_use_pfs(true); }        // first-invalid: check main key through PFS
    else if (need_check_main_key_) {
      auth_data_.drop_main_auth_key(); on_auth_key_updated();
      // log out if this is the main DC session and not allowed to drop silently
    }
  }
}
```

Escalation ladder for the permanent key (all on `-404`):

1. **First `-404`** → if not already in PFS mode, switch to PFS and check the
   main key via `help.getNearestDc` (`on_check_key_result`,
   `Session.cpp:449`). If that check returns OK → back to PFS mode, key kept.
   If the check ALSO returns `-404` → the permanent key is confirmed dead.
2. **Confirmed** → `drop_main_auth_key()` (key cleared from `AuthData` and the
   shared binlog via `on_auth_key_updated()`). Then either:
   - a silent `log_out` (main DC session) — the user must reauthorize from
     scratch, or
   - `on_session_failed` for non-main DC sessions (the DC gets a fresh
     handshake on next use, no global logout).
3. All in-flight queries are marked unknown and retried/resolved.

### RPC-level auth errors

`400 AUTH_KEY_UNREGISTERED` / `401 AUTH_KEY_INVALID`, `SESSION_REVOKED`,
`SESSION_EXPIRED` surface through the RPC error path (SessionConnnection →
NetQueryDispatcher → `G()->log_out(...)` where needed). These are treated as
user-level deauthorization and force logout; MTProto-layer `-404` (a bare
int32 frame) is the transport signal that specifically means "the key itself
is unknown."

### `destroy_auth_key` flow (explicit rotation)

TDLib exposes `net.destroyAuthKey` semantics: `SessionConnection::destroy_key()`
sets `need_destroy_auth_key_`; the next flush packet appends the
`destroy_auth_key` service message (`CryptoStorer.h:224`,
`DestroyAuthKeyImpl`). On `destroy_auth_key_ok/none/fail` →
`on_destroy_auth_key()` → `Session::on_destroy_auth_key` → the session closes,
the key is dropped, and the client regenerates on next use. Without such an
explicit request, keys are effectively permanent.

### `ENCRYPTED_MESSAGE_INVALID` during bind (`Session::on_bind_result`)

When binding a temp key to the permanent key fails with
`ENCRYPTED_MESSAGE_INVALID`, the permanent key is suspected:

- **Immunity window**: if server time is unreliable, OR the main key is
  younger than 60s, OR it is older than 24h but was successfully used within
  the last 24h (`has_immunity`), the main key is NOT dropped.
- Outside the window: the main key is dropped (non-PFS) or validated through a
  PFS probe (`need_check_main_key_ = true`, `set_use_pfs(false)`).

---

## Multi-DC Key Management

`td/telegram/net/DcAuthManager.cpp` orchestrates handshakes per DC; each DC
gets its own independent 256-byte key.

| Concept | Implementation |
| --- | --- |
| Per-DC storage | binlog PMC key `authkey<dc_id>` — one key per https:// DC, per account |
| Sharing | `AuthDataShared` (per DC) holds `AuthKey`; all `Session`s and the `ConnectionCreator` for that DC read the same object |
| Raw vs virtual DC | `DcId` distinguishes raw (1–5) from virtual DC ids (media, CDN, downloads); every raw DC has its own key, virtual DCs reuse the parent raw DC's key |
| CDN keys | `is_cdn_` sessions use the same handshake but a stricter `-404` path (drop key, fail session, no global logout) |
| DC migration | On `FILE_MIGRATE`/`USER_MIGRATE`, the new DC is used directly — the client lazily handshakes that DC's key on first use; the migrated DC's own key is independent |
| Key generation isolation | `p_q_inner_data_dc` binds the key to the negotiated DC (`Handshake.cpp:116`) |

When a key for a DC is dropped after a confirmed `-404` (non-main DC), the
next query to that DC triggers a fresh `DcAuthManager` handshake transparently
— no user interaction required. For the main DC, dropping the key means
`log_out` — the user must go through the auth code/QR flow again.

---

## Key Lifetime & Staleness

- **Permanent keys do not expire.** No TTL is stored, no re-handshake is
  scheduled (`expires_at_` stays 0; `AuthKey::store` omits the field).
- **Staleness is detected only via errors**: a stale key's first packet earns
  a transport `-404` and the ladder above. There is no proactive
  "rotate after N days".
- **Temp (PFS) keys DO expire**: `expires_in` chosen by the client
  (`p_q_inner_data_temp_dc`, `AuthKeyHandshake::AuthKeyHandshake(dc_id,
  expires_in)`); `expires_at_ = now + expires_in`. Refresh logic:
  - `AuthData::need_tmp_auth_key(now, refresh_margin)` → true when empty or
    within `refresh_margin` of expiry. `TempAuthKeyWatchdog` schedules the
    refresh and reissues `auth.bindTempAuthKey` with the same permanent key.
  - Binding re-proves the permanent key (`auth_flag` on the temp key = bound),
    which is how `-404` on the bind reveals a dead permanent key.
- **Server-salt staleness** is handled separately (30-day epoch via
  `get_future_salts`, `bad_server_salt` resend) and never touches the key.

---

## Comparison with Teleframe's Current State

Files consulted: `src/Core/MTProto/Crypto/AuthKeyFactory.php`,
`src/Core/MTProto/SessionData.php`, `src/Core/MTProto/Client.php`,
`src/Core/MTProto/Connection/EncryptedConnection.php`,
`src/Core/Exceptions/Rpc/RpcExceptionResolver.php`,
`src/Core/Exceptions/Rpc/AuthKeyException.php`.

### Already aligned with TDLib

- **Handshake correctness**: `AuthKeyFactory::generate()` implements the full
  MTProto 2.0 flow (`req_pq_multi → req_DH_params → set_client_DH_params`) with
  transcript-verified `tmp_aes_key/iv`, RSA-PAD, `new_nonce_hash1`, nonce
  echo checks, and `p_q_inner_data_dc` DC binding — matching TDLib's
  `Handshake.cpp` step for step.
- **Server-salt handling**: `EncryptedConnection` resends on `bad_server_salt`
  and mirrors the salt into `SessionData` (TDLib parity).
- **Fresh-key connection binding**: `Client::ensureAuthKey()` promotes the
  PlainConnection socket into the EncryptedConnection — the TDLib rule that a
  new key must make its first encrypted request on the handshake connection
  or get `-404`.
- **`-404` detection**: `EncryptedConnection::assertNotTransportErrorFrame`
  + `RpcExceptionResolver::fromTransportCode(-404)` → `AuthKeyException`
  (name `AUTH_KEY_UNREGISTERED`).
- **Reuse-before-regenerate**: `Client::ensureAuthKey()` reuses any 256-byte
  `session->authKey`; only an empty/short key triggers the handshake.

### Gaps (Teleframe lacks vs TDLib)

1. **No persistence layer for keys beyond the session string.**
   `SessionData::exportString()`/`importString()` is a manual
   `dcId:base64(authKey):userId:timeDelta:salt` blob with **no metadata**
   (no `created_at`, no `auth_flag`, no key_id). There is no per-DC key store
   (TDLib's `authkey<dc_id>` binlog entries); keys live only where the caller
   stores the session string. No key_id is ever derived/validated, so a
   corrupted key is detected only by a round-trip `-404`.
2. **No re-auth state machine on `-404`.**
   Teleframe throws `AuthKeyException` and the caller must manually clear the
   session and re-handshake. TDLib auto-escalates: first `-404` → probe via
   `help.getNearestDc`, second or main-DC `-404` → drop key + transparent
   regenerate (non-main DC) or log out (main DC).
3. **No `auth_flag` / key-validity tri-state.**
   TDLib distinguishes Empty / NoAuth / OK and refuses to send auth-flagged
   traffic over an unproven key. Teleframe's `authKey` string is either
   present (used blindly) or absent (handshake).
4. **No temporary/PFS keys.**
   TDLib rotates temp keys proactively and binds them via
   `auth.bindTempAuthKey` (also the vehicle that *probes* the permanent key).
   Teleframe connects directly with the permanent key on every connection —
   simpler, but no PFS and no proactive key-health probing. TDLib's
   `EncryptedConnection::encrypted_bind()`/`bind_auth_key_inner` have no
   Teleframe counterpart.
5. **No key age / immunity logic.**
   TDLib's `ENCRYPTED_MESSAGE_INVALID` immunity window (don't drop a key
   younger than 60s) has no Teleframe equivalent — though Teleframe has no
   bind path to trigger it either.
6. **No proactive keepalive of key validity.**
   TDLib rechecks the main key through PFS probes. Teleframe only discovers a
   dead key when a real RPC lands on it.
7. **RPC auth errors are not mapped to logout/regenerate.**
   `AuthKeyException` gives a hint that mentions regeneration, but nothing in
   `Client` consumes it to drop the session and re-run
   `AuthKeyFactory::generate()` (per-DC).
8. **SessionData has no `keyId` field** even though the wire header needs it —
   `PacketCodec` must derive it from the key every packet (TDLib stores it
   once in `AuthKey`).

---

## Recommendations

1. **Add a per-DC auth-key store (binlog/PMC parity → DB/Redis).**
   Persist `{dc_id, auth_key, key_id, created_at, server_time_delta, salt}` —
   TDLib's `authkey<dc_id>` shape — keyed by `(account, dc_id)`, not a single
   opaque session string. Derive and store `key_id =
   substr(sha1(authKey), 12, 8)` once (validate on load: recompute, reject
   mismatch) so corruption is caught without a network round-trip.

2. **Implement the `-404` auto-recovery ladder in `Client`.**
   - On `AuthKeyException` from a transport `-404` symmetric to
     `Session::on_closed`:
     - non-main-DC: drop the stored key for that DC, transparently re-handshake
       on the next call (retry the failed request once);
     - main DC: if a "probe" hasn't happened yet, first re-validate via
       `help.getNearestDc`; only on a second `-404` drop the key and surface a
       re-login signal.
   - Wrap it so callers see at most one retry; never silently loop.

3. **Add the tri-state key validity to `SessionData`.**
   Model Empty/NoAuth/OK (`auth_key` empty / present & unproven / proven).
   Treat a freshly generated key as NoAuth until the first successful RPC and
   persist the upgraded state — mirrors `AuthKeyState.h` and lets multi-session
   clients avoid racing on one unproven key.

4. **Add session-level `keepalive`/probe.**
   A periodic `help.getNearestDc` (or `ping`) is both the TDLib main-key probe
   and the salt/time sync; schedule it on idle connections long before the
   ~60s server idle kill.

5. **(Optional) PFS temp keys.**
   Only worth doing once the permanent-key store, recovery ladder, and probe
   exist. Bind via `auth.bindTempAuthKey` and refresh with a watchdog —
   otherwise Teleframe and TDLib already agree that permanent keys carry the
   session credentials.

6. **Harden the DH parameter check.**
   Replace the fixed `KNOWN_DH_PRIME_HEX` equality with TDLib's
   `DhHandshake::check_config` semantics (2048-bit check + safe-prime proof +
   quadratic-residue mod conditions, cached per prime). Also tighten the `g_a`
   range to `[2^(2048-64), p - 2^(2048-64)]` per `dh_check` — this is the one
   place Teleframe's handshake is weaker than TDLib's.