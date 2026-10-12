# TStore — Telegram-backed File Storage (bucket-style, update-aware)

The storage verbatim (2026-09-20) surface: an archive **and** a
bucketed object store where the bucket is a Telegram channel, hot reads
are served from a MinIO-class cache, and media that flows through the
ingest pipeline is indexed so it can be fetched back by its Telegram
identity. Files land on Telegram either **raw** (readable by anyone who
can see the channel) or **sealed** (password-encrypted via Argon2id +
secretstream); separately, *data channels* — channels you post media to
for reuse elsewhere — get their documents indexed into `tf_store_media`
with the final Telegram hash, size, and mime.

> **Where it lives.** Everything is under `MeRezaRezaei\Teleframe\Store`
> (+ `Laravel\Store`, `Laravel\Console\TstoreCommand.php`, the `TStore`
> facade, and `Teleframe::store()`). The six container bind keys are
> public and stable: `teleclient.tstore.{channel-factory,index,hot-cache,queue,schedules,service}`.

## Concepts

- **Bucket = channel.** A bucket is a named Telegram channel this account
  created or joined. Bucket names carry a kind prefix (`teleframe-store:`,
  `teleframe-data:`, `teleframe-backup:`) so the registry can classify
  them (`ChannelRegistry`); the *data* kind is the media-indexing special
  case. One `StoreService` instance = one account; a channel factory
  resolves bucket → `TelegramStoreChannel`.
- **Object = files of one bucket.** `put` splits your file into chunks
  by the Telegram cap math (`ChunkPlanner`: 512 KiB parts, ≤ 4 GiB
  premium / 2 GiB free), uploads each chunk as a force-file document
  *named by its hash*, then posts a `TFSTORE1:` text message carrying a
  pretty-printed JSON manifest (`MetadataMessage`). `get` reads the
  manifest, fetches chunks by hash, and reassembles; `delete` removes the
  objects, messages, and cache entries.
- **Raw vs sealed.** `put --mode raw` posts plaintext chunks; a user who
  can read the channel can download and reassemble. `--mode sealed
  --passphrase …` seals each chunk with its own XChaCha20-Poly1305
  secretstream (24-byte header prepended, single `FINAL` push — the
  canonical single-seal wire). The manifest stores `wire_sha256`
  (ciphertext hash) + `sha256` (plaintext hash). Raw and sealed objects
  can coexist in one bucket.
- **Hot cache (S6).** Fetched chunks are fanned into a MinIO-class cache
  (`HotCacheContract`: `MemoryHotCache`, `MinioHotCache` over
  `S3GatewayContract`) under `<bucket>/<sha>/<part>` so repeat reads are
  fast; the cache is **separate from Laravel storage**, TTL + LRU-budget
  driven, evicts cold entries (`prune`), and fails **soft** — a cache
  miss or MinIO outage falls through to the Telegram fetch, never breaks
  the API.
- **Queued uploads (S9).** `put --queued` enqueues an `UploadJob` JSON
  payload on the `tstore.upload` queue (topic exchange
  `teleframe.tstore`) instead of uploading inline; `teleframe:tstore
  work` drains it. `SyncUploadQueue` is the zero-dependency default;
  `AmqpUploadQueue` uses ext-amqp (RabbitMQ on `127.0.0.1:5672` by
  default) and skips loudly when the extension or broker is absent.
  - **A failed upload is durable, and it is never silently dropped.**
    The ack happens **last**: the disposition is made durable first, then
    the message is acked. On a handler failure the job is requeued with a
    stamped attempt count, up to `AmqpUploadQueue::MAX_ATTEMPTS` (3); past
    that it is moved to the **real dead-letter queue `tstore.upload.dl`**,
    which is declared up front rather than lazily on first failure, and the
    failure reason travels with the payload.
  - **The temp file is unlinked only after a SUCCESSFUL upload.** It used to
    be unlinked from a `finally`, which combined with the queue's
    ack-in-`finally` to make one transient failure total: the message was
    acked *and* the only copy of the bytes was deleted, so the retry had
    nothing left to send while the ledger row still read healthy. So: **a
    failed upload leaves the source file on disk, on purpose** — that is what
    the retry resends. If you find temp files accumulating, you have a
    genuinely failing upload path, not a cleanup bug.
- **Schedules (S10) — two separate seams, two separate actions.**
  `teleframe:tstore schedules` resolves `teleclient.tstore.schedules` and
  prints that publisher's `describe()` (`TstoreCommand.php:380`); it never
  calls `install()`, so it is a **probe that arms nothing**. The default
  binding is `NullSchedulePublisher`, whose `install()` answers
  `skipped (null schedule driver)` per schedule and whose `describe()` says
  "Nothing is scheduled — no job will fire."
  `teleframe:tstore schedule` (singular) is a *different* action probing the
  **database-backup** seam: the backup spec, the bound
  `TemporalBackupScheduler`, and the `backup` watchdog registration. Do not
  read one as the other.
- **`teleframe.tstore.schedules` selects a driver** — `null` (the shipped
  default) or `temporal` — read at
  `src/Laravel/Providers/TeleframeServiceProvider.php:687`. This page used to
  claim it drove Temporal; that was false, and the corrected statement is
  narrower than it looks:
  - **It is armed by an explicit operator action, not by boot.**
    `teleframe:tstore schedules --install` arms the tstore maintenance jobs
    (`tstore-hot-cache-prune`, `tstore-registry-sweep`); the backup seam is
    armed by `teleframe:tstore schedule --install`; notify by
    `teleframe:notify schedules --install`. Arming is never implicit, so a
    package install cannot start firing jobs. Nothing else calls
    `install()` on this seam, so no tstore maintenance schedule fires by
    setting the key. Under `temporal` the publisher prefers a host-bound
    `RecurringScheduleClient` verbatim, else the package's own SDK-backed
    client via `TemporalClientResolver`, and **refuses by name at resolve
    time** when neither exists — it never degrades to a null publisher that
    would report `skipped` while you believe the job recurs. `temporal/sdk
    ^2.19` is in **`require`** (it used to be in `require-dev`, which made
    every Temporal path unreachable after `composer install --no-dev`), so a
    production install already has it — you do not `composer require` it.
    What you still need is a running worker: see
    [usage/integration.md §6.6](usage/integration.md) and the queue-mismatch
    failure mode there, because an armed schedule with no worker on its task
    queue executes nothing and reports nothing.
  - **The task queue is the shared `TemporalSchedulePublisher::defaultTaskQueue()`,
    not a hardcoded `'default'`.** That one method reads
    `TELEFRAME_TEMPORAL_TASK_QUEUE` (falling back to `'default'`), and **all
    four** schedule seams use it — `tstore`, `notify`, `app` and
    `store.backup`. `tstore` and `notify` used to pass the literal
    `'default'` while `app` and `backup` read config, so a uniform setup
    still split the schedules across two queues behind one worker. The
    provider deliberately does *not* read a
    `teleframe.tstore.schedules_task_queue` key: that key is declared in no
    config file, and reading an undeclared key is the defect the app wiring
    had to undo. A host needing another queue binds its own
    `TemporalScheduleClient`, which wins verbatim.
- **The two schedule drivers with a real armed caller** are
  `teleframe.app.schedules` (app calls, `AppServiceProvider`) and
  `teleframe.store.backup.schedule.publisher` (the database backup,
  `StoreBackupServiceProvider`); both use the same posture above.

## Media indexing (S3/S11) — the "final hash from Telegram too" part

Three paths write into `tf_store_media` (keyed
`account_id + bucket + msg_id`, so every path is idempotent):

1. **History walk (exact, one-shot):** `teleframe:tstore index --buckets data:…`
   → `StoreService::indexDataChannel` walks the channel's messages and
   upserts one media row per document with the **Telegram-native**
   identity: `doc_id`, `access_hash`, `file_reference`, `size`, `mime`.
   Content `sha256` stays lazily `null` until bytes are pulled through
   the cache (content hashing requires the bytes; native hashes don't).
2. **Live updates (optional, promptness):** with `teleframe.tstore.listen`
   (`TELEFRAME_TSTORE_LISTEN=1`), `UpdateStoredTstoreListener` indexes a
   presence row the moment a data-channel document lands — right now, at
   message granularity — leaving `doc_id`/`size`/`mime` as zeros because
   the mirror defers the nested Document object. The next `index` walk
   overwrites the same natural key with exact values. Single-shot proof:
   redelivering the same update collapses onto one row (tested).
3. **Raw update path (exact, from the handler pipeline):**
   `StoreService::archiveMessageMedia(Update)` — S11's handler helper —
   takes the raw TL update array, and when it carries a document whose
   peer is a declared data channel, upserts full identity immediately
   (the bus path that has raw bytes). Non-document updates and
   undocumented peers are silent no-ops.

`TelegramStoreChannel::listAllRows` and the per-row `media.document`
extraction are the shared readers for both the walk and the helper.

## CLI — `php teleframe:tstore <action>`

| Action | What it does |
| --- | --- |
| `buckets` | List known buckets + kinds (`--buckets data` filters). |
| `sync` | Walk dialogs, classify by prefix, upsert the registry. |
| `put <bucket> <file>` | Upload an object (`--mode raw|sealed`, `--passphrase`, `--mime`, `--queued`). |
| `get <bucket> <name>` | Download by manifest name (`--passphrase`, `--out`). |
| `ls <bucket>` | List objects in a bucket. |
| `index` | Reconcile a data channel's media index (`--buckets data:…`). |
| `verify` | Re-fetch every chunk + assert hashes (`--passphrase`). |
| `prune` | Evict cold cache entries past their TTL/LRU budget. |
| `work` | Drain queued uploads (`tstore.upload`). |
| `schedules` | Probe only: resolve `teleclient.tstore.schedules` and print that publisher's `describe()`. Never calls `install()`, so it arms nothing. |
| `schedule` | Probe only: print the **backup** schedule spec, the bound `TemporalBackupScheduler` posture and the `backup` watchdog registration. A different seam from `schedules` above. |
| `backup` | One database-dump run: dump → sealed `put` → run-ledger row → heartbeat. |
| `publish <bucket> <msgId> <peer-json>` | Repost an indexed file to another peer **by its Telegram hash** — no download, no re-upload (`--caption=…`, or `--peer=…` instead of the third argument). |

The command is the container seam in one file: the six public bind keys
are resolved from the container and swapped offline in tests
(`TstoreCommandTest` uses fakes). The `TStore` facade and
`Teleframe::store()` hand you the same `StoreService` instance.

### Publishing by hash (`publish`)

For "posting them in our public channel we just pass the hash": the source
message must already be **indexed** (`teleframe:tstore index`), because the
`tf_store_media` row is what holds Telegram's `file_reference`. That row plus
the existing Telegram copy *is* the reference:

```bash
php teleframe:tstore publish data:pics 101 '{"_":"inputPeerChannel","channel_id":900,"access_hash":4242}' --caption="repost"
```

The file is never downloaded and never re-uploaded; the hash goes straight to
the wire as an `inputMediaDocument`, and Telegram serves it from the copy it
already has. The destination peer is validated first (it must be an
`inputPeer*`), and a source message with no stored reference **fails loudly
with a non-zero exit** — silently re-uploading would be exactly the cost this
feature exists to remove, so the CLI does not paper over it. In PHP the same
thing is `TStore::publishByReference($bucket, $msgId, $peer, $caption)`, a real
method on the facade rather than an `@method` docblock.

## Config (`config/teleframe.php` → `teleframe.tstore`)

`account_id` (env `TELEFRAME_TSTORE_ACCOUNT`), `passphrase`, `cache`
(`memory` default; minio requires the gateway + `S3GatewayContract`
bind), `cache_ttl_seconds`, `cache_max_bytes`, `bucket_prefix`
(`teleframe-store:`), `premium`, `queue` (`sync`|`amqp`), `amqp.*`,
`schedules` (`null`|`temporal`, default `null` — read by
`TeleframeServiceProvider`; see Schedules (S10) for what that does and does
not buy you), `schedules_dsn`, `schedules_namespace`, `listen`
(`TELEFRAME_TSTORE_LISTEN`, default
off).

## Safe-by-default posture

- The **index flag is off** (`listen=false`): the mirror is the source
  of truth; live indexing is a promptness optimization, never a
  requirement. Nothing on the store path touches the network until you
  run a command or call the service with a live channel factory.
- Cache failures are **soft** — never throw-through to the caller.
- The store never uses regex in `src/Teleframe/Store` (zero-regex rule).
- `SyncUploadQueue` + `MemoryHotCache` + `MemoryStoreIndex` are the
  offline defaults, so the CLI and tests run without credentials,
  RabbitMQ, MinIO, or Temporal.

See `.brain/superpowers/specs/2026-09-20-telegram-store-design.md` for
the design, `.brain/superpowers/plans/2026-09-20-telegram-store.md` for
the wave breakdown, and `tests/Store` + `tests/Laravel/{Console,Realtime}`
for the coverage.