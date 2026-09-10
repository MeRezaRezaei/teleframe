# TDLib Multi-DC Management Analysis

**Date:** 2026-09-10
**Source:** TDLib September 2026 snapshot (`tdlib/td@latest`, layer 227-era wire / 229 schema)
**Scope:** DC discovery, DC option caching, migration protocol, file reference lifecycle, chunked download, multi-DC auth, CDN routing
**Phase:** 0-E of the TDLib reverse-engineering series

---

## Overview

Telegram distributes state across up to five production Data Centers (DCs), plus
CDN DCs beyond the main five. Every entity that can be migrated carries the DC
it lives on (`dc_id` fields on `photo`, `document`, `groupCall`, etc.). Files,
chats, channels, statistics, and per-phone-number accounts are spread across
DCs. The wire answers to the wrong DC with a `303 SEE_OTHER` error carrying a
suffix: `PHONE_MIGRATE_X`, `USER_MIGRATE_X`, `NETWORK_MIGRATE_X`,
`FILE_MIGRATE_X`, `STATS_MIGRATE_X`. TDLib treats migration as a first-class
transport concern: the query dispatcher pre-emptively routes every request to
the DC that owns the target entity, and when the server disagrees it re-targets
the in-flight query without notifying callers.

The machinery is spread across these files (there is no single `DCManager`):

| Component | File | Responsibility |
|---|---|---|
| DC id abstraction | `td/telegram/net/DcId.h` | Internal (1..5) vs external (CDN) vs main vs invalid |
| DC option model + cache | `td/telegram/net/DcOptions.h` | `DcOption` (ip/port/secret/flags), `DcOptions` set |
| Config recoverer | `td/telegram/ConfigManager.cpp` | SimpleConfig (DNS), `updateDcOptions` live push, `help.getConfig` full config |
| Query routing | `td/telegram/net/NetQuery*` + `NetQueryDispatcher.cpp` | Per-DC session multiplex, migrate fixup, lazy DC init |
| Cross-DC auth | `td/telegram/net/DcAuthManager.cpp` | `auth.exportAuthorization` → `auth.importAuthorization` per DC |
| File reference repair | `td/telegram/FileReferenceManager.*` | Reverse-map file → source → refetch |
| Chunked download | `td/telegram/files/FileDownloader.*`, `PartsManager.h` | `upload.getFile` chunks, CDN, resume |

---

## DC Discovery & Caching

### 1.1 Sources of DC options (layered by priority)

`ConfigRecoverer` (`ConfigManager.cpp`) maintains three option sets and merges
them under one rule:

```cpp
// update_dc_options(), ConfigManager.cpp:823
void update_dc_options() {
  auto new_dc_options = simple_config_.dc_options;
  new_dc_options.insert(new_dc_options.begin(),
                        dc_options_update_.dc_options.begin(),
                        dc_options_update_.dc_options.end());
  ...
}
```

**`dc_options_ = dc_options_update_ + simple_config_` — live-session options
always win.** Sources, in priority order:

1. **`updateDcOptions#8e5e9873 dc_options:Vector<DcOption>` push** — arrives at
   any time from any connected DC. `ConfigManager::on_dc_options_update()` saves
   it to the binlog PMC under key `dc_options_update` (so it survives restart)
   and forces an immediate config reload (`expire_time_ = now`).
2. **`help.getConfig` full config (main DC)** — fetched on startup (if the
   cached expiry is past) and periodically. Refreshed every 2–3 min while
   "expect_blocking" is set, 20–30 min otherwise, with a random jitter of up to
   `reload_in / 5`. Stored at `process_config()` (ConfigManager.cpp:1173);
   note "Do not save dc_options in config, because it will be interpreted and
   saved by ConnectionCreator."
3. **SimpleCallback DNS/HTTPS config (`help.configSimple`)** — fetched only
   when the client cannot reach any known DC for more than
   `max_connecting_delay()` (5 s blocking, 20 s non-blocking). Providers:
   Google/Mozilla DNS-over-HTTPS TXT for `apv3.stel.com` (prod) /
   `tapv3.stel.com` (test), Azure CDN, Firebase. The payload is RSA-decrypted +
   AES-CBC-decrypted with an embedded signature check (`decode_config()`).

`ConfigRecoverer` also watches connectivity: on failure states it clears
`simple_config_` and falls back to DNS again; on reconnection it re-fetches.

### 1.2 DcOption structure (what gets cached)

`DcOptions.h` — each cached option is:

```
DcId dc_id   (internal = main DC number, external = CDN DC number)
IPAddress ip_address  (v4 or v6)
mtproto::ProxySecret secret_   (for obfuscated TCP proxies, optional)
flags: IPv6 | MediaOnly | ObfuscatedTcpOnly | Cdn | Static | HasSecret
```

Flags semantics seen in the code and TL schema:
- `media_only` — DC serves only media; API queries are not sent there.
- `cdn` — external DC, file tokens only (`upload.getCdnFile`), needs CDN RSA keys.
- `tcpo_only` — only obfuscated TCP transport accepted.
- `static` — immutable "official" endpoint (this_port_only assertion in the new `dcOption` TL).
- `secret` — MTProto proxy secret for transport obfuscation.

**Auth per DC:** each DC keeps its own authorization key. `AuthDataShared` is
created per `DcId` in `NetQueryDispatcher::wait_dc_init()` (line ~239), keyed in
the binlog by DC (`AuthDataShared` persists `auth_key`, `future_salts` per DC).
The net layer is *not* asked which DC a query targets — `NetQuery` carries a
`DcId` and the dispatcher routes on it. Auth keys are cached per DC, not shared.

The client's `main_dc_id` is persisted separately under PMC key `main_dc_id`
and read back in both `DcAuthManager` and `NetQueryDispatcher` constructors.

### 1.3 `help.getNearestDc`

The TL schema exposes `nearestDc#8e1a1775 country:string this_dc:int nearest_dc:int = NearestDc`.
TDLib's bootstrap path (AuthManager) effectively uses a lighter equivalent:
it starts on DC 2 (default `main_dc_id_ = 1` there because TDLib defaults to
`DcId::main()`), and the *first* migration error any query returns
(`PHONE_MIGRATE_X`/`USER_MIGRATE_X`) both re-targets that query and sets the
de-facto main DC. `help.getNearestDc` itself is available in the schema and is
used by TDLib in a few flows to learn `this_dc`/`nearest_dc` up front.

---

## DC Migration Protocol

### 2.1 `try_fix_migrate` — the single choke point

`NetQueryDispatcher.cpp:391` — every error with HTTP code **303** is routed here:

```cpp
void NetQueryDispatcher::try_fix_migrate(NetQueryPtr &net_query) {
  auto error_message = net_query->error().message();
  static constexpr CSlice file_migrate_prefix = "FILE_MIGRATE_";
  if (begins_with(error_message, file_migrate_prefix)) {
    auto new_dc_id = to_integer<int32>(error_message.substr(file_migrate_prefix.size()));
    net_query->resend(DcId::internal(new_dc_id));   // ① file lives elsewhere
    return;
  }
  static constexpr CSlice prefixes[] = {"PHONE_MIGRATE_", "NETWORK_MIGRATE_", "USER_MIGRATE_"};
  for (auto &prefix : prefixes) {
    if (error_message.substr(0, prefix.size()) == prefix) {
      auto new_main_dc_id = to_integer<int32>(error_message.substr(prefix.size()));
      set_main_dc_id(new_main_dc_id);                // ② account/network moved
      if (!net_query->dc_id().is_main()) {
        net_query->resend(DcId::internal(new_main_dc_id));
      } else {
        net_query->resend();                          // re-send to new main
      }
      break;
    }
  }
}
```

Two distinct semantics:
- **`FILE_MIGRATE_X` / `STATS_MIGRATE_X`** — *entity-scoped*: the object lives on
  DC X. Only the current query is re-targeted; "main DC" is untouched.
- **`PHONE_MIGRATE_X` / `NETWORK_MIGRATE_X` / `USER_MIGRATE_X`** — *session-scoped*:
  the whole account/network now lives on DC X. `set_main_dc_id(X)` updates the
  global main-DC atom, tells the old main session to clear its "main" flag, tells
  the new DC's session to set it, notifies `DcAuthManager`, and persists the new
  id to the binlog. Subsequent `DcId::main()` queries go to the new DC.

`NetQuery::resend()` (NetQuery.cpp) re-queues the identical packet on the new
DC with a fresh message id — callers never observe the migration.

### 2.2 Pre-emptive routing in `dispatch()` (NetQueryDispatcher.cpp:157)

```cpp
auto dest_dc_id = net_query->dc_id();
if (dest_dc_id.is_main()) {
  dest_dc_id = DcId::internal(main_dc_id_.load());   // resolve "main" indirection
}
if (!net_query->is_ready() && wait_dc_init(dest_dc_id, true).is_error()) {
  net_query->set_error(...);
}
...
switch (net_query->type()) {                          // 4 session lanes per DC
  case Common:        → dcs_[dc].main_session_
  case Upload:        → dcs_[dc].upload_session_
  case Download:      → dcs_[dc].download_session_
  case DownloadSmall: → dcs_[dc].download_small_session_
}
```

`wait_dc_init` lazily materializes the four `SessionMultiProxy` actors for a DC
on its first use (with a busy-wait until `is_inited_` if another thread is
initializing). Session counts by lane: main = `max(session_count,1)`;
upload/download/download_small = 4/2/2 normally, 8/8/8 for premium accounts
(and upload lanes are 8 on DC 2/4 for premium). Each lane is a `SessionMultiProxy`
with per-DC `AuthDataShared` (its own auth key + future salts).

### 2.3 Cross-DC authorization export/import (`DcAuthManager`)

When a query is migrated to a DC that has no usable auth key, the auth state
machine in `DcAuthManager.cpp` runs:

1. `auth.exportAuthorization(dc_id)` — sent to the **main** DC, `AuthFlag::On`.
   Returns `(id, bytes)`.
2. `auth.importAuthorization(id, bytes)` — sent to the **target** DC,
   `AuthFlag::Off` (it is deliberately not the main auth-key-binding handshake).
3. State machine states: `Waiting → Export → Import → BeforeOk → Ok`, gated on
   the main DC having `AuthKeyState::OK` first (`loop()` returns until then).
   On failure the loop restarts from `Export`. Timeouts are 24 h
   (`total_timeout_limit_ = 60 * 60 * 24`).

The same primitive is reused for QR login: `auth.loginTokenMigrateTo
#68e9916 dc_id:int token:bytes` tells the client the QR code belongs to another
DC; `AuthManager` records `imported_dc_id_`, re-exports a fresh token there and
calls `set_main_dc_id(imported_dc_id_)` (AuthManager.cpp:1211/1248). Teleframe
already replicates this via `loginTokenMigrateTo` → `DcMigrationException` +
`importLoginTokenAt(dcId, token)`.

### 2.4 `updateDcOptions` as a migration pre-emptor

Telegram pushes `updateDcOptions#8e5e9873` whenever the app inherits DC
re-pointing (e.g. the nation-level IP moves). Receiving it bumps
`dc_options_update_`, forces an immediate `help.getConfig` reload, and swaps the
live connection endpoints — even while `PHONE_MIGRATE_X` errors drive the main
DC change.

---

## File Reference Routing

### 3.1 Files are DC-bound

Every media constructor embeds its home DC:

```
photo#fb197a65 ... dc_id:int = Photo
document#8fd4c4d8 ... dc_id:int = Document
encryptedFile / secureFile / stickerSet / chatPhoto / userProfilePhoto ... dc_id:int
webfile_dc_id:int  (in help.getConfig — the DC web preview files live on)
```

TDLib stores this in `FullRemoteFileLocation` (FileLocation.h:501):
`{FileType, id, access_hash, DcId dc_id_, std::string file_reference}`. The
`FileDownloader` builds `inputFileLocation{id, access_hash, file_reference}`
and the query is dispatched to `remote_.get_dc_id()` — i.e. **the file's own
DC, not the session DC**. That is why `FILE_MIGRATE_X` is an entity-scoped
redirect rather than a session change, and why per-DC auth keys are mandatory:
media DCs are often *not* the account DC.

File routing inputs:
- `upload.getFile(location, offset, limit)` per chunk
- `upload.getCdnFile(file_token, offset, limit)` post-redirect
- `upload.getWebFile(location, offset, limit)` for `webfile_dc_id`
- `upload.getFileHashes(location, offset)` for integrity/reuse checks

### 3.2 CDN redirect flow

`FileDownloader` has three query types: `Default`, `CDN`, `ReuploadCDN`
(FileDownloader.h:56). `upload.getFile` may answer `upload.fileCdnRedirect
#f18cda44 dc_id:int file_token:bytes encryption_key:bytes encryption_iv:bytes
file_hashes:Vector<FileHash>`. The downloader then:
- switches to `use_cdn_` with `cdn_dc_id_`, `cdn_file_token_`
- fetches the CDN DC's RSA key via `PublicRsaKeySharedCdn` (per-DC CDN keys,
  watched by `PublicRsaKeyWatchdog`, TL `cdnPublicKey#c982eaba dc_id public_key`)
- issues `upload.getCdnFile(file_token, offset, size)` per chunk
- decrypts each chunk in place with per-chunk AES-CTR using key/IV derived from
  the CDN redirect + chunk offset (FileDownloader.cpp:253: `offset` is written
  big-endian into IV bytes 12–15)
- on `FILE_TOKEN_EXPIRED` switches to `ReuploadCDN`:
  `upload.reuploadCdnFile(file_token, request_token)` for each hash

---

## File Reference Refresh

### 4.1 Error contract

`FileReferenceManager::is_file_reference_error()` — code `400` AND message
prefixed `FILE_REFERENCE_`. Variants handled by
`get_file_reference_error_source()`:
- `FILE_REFERENCE_EXPIRED`, `FILE_REFERENCE_EMPTY`, `FILE_REFERENCE_INVALID` (no index)
- indexed multi-file forms: `FILE_REFERENCE_<idx>_...` inside sendMedia batches
- `FILE_REFERENCE_ATTACH_<idx>_`, `FILE_REFERENCE_SOLUTION_...` (polls),
  `FILE_REFERENCE_ANSWER_<idx>_...`
- `COVER_` suffix selects the cover file ids/references vector instead

Bots are exempt (`auth_manager_->is_bot()` → no repair).

### 4.2 Reverse map: FileId → sources

`FileReferenceManager` keeps a `WaitFreeHashMap<FileId, Node>` where each node
holds an *append-only* set of `FileSourceId`s — independent records of "where we
saw this file". Sources include (`fileSource*` TL dialect, FileReferenceManager.h:153+):

```
Message, UserPhoto, ChatPhoto, ChannelPhoto, WebPage, SavedAnimations,
RecentStickers, FavoriteStickers, Background, ChatFull, ChannelFull, AppConfig,
SavedRingtones, UserFull, AttachMenuBot, WebApp, Story, QuickReplyMessage,
StarTransaction, BotMediaPreview, StoryAlbum, UserSavedMusic, DraftMessage,
RichMessage, WelcomeMessages, CommunityFull
```

Each source knows the exact method to re-fetch the owner object (the `send_query`
visitor, FileReferenceManager.cpp:473): a message → `get_message_from_server`,
a user photo → `reload_user_profile_photo`, a web page → `reload_web_page_by_url`,
a chat full → `reload_chat_full`, app config → `reload_app_config`, etc.

### 4.3 Repair protocol

`repair_file_reference(file_id, promise)`:
1. Coalescing: a node has one `Query` holding all waiting promises +
   `active_queries` count + a `generation` (bumped to invalidate stale
   inflight repairs). Only one repair runs per file at a time.
2. Rate limiting: `last_successful_repair_time >= now - 60` →
   `429 Too Many Requests: retry after 60` to all waiters.
3. Sources are tried in insertion order; on failure the next source is tried.
   Exhausted sources → `400 File source is not found` (or 429/1).
4. On success, `FileManager::on_file_reference_repaired(file_id, source_id, ...)`
   stores the fresh reference (from the newly fetched object) back into the
   file's `FullRemoteFileLocation`, so `remote_.as_input_file_location()`
   carries a current reference for subsequent chunks.

Uploaded-file references are tracked by the *same* mechanism: `FileManager`
records `upload_was_update_file_reference_` / `download_was_update_file_reference_`
flags; locating a file whose reference changed mid-operation re-runs the repair
path. Stale references are dropped first via `delete_file_reference(slice)`.

---

## Chunked Download Strategy

`PartsManager` constants (PartsManager.h:56):

```
MAX_PART_COUNT        = 4000
MAX_PART_COUNT_PREMIUM = 8000
MAX_PART_SIZE         = 512 << 10   // 512 KiB per chunk
MAX_FILE_SIZE         = 512 KiB * 8000 ≈ 4 GiB
```

`FileDownloader` mechanics:
- One `upload.getFile(location, offset, part_size)` per part; `part_size` ends
  at `min(part_size, remaining)`; offsets must be 16-byte aligned (decrypted
  blocks / CDN IV unit).
- **Resume**: partial state is `PartialLocalFileLocation{part_size, path, iv,
  FileBitmask, ready_size}` persisted by `DownloadManager` under binlog keys
  `dlds#<download_id>` + a `dlds_counter`. On restart the bitmask tells exactly
  which parts are already on disk; `pwrite` appends only missing parts. The
  initial part size is read from a previously persisted partial (validated
  power-of-two ≤ 1 MiB) or defaults to 128 KiB (FileDownloader.cpp:520).
- **Parallelism**: a part map + `OrderedEventsProcessor` keep part completions
  in order while letting several parts be in flight. A `ResourceManager`
  governs resource units: `active_limit()` (default 2) concurrent
  downloaders/uploaders, `read_only_limit_` for streaming. New parts are started
  only when `resource_state_.unused() >= part_size`.
- **Streaming/partial reads**: `update_downloaded_part(offset, limit,
  max_resource_limit)` moves the streaming window and caps how many parts up to
  `max_resource_limit / part_size` are scheduled.
- **Small files**: the `DownloadSmall` session lane handles is_small downloads,
  kept separate so small-fetch latency is not starved by bulk media.
- **Integrity**: `upload.getFileHashes(location, offset)` returns
  `fileHash{offset, limit, hash}`; `check_loop` compares a locally-computed
  hash window against the returned hashes when a file was found via a hash
  search (`need_search_file_`) or re-verification (`only_check_`).

### Upload file references
Uploads reuse the same `inputFileLocation` / `upload.saveFilePart` flow.
References for uploads come from the server response (e.g. `document`/
`photo` constructors returned by `messages.sendMedia`); `FileManager` records
that the operation updated the reference
(`upload_was_update_file_reference_`), so a subsequent download of the same
file already carries the refreshed reference. On `FILE_REFERENCE_*` failure the
repair path re-fetches the owner object via its file source.

---

## Comparison with Teleframe

| Concern | TDLib | Teleframe today |
|---|---|---|
| DC endpoint table | Runtime `DcOptions` from DNS/HTTPS/updateDcOptions/help.getConfig, merged + shuffled + persisted (`dc_options_update`, `main_dc_id` binlog keys) | Hardcoded `Client::DC_IPS` const (5 prod IPs), no test-DC table, no runtime discovery, no persistence of dc options |
| Main-DC state | First-class `DcId::main()` indirection, persisted, swapped on PHONE/NETWORK/USER_MIGRATE | `SessionData::dcId` on each session; no global main-DC atom, no persistence of migrated DC on the account |
| Migration errors | Transport-level 303 handled inside `NetQueryDispatcher::try_fix_migrate` — query re-sent silently, callers unaffected | Detected at resolver level: `RpcExceptionResolver` maps `FILE/PHONE/NETWORK/USER/STATS_MIGRATE_%d` → `DcMigrationException` (code 303, dc bound 1..5); callers must re-issue manually |
| QR login migration | `loginTokenMigrateTo` → `imported_dc_id_` → re-export token + `set_main_dc_id` | `TeleframeAuthService::pollQrLoginToken` throws `DcMigrationException` carrying the token; `importLoginTokenAt(dcId, token)` reconnects on the new DC — **matches TDLib's approach** |
| Per-DC auth keys | Each DC owns an auth key (`AuthDataShared` per DcId); `DcAuthManager` exports/imports via `auth.exportAuthorization`/`importAuthorization` | Single auth key per session (`SessionData`); no per-DC auth management, no export/import flow |
| Session lanes | Four `SessionMultiProxy` lanes per DC (main/upload/download/download_small), lazily initialized, premium boosts 4/2/2 → 8/8/8 | One `EncryptedConnection` per client; no lane separation, no concurrency pool |
| File DC routing | `FullRemoteFileLocation.dc_id_` drives download/upload query DC directly | No file manager; no download/upload; file `dc_id` fields exist in schema models (`generated/`) but are unused by the engine |
| File reference lifecycle | `FileReferenceManager` reverse-map; `FILE_REFERENCE_*` (400) triggers source-based re-fetch with coalescing + 60 s rate limit; stale refs `delete_file_reference` | Not implemented — no file reference tracking, no repair |
| Chunked download | `upload.getFile` 512 KiB parts, bitmask resume (`dlds#*`), parallel parts + resource limits, CDN redirect (`upload.getCdnFile`, AES-CTR per chunk), `upload.getFileHashes` check | Not implemented |
| CDN | External `DcId`, per-DC CDN RSA keys watched by `PublicRsaKeyWatchdog`, `upload.reuploadCdnFile` on token expiry | Not implemented |
| `help.getNearestDc` | Available in schema, used opportunistically; `this_dc`/`nearest_dc` | Method exists in generated `Methods/Generated/Help.php`, not called by engine flows |

Teleframe's existing strengths: correct static migration error decoding
(`DcMigrationException` with strict digit tails), a working QR `loginTokenMigrateTo`
handoff, and per-session auth keys. The gaps are all *runtime* layers:
no DC endpoint learning, no per-DC auth, no file-scoped routing, no file
references, no chunked transfer.

---

## Recommendations

**R1 — DcId + main-DC state as a first-class value (smallest, highest value).**
Introduce an internal `DcId` value object (int ≤ 1000, `main` = -1 indirection,
`external` flag for CDN) plus an engine-global main-DC registry persisted with
the session (`dc_id` already in `SessionData`; add `main_dc_id`). Resolve
`DcId::main()` at dispatch time. This makes PHONE/USER/NETWORK migration a
transport concern instead of a caller concern.

**R2 — Transport-level migration fixup in the client.**
Add a `try_fix_migrate` equivalent to `Client::call()`/`callBatch()`: when a
response decodes to error code 303 with a `MIGRATE_<n>` suffix, re-target the
query once (without re-encoding the body) and hand the DcId change to the
session. Keep `RpcExceptionResolver` for non-transport callers, but stop
requiring every caller to catch `DcMigrationException` for routine account
migration. Mirror TDLib's split: `FILE_MIGRATE`/`STATS_MIGRATE` change only the
query target; `PHONE`/`NETWORK`/`USER_MIGRATE` also flip the session's main DC.

**R3 — Per-DC auth key cache + export/import.**
`SessionData` (or a new `DcSessionBag`) should hold one auth key per DC, lazily
created. Implement the `auth.exportAuthorization(dc_id)` →
`auth.importAuthorization(id, bytes)` state machine (the schema methods exist in
generated `Auth.php`). This is a prerequisite for R4/R5 — media DCs are usually
not the account DC and TDLib never re-runs the full DH handshake there.

**R4 — File-scoped DC routing with `FILE_MIGRATE` auto-retry.**
Model `FileLocation{fileType, id, access_hash, dc_id, file_reference}` and
carry `dc_id` on the file entity so `upload.getFile` is dispatched to the file's
DC directly. On `FILE_MIGRATE_X`, re-dispatch to DC X with the same body (TDLib
does exactly this and callers never see it).

**R5 — File reference registry with source-based repair.**
Persist a per-file reverse map `file_id → sources[]` (message full id, user
photo id, web URL, chat id…). On `FILE_REFERENCE_*` (code 400) errors, drop the
stale reference and re-fetch the owning object through its source method,
coalescing concurrent repairs per file and rate-limiting to one repair / 60 s
(TDLib semantics). Bots can skip repair (matching TDLib's `is_bot` exemption).

**R6 — Chunked download with resume + parallelism.**
Implement `upload.getFile` part fetches at 512 KiB (const `MAX_PART_SIZE`),
persisted part bitmask for resume (mirror TDLib's `PartialLocalFileLocation`),
16-byte offset alignment, and a small concurrency cap (2 active) matching
`ResourceManager` semantics. Keep small fetches on a separate lane if HTTP-like
latency becomes a concern; adopt `upload.getFileHashes` only for
integrity-sensitive reuse (later phase).

**R7 — Runtime DC option discovery (far term).**
Replace the hardcoded `DC_IPS` const with a `DcOptionsStore` fed by
`help.getConfig` (`dc_options`, `this_dc`, `webfile_dc_id`), `updateDcOptions`
pushes, and — only when unreachable — the DNS `configSimple` fallback, merged
with live-update precedence and persisted under a `dc_options_update`-style key.
This unlocks test-DC support and auto-recovery when Telegram rotates endpoints.

**R8 — CDN support (far term, only after R4–R6).**
Listen for `upload.fileCdnRedirect`, fetch per-DC CDN RSA keys, `getCdnFile`
chunks with per-chunk AES-CTR key/IV, and `reuploadCdnFile` on token expiry.
Reuse the `DcId.external` marker introduced in R1.

Implementation order implied by dependencies:
R1 → R2 → R3 → (R4+R5) → R6 → R7/R8.