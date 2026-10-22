> **Merged from teleclient (archived).** This package (teleframe) absorbed the teleclient
> consumer-capability layer in the Phase 2 module merge. Namespace is
> `MeRezaRezaei\Teleframe\*`; the wire keys (`tg:*`, consumer group `teleclient`)
> are unchanged and move with the data.
><br>

# Backup — Encrypted Telegram Channel/Group Vault

The Phase 4 surface: configured local sets → content-addressed chunks →
Argon2id + secretstream encryption → a private Telegram **channel or
group** per set via a dedicated session — manifest-first restore, no
server-side index, and a staleness verifier. `php teleframe:backup <action>` drives
everything; the shipped `driver` default is `telegram`, so a default install
either performs a real channel backup (once `account` is set) or refuses
loudly — it never stores nothing and prints green.

> **Warning — passphrase rotation trap.** Dedup keys on the *plaintext*
> chunk hash and the runner reuses the set's salt, so giving an existing
> set a new passphrase does **not** re-encrypt anything: unchanged chunks
> count as already-uploaded (their ciphertext is still sealed under the
> *old* key) and only newly-uploaded uniques use the new key. The channel
> then holds a mixed-key set that **no single passphrase can restore** —
> silent data loss on the next `restore`. To rotate a passphrase: point
> the backup at a **new set id** (new channel, new salt), `run` it fully
> under the new passphrase, `verify --passphrase` it, and only then
> retire the old set/channel.

## How it works

- **One channel OR group per set.** Each backup set gets its own private
  destination, titled `teleproto-backup:<setId>` (find-or-create on first
  run — no manual provisioning step, though pre-creating a peer with that
  exact title works too). The destination **kind** is configurable:
  `'channel'` (a private broadcast channel — the default, and
  byte-identical to the historical behaviour) or `'group'`. A group is
  created as a **supergroup** (`channels.createChannel` with
  `megagroup: true`), which is the group kind Telegram still supports and
  the only one you can add other people to — which is the point, if the
  reason you wanted a group is *"channels and groups have the ability that
  you can add someone into them for backup"*. An **existing** legacy basic
  group is usable too; it dials as `inputPeerChat` rather than
  `inputPeerChannel`, and the vault resolves that from the dialog row.
  Chunks are force-file documents *named by their hash*; manifests are text
  messages carrying `TBMANIFEST1:` + base64 of an AEAD-sealed blob; the
  latest manifest wins.
- **Resolve by id or by title.** A set can also name an explicit
  `peer_id`, which is resolved from the dialog list in preference to the
  title — so a peer renamed after it was configured stays reachable. An id
  the account **cannot see** fails loud and mints nothing: a vault pointed
  at the wrong destination is indistinguishable from a working backup, so
  the vault refuses rather than quietly archiving somewhere nobody reads.
  A broadcast channel never adopts a supergroup of the same title, and a
  group vault never adopts a broadcast channel — a supergroup is a
  `channel` object on the wire, so the `megagroup` flag is the only thing
  separating the two and it is what the vault matches on.
- **The metadata is the message context, sealed.** Every chunk's caption
  carries the set's own metadata — `{set, generation}` — as an AEAD-sealed,
  base64-encoded fragment behind a constant marker, using the same key the
  manifest is sealed under (so one decoder opens both, and a chunk's
  context can never be read by a different key than the manifest that
  claims it). Telegram sees a blob whose shape leaks nothing, which is the
  owner's *"a coding string so telegram cannot see what is inside"*. What it
  is deliberately **not** is the chunk's plaintext sha256 in the caption: a
  document labelled with the sha256 of its own plaintext is a
  chosen-plaintext confirmation oracle for anyone who can list the peer, so
  that stays out. The fragment is written once per generation, not once per
  chunk, so every chunk of a run agrees message-for-message.
- **Content-addressed chunks.** Files are split at `chunk_size` (default
  4 MiB) and keyed by the sha256 of their plaintext. Identical chunks —
  across files or across runs — upload once: a second unchanged run
  uploads zero (the manifest's `chunk_hashes` seeds the known set, and
  the per-set salt reuse keeps hashes stable).
- **Crypto** (see `VaultCrypto`, ported from the owner's sdd/backup):
  passphrase + 16-byte salt → 32-byte key via **Argon2id13**
  (`crypto_pwhash`, OPSLIMIT/MEMLIMIT MODERATE = 3 passes / 256 MiB);
  each chunk sealed with its own **XChaCha20-Poly1305 secretstream**
  (24-byte header prepended, single push tagged `FINAL`). Any truncation,
  reordering, or tampering fails loud at decrypt time.
- **Manifest-first restore.** The manifest — posted *last* — alone
  describes the set: version, set id, salt, created date, per-file
  `{path, size, chunk list}`, and the unique-chunk index. `restore`
  reads it, derives the key from its salt, fetches/decrypts/reassembles
  every file, and validates all paths (traversal-proof) before writing
  anything. Nothing is indexed server-side; the peer is dumb storage.

## Setup — dedicated session, channel provisioning

The telegram driver uploads through **one dedicated session** (a spare
user account or a bot): back up to a channel that account admins. Do not
reuse your primary poller account — quota and flood-waits then can't
starve ingestion.

1. Export a session string for the dedicated account (teleproto's
   `teleproto:login` or `TeleprotoAuthService`).
2. Register it under `teleframe.daemon.accounts` (the same shared
   registry the daemon/backfill use) — the backup command resolves its
   scope through that entry (`AccountWorker::buildLiveScope`).
3. Destinations are **provisioned automatically on first run**: the vault
   scans dialogs for `teleproto-backup:<setId>` of the configured kind and
   creates the private peer when missing. Pre-creating a peer with that
   exact title is equally fine (the exact-title scan finds it).

## Per-set keys

Every key below is read from the backup set row
(`teleframe.backup.sets.<setId>`). **The shipped config does not declare
`peer_type` / `peer_id` / `premium`** — `sets.default` ships `paths` and
`excludes` only, and each of the three defaults below is applied where the key
is read (`TeleframeServiceProvider.php:518,525` for the peer pair,
`BackupRunner.php:611-613` for `premium`). The ones this page documents beyond
`paths` / `excludes` / `chunk_size` are:

```php
'sets' => [
    'default' => [
        'paths'    => [base_path('docs')],
        'excludes' => ['.git', 'node_modules'],

        // 'channel' (default, private broadcast) or 'group' (supergroup).
        'peer_type' => 'channel',

        // Resolve THIS peer id instead of searching by title. An id the
        // account cannot see fails loud and mints nothing.
        'peer_id'   => null,

        // Is the ACCOUNT behind this set a Telegram premium account?
        // FALSE by default, so the free upload cap applies.
        'premium'   => false,
    ],
],
```

| Key | Default | What it does |
| --- | --- | --- |
| `peer_type` | `'channel'` | `'channel'` = private broadcast channel. `'group'` = a supergroup, created as one when missing; an existing legacy basic group is usable and dials as `inputPeerChat`. Anything else is refused at vault construction. |
| `peer_id` | `null` | Resolve this dialog id instead of by title. Wins over the title, so a renamed peer stays reachable. Never falls back to creating a peer. |
| `premium` | `false` | Whether the account is premium. Decides which per-document upload cap `chunk_size` is checked against (see *Limits* below). A non-boolean is a config error. |

`premium` is the same signal `teleframe.tstore.premium` uses, and it
defaults to **false** because the free cap is the ceiling that always works.

## Configuration

```php
// config/teleframe.php
'backup' => [
    // The accepted set is memory|telegram|s3 (any other value throws at bind
    // time). 'telegram' is the DEFAULT and 'telegram' and 's3' are both
    // DURABLE: each survives the process. 'memory' is the one non-durable
    // driver — a process-local InMemoryVault, gone when the process
    // exits, so it requires an explicit TELEFRAME_BACKUP_ALLOW_VOLATILE=1
    // acknowledgement and still prints a WARNING.
    'driver' => env('TELEFRAME_BACKUP_DRIVER', 'telegram'), // memory|volatile, telegram, s3
    'account' => env('TELEFRAME_BACKUP_ACCOUNT'),         // daemon.accounts account_id (telegram driver)
    'chunk_size' => env('TELEFRAME_BACKUP_CHUNK_SIZE', 4194304),
    'sets' => [
        'default' => [
            'paths' => [base_path('docs'), base_path('database/seeds')],
            'excludes' => ['.git', 'node_modules'],   // substring match on absolute path
            // per-set keys — peer_type, peer_id, premium — see "Per-set keys"
        ],
    ],
],
```

`driver => 'telegram'` is the default, so a backup is real out of the box
and needs `account` pointing at a daemon.accounts entry; without it the
command refuses loudly. `driver => 's3'` is the other durable option — one
bucket, no Telegram method at all. `driver => 'memory'` is the offline
driver, NOT the default: an in-memory vault that persists nothing, for smoke
tests and CI only.

## CLI

```bash
# The passphrase is NEVER an argument: use the env var, a file, or the
# hidden prompt (argv is world-readable in `ps` and lands in shell history).
export TELEFRAME_BACKUP_PASSPHRASE='...'

php artisan teleframe:backup run      --set=default
php artisan teleframe:backup status   --set=default
php artisan teleframe:backup verify   --set=default --sample=5
php artisan teleframe:backup verify   --set=default                    # full integrity (keyed)
php artisan teleframe:backup prune    --set=default
php artisan teleframe:backup restore  --set=default --target=/tmp/restore
php artisan teleframe:backup run      --set=default --passphrase-file=/run/secrets/tf-backup
```

- `run` — scan → chunk → encrypt → dedup → upload uniques → post
  manifest; prints `{uploaded, skipped, files, bytes}`.
- `status` — latest-manifest summary `{files, bytes, chunks, created,
  age_hours, max_age_hours}`; **fails** on a stale or missing backup (see
  *Staleness* below).
- `verify` — availability sampling (below); exits non-zero on problems.
  Prints a `corrupt` count on **both** the keyed and the keyless path.
- `prune` — chunk GC (below); keyless, so it is cron-safe like plain
  verify. Prints a loud console warning if it runs without its Redis lock.
- `restore` — manifest-first rebuild into `--target`; fails loud on a
  wrong passphrase (`Decryption failed`).

## Staleness — `status` is a health check, not an inventory

`status` used to print the manifest's `created` timestamp and return
`SUCCESS` unconditionally. Nothing compared that timestamp to now, so a
backup cron that had been failing for months produced no line, no exit code,
and — because the engine binds `NullLogger` by default — nothing in your logs
either. A backup that is not being taken is the failure nobody notices.

It is now evaluated, and it **fails**:

| State | Result |
| --- | --- |
| No manifest at all | `FAILURE` — no backup has ever been written for the set, so there is nothing to restore. A set with no backup is not a healthy set. |
| Newest manifest older than `teleframe.backup.max_age_hours` | `FAILURE`, printing the age and the bound. Every hour above the bound means the run is failing or not scheduled. |
| Within the bound | `SUCCESS`, with `age_hours` and `max_age_hours` in the line so you can see your headroom. |

The bound defaults to **24 hours** (`BackupCommand::DEFAULT_MAX_AGE_HOURS`) —
right for the default daily cron, wrong for a weekly one, so set it:

```php
// config/teleframe.php
'backup' => ['max_age_hours' => 24 * 9],
```

## Verifier semantics — availability vs integrity

`verify` is deliberately **keyless**: it samples N (default 5, `--sample`)
chunk hashes from the latest manifest, re-downloads each, and counts
`ok` when the chunk arrives non-empty; `missing` counts fetch failures.
Integrity is *not* re-proven here — it is structural: every chunk is a
single-message secretstream sealed `FINAL`, so corruption fails loud at
restore time. This keeps the routine staleness check secret-free (cron-
safe). Both paths now print a `corrupt` count, so a chunk that is present
but has rotted is no longer invisible on the keyless path (it is expected to
stay `0` there — `corrupt` only ever increments on the keyed path, which
decrypts and compares plaintext sha256 against the content address). The
keyless exit code fails on `missing > 0 || ok < checked`; the keyed one fails
on `missing > 0 || corrupt > 0`.

## Pruner semantics — chunk GC

Files deleted (or changed) between runs drop their chunks out of the new
manifest, but the peer kept them forever — `prune` closes that. The
latest manifest's `chunk_hashes` is the keep-set; `prune` walks every
vault message (the vault lists all via `findMessagesByName('')` — for a
Telegram vault that is `messages.search` with an empty q, which returns
every message, merged with realtime history) and deletes each entry that
is chunk-shaped (exactly 64 lowercase hex chars — a sha256 content
address) yet outside the keep-set. Manifest messages and anything not
chunk-shaped are structurally immune, `delete` is idempotent, and a
repeated prune prints `pruned:0` — safe to cron right after every `run`.
Prints `{scanned, pruned}`; needs no passphrase (chunk names are
plaintext hashes). Note prune only ever sees the LATEST manifest, so run
it after a successful `run` — pruning against a stale manifest would
delete chunks the newest backup still needs (the keep-set comes from the
manifest, so in practice this means: never prune while a run is mid-
flight).

**The lock, and what happens without it.** The prune's Redis lock is what
prevents deleting chunks an in-flight backup is still uploading. When Redis
is unavailable the prune **proceeds anyway** — and that degradation used to
reach the operator only as a PSR-3 warning, which the `NullLogger` default
swallowed, so the command printed a clean success line and said nothing. It
is now printed **on the console**, where no logger can silence it:

```
WARNING: prune set=default ran WITHOUT the lock (Redis unavailable) — an in-flight backup may now reference deleted chunks.
```

If you see that line, treat the next restore as suspect until a fresh full
`run` completes and `verify` comes back clean.

## Security notes

- **Passphrase handling**: env `TELEFRAME_BACKUP_PASSPHRASE`,
  `--passphrase-file=PATH`, or a hidden prompt — NEVER `--passphrase=`
  (argv is visible in `ps` and in shell history); never logged or echoed. Lose it
  and the chunks are unrecoverable by design (Argon2id + AEAD, no
  escrow). The salt is public (manifest), the key never leaves the
  process. Rotating the passphrase means rotating the set id — see the
  rotation trap warning above.
- **No server-side index**: Telegram sees ciphertext blobs, file names
  (hashes), peer titles, and message timing — nothing else. The chunk
  caption's metadata fragment is sealed, so the set id and generation are
  not legible on the wire either. Do not put secrets in set names/paths.
- **Peer hygiene**: keep the vault peers private and empty of other
  traffic — the vault scans by title (or by the id you pinned) and exact
  chunk names. A **group** widens the audience to its members: the
  manifest is AEAD-sealed and chunks are named by ciphertext, so a member
  cannot forge an index or confirm a plaintext, but they can *delete*
  messages (which `verify` and `status` will report) and read the traffic
  volume. The passphrase remains the thing that protects the content.
- **Confirm-guess leak (closed)**: a document named by the sha256 of its
  own **plaintext** is a chosen-plaintext confirmation oracle — a peer
  reader holding a byte-exact guess of a chunk's content could confirm or
  deny it just by looking for that hash among the messages, no key needed.
  Chunks are named by the sha256 of their **ciphertext** and the
  ciphertext → plaintext book lives inside the sealed manifest, so that
  oracle is closed. What a peer reader can still observe is the document
  *count* and *size* per run — which is why vault peers must stay private
  and third-party-free.

## Limits

- **Chunk size vs upload cap**: a chunk here is uploaded as **one whole
  document**, so `chunk_size` is capped at Telegram's per-document limit —
  `2097152000` bytes (1.953125 GiB) on a free account,
  `4194304000` bytes (3.90625 GiB) on a premium one, read from
  `Store\ChunkPlanner` rather than restated here. The cap that applies is
  **the account's**, declared with `premium => true` on the set (see
  *Per-set keys*); the default is free. That check is at *configuration*
  time on purpose: a `chunk_size` above the free cap used to validate
  cleanly and then fail at upload time, after the whole set had been
  scanned, chunked and encrypted. At the 4 MiB shipped default a chunk is
  ~0.1% of the smallest cap, so neither cap is reachable in practice.
  Uploads go through teleframe's 512 KB `upload.saveFilePart` loop either
  way.
- **Dedup scope** is per set (salt reuse is the linchpin: same passphrase +
  same salt → same chunk hashes). Different sets don't share chunks.
- **Garbage collection exists but is manual**: chunks that drop out of
  every file list (deleted/changed source files) stay in the channel
  until you run `prune` — it GCs exactly those orphans against the
  latest manifest (see Pruner semantics above). Telegram storage is the
  only cost of delaying it.
- **Telegram-driver coverage**: beyond the offline wiring (fake call
  map), the telegram driver is exercised only by the live-gated smoke
  (`tests/Backup/LiveVaultSmokeTest.php`, skipped unless
  `TELEFRAME_LIVE=1` plus a real session in `../teleproto/.env` or
  the environment) — run it manually before relying on the driver.
- **`memory` driver** never persists — it exists for offline tests.
