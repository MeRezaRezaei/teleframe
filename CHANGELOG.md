# Changelog

All notable changes to `teleframe` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

> The `[Unreleased]` block is kept **above** the last release, per the
> Keep-a-Changelog order this file's own header claims to follow. It used to
> sit *below* `[1.3.0]`, which meant the newest work in the repository —
> including the supervisor — was invisible to anyone reading top-down.

### Fixed — first-release: three integrations were wired but inert, and one lost data

`b0d05c53`. A host installing this package and pointing it at Redis, RabbitMQ
and Temporal got green health signals and a system that did nothing. Every
finding was confirmed against the shipped code and carries a test.

**Distribution — a production install could not have worked**
- `temporal/sdk` moved from `require-dev` to `require`. After
  `composer install --no-dev` the SDK did not exist, so every Temporal path
  was unreachable. **Do not `composer require temporal/sdk` yourself.**
- `archive.exclude` no longer drops `/bin` wholesale — the dev scripts are
  excluded individually, but **`bin/teleframe-worker` ships**, because it is
  the only way to serve a tick.
- `ext-amqp` is now declared in `suggest`: the hard requirement of the tstore
  and notify RabbitMQ drivers, which refuse to arm without it.

**Temporal — schedules armed, nothing executed, in total silence**
- `TELEFRAME_TEMPORAL_TASK_QUEUE` is the one operator-facing task-queue knob,
  resolved on the serve side by the worker and on the arm side by all four
  publishers via `TemporalSchedulePublisher::defaultTaskQueue()`. Precedence
  on the worker: `--task-queue` flag → env var → `'default'`. The worker
  prints which it used and warns to compare it against the queue the arm
  command reported — a worker on the wrong queue blocks forever with no
  error.
- `tstore` and `notify` no longer hardcode `'default'`; all four seams
  (`tstore`, `notify`, `app`, `store.backup`) share one default and cannot
  split across queues behind one worker.

**RabbitMQ — a transient failure destroyed the file and the job**
- `tstore.upload` acks **last**, after a durable disposition: a bounded
  requeue (`MAX_ATTEMPTS` 3) then the real `tstore.upload.dl` dead-letter
  queue. The temp file is unlinked only after a **successful** upload, so a
  failed upload leaves it for the retry instead of deleting the only copy.
- The notify adapter reconnects on a broker restart, and logs and parks
  dead-lettered jobs.
- `teleframe.notify.amqp.user` / `.password` no longer default to
  `guest`/`guest`; the notify path runs the same `assertUsableIdentity()`
  guard as the store path, so stock credentials against a **remote** broker
  are refused.
- `queue=auto` on notify now tries **RabbitMQ before the Laravel host queue**
  when a broker host is configured.
- Nine new, genuinely-read keys: `teleframe.tstore.amqp.{heartbeat,
  connect_timeout, read_timeout, write_timeout,
  publish_retry_budget_seconds}` and `teleframe.notify.amqp.{heartbeat,
  connect_timeout, read_timeout, write_timeout}`. The declared surface is
  160 keys / 150 READ.

**Redis — the daemon produced updates and consumed nothing**
- `teleframe:daemon` drains the bus on **every rotation in its default
  mode**, not only under `--ingest-only`. The drain logs
  `[bus] drained N entries (M forwarded)`; a failure logs
  `[bus] drain failed: …` and cannot take the daemon down.
- `teleframe:ingest` creates the Redis consumer group itself
  (`XGROUP CREATE … MKSTREAM`, `BUSYGROUP`-tolerant). A fresh Redis no longer
  dies on its first batch with `NOGROUP`.
- `LaravelRedisAdapter` gained `xpending`, `xautoclaim`, `xrange`, `xdel`,
  `hdel` and `xadd(..., ['maxlen' => N])`, so consumer-crash reclaim,
  dead-letter replay and the ops dead-letter view work against a real Redis
  rather than only on the in-memory double.
- The update stream is bounded (`RedisStreamSink::DEFAULT_MAXLEN` = 50,000,
  approximate `MAXLEN ~`) and the dead-letter stream at 10,000. The strikes
  hash no longer grows per successful entry.

**Routing and sessions**
- **Added `teleframe:routing <account>`** — the write path that turns
  `act_on` on, and therefore the only reason `onMessage` handlers can fire
  at all. `account` is a required argument; `--mode=act_on` is the default,
  with `--peer`, `--peer-id`, `--account-wide`, `--priority`, `--dry-run`.
  Nothing previously in the package could write `MODE_ACT_ON`, and there is
  **no config key that sets the routing mode**.
- **A revoked auth key is now terminal.** It used to be treated as transient,
  so `pollUser()` spun at 2 s per iteration forever with no output and the
  daemon printed an unconditional `Daemon stopped.` and exited `1` — an
  unbounded restart loop under any `Restart=always` policy. It now rethrows
  by name, the daemon prints the per-account errors and tells the operator to
  re-run `teleframe:login`, and `Daemon stopped.` is printed only on a clean
  exit. **Note for operators on a restart-always policy: the process will
  still be restarted; fix the session rather than adding backoff.**
- The daemon gained a default session resolver (bound via `bindIf`, so a host
  binding its own still wins), so a host whose accounts live in the database
  no longer has every row skipped.
- `teleframe:doctor` gained `--account=` to probe a **stored**
  `telegram_accounts.id` session instead of the throwaway key it always used
  (which went green for a revoked session), and a bounded Redis/bus probe
  reporting `PING`, `XLEN tg:stream:updates`, whether consumer group
  `teleclient` exists, and the dead-letter size. A missing group is a
  `FINDING`, not a pass. `--bus-timeout=` bounds each call.
- `teleframe:backup status` now **fails** when the newest manifest is older
  than `teleframe.backup.max_age_hours` (default 24 h), or when there is no
  manifest at all — a backup that stopped running is no longer undetectable.
  `prune` prints a loud **console** warning when it runs without its Redis
  lock (that combination could delete an in-flight backup's chunks), since a
  PSR-3 warning alone was swallowed by the `NullLogger` default. Keyless
  `verify` now prints the `corrupt` count on both paths.


### Added
- **A Temporal worker, so an armed schedule is a job that runs and not a green
  line.** `SdkTemporalScheduleClient` arms a real server-side Temporal schedule
  whose action is `StartWorkflowAction::new($type)` — the **workflow type is a
  raw string** — and until now NOTHING in this package implemented any of those
  strings. `grep -rln "WorkerFactory|registerWorkflowTypes|@WorkflowInterface|
  @ActivityInterface" src/` returned **zero files**: no worker bootstrap, no
  `#[WorkflowInterface]`, no `#[ActivityInterface]`, no entry point. So a host
  that set `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` (or the app / notify
  equivalents) and installed `temporal/sdk` got
  `armed %s recurring every %ds` for a schedule whose **every tick started a
  workflow type nothing implemented**, and `describe()` counted it as armed.
  - **This is the same shape as the false greens this cycle has been removing,
    one level up** — a class that exists, a config key that is read, a test that
    constructs the object, a capability that is unreachable. It is specifically
    a **regression**: before `729452d1` the `temporal` driver could only throw a
    LOUD, NAMED `RuntimeException` telling the operator to bind a client.
    Replacing that honest failure with a silent success made it worse.
  - **`TemporalWorker`** (`src/Teleframe/Store/Schedules/Worker/TemporalWorker.php`)
    registers the workflow types the three spec producers emit —
    `teleframe:tstore backup` and `teleclient.notify.check-no-show` (both fixed)
    and one `telegram.app.run-call {app} {call}` type **per declared (app, call)
    pair**, since the type embeds both names and Temporal dispatches on the name
    the server sends. Activities: `teleframe.store.backup-dump`,
    `teleframe.notify.check-no-show`, `teleframe.app.run-call`, each delegating
    to work the package already had (`DatabaseBackupService::backup()`,
    `NoShowWatchdog::check()`, `AppRuntime::runCall()`) — so a scheduled dump and
    a manual `php artisan teleframe:tstore backup` share one code path.
  - **Entry point: `php bin/teleframe-worker`** (`--describe` prints the served
    types with no server; `--app=… --call=…` declares the app-call pairs;
    `--task-queue=` must match the queue the schedules were armed on).
  - **`temporal/sdk` stays require-DEV, so the SDK's absence is LOUD and BY
    NAME.** No SDK class appears in a signature or property, so the whole
    `Worker/` tree loads and constructs with the SDK absent; `register()`/`run()`
    then throw naming `composer require temporal/sdk` and the entry point, and
    never return an empty worker. A `ContainerResolver` seam (the same shape as
    `TemporalClientResolver`'s `$sdkFactory`) lets a test exercise that guard in
    a checkout where the SDK *is* installed.
  - **DISCLOSURE — the part that makes it honest rather than merely present.**
    `TemporalSchedulePublisher` now states, per armed schedule, whether a worker
    in **this package** serves that type. An uncovered type reads
    `worker: NOT SERVED by any worker in this package … every tick of this
    schedule will fail to start a workflow` instead of a green `armed` line, and
    `describe()` repeats it plus the standing "the driver is only the SCHEDULE
    side" sentence naming the entry point and the task queue.
    `ScheduleWorkerCoverageTest` is the honesty-property test: arm a schedule for
    a workflow type the package does not serve and the status MUST say so. It is
    the test that would have caught this defect.
  - **Honest limits: there is no live Telegram run and no live Temporal server
    in this repository.** Registration is proved against the real SDK's own
    `WorkflowReader`/`ActivityReader` on a real `Worker`
    (`TemporalWorkerRegistrationTest`), and the SDK-absent path is proved
    refusing; "a server dispatches a tick to the handler and the activity runs"
    is **unverified**, and both `TemporalWorker::describe()` and the publisher
    say so in their own output.
- **Telegram Passport reads the MTProto claim channel too, so Passport login
  is no longer Bot-API-only (2026-09-27, `068a8829`).** §6 of
  `docs/reference/telegram-passport-official.md` puts a Telegram-**verified**
  email/phone in exactly TWO places, and this package read only the first. The
  `SecureValue` traversal was documented in `PassportVerifiedClaims`' own
  docblock as ABSENT, so a field-mode or anonymous login on an
  MTProto-shaped update was refused — and the owner's requirement, "it must
  be able to let laravel users login using it when one configures them", is
  exactly what that refusal left half-met, on the shape MTProto actually
  speaks.
  - **`SecureValueTraversal::fromValues()`**
    (`src/Teleframe/Passport/SecureValueTraversal.php:146`) walks every
    `SecureValue` on the `values: Vector<SecureValue>` sibling of
    `SecureCredentialsEncrypted`: the unconditional `type`, the nested `data`
    (counted, never decrypted — it is opaque AES), `front_side` /
    `reverse_side` / `selfie`, the `translation` and `files` vectors, and
    `plain_data`. Every branch it reaches is recorded and observable
    (`branchesVisited()`, `secureDataCount()`, `fileCount()`), so a test
    proves a branch was visited rather than inferring it. The raw wire arrays
    and the shipped generated DTOs are both read by ONE rule, so the two
    spellings cannot drift apart; the generated artifacts are read, never
    edited.
  - **A leaf becomes a claim only under the §7 type table** — only
    `secureValueTypeEmail` / `secureValueTypePhone`, and only from a
    `securePlainEmail` / `securePlainPhone`. A `plain_data` on any other type
    is a protocol violation and yields nothing. Two leaves that DISAGREE yield
    NO claim, the same rule the Bot API element list already followed: picking
    a winner would let whoever can append a second `SecureValue` choose which
    identity a login resolves.
  - **`PassportVerifiedClaims::fromSecureValues()`**
    (`src/Teleframe/Passport/PassportVerifiedClaims.php:221`) returns the SAME
    value object `fromPassportData()` produces, so nothing downstream can tell
    the two shapes apart. A walk that finds no claim still yields `none()`,
    and a field-mode login on that is still refused loudly — a shape yielding
    no claim is never downgraded onto some other coordinate.
  - **The new shape cannot become a way to SKIP the security check.**
    `EncryptedCredentials::updateCarriesCredentials()`
    (`src/Teleframe/Passport/EncryptedCredentials.php:123`) now reads the
    `values` sibling off the `messageActionSecureValuesSentMe` action, and
    `PassportLoginService::handleUpdate()` uses that ONE predicate instead of a
    private copy of the coordinate list
    (`src/Teleframe/Passport/PassportLoginService.php:123`). Without it, an
    MTProto-shaped update would have answered "not my update" and never reached
    the null-session refusal at all. That refusal — the CRITICAL
    account-takeover fix — is untouched and still fails the suite if removed.
  - **Tests:** `tests/Passport/SecureValueTraversalTest.php` and
    `tests/Passport/MtprotoPassportClaimsTest.php`; 334 tests / 1491 assertions
    green, phpstan L5 clean, zero regex in the tree, smoke exit 0. Seven mutants were
    tried and each broke the suite; the single survivor is an equivalent
    mutant, argued in
    `docs/superpowers/specs/2026-09-27-mtproto-securevalue-claim-traversal.md`.
  - **Honest limits, and they are NOT narrowed by this entry.** The fixtures
    are **spec-derived**, which defends against our own misreading and not
    against Telegram's. **No live run exists**: nothing in this module has
    been executed against real Telegram data, and no live Passport gate has
    ever been run, opt-in or otherwise. The traversal **COUNTS**, it does not
    decrypt — `getFile` download and `PersonalDetails` / `IdDocumentData` /
    `ResidentialAddress` modelling remain absent. And the chain's edges are
    still Bot-API-shaped: the sender telegram id is read from
    `message.from.id`, which a pure MTProto update does not have, so reading it
    off the MTProto peer is a separate piece of work.

- **A real Temporal client ships, so `temporal` is a door and not a wall
  (2026-09-27, `729452d1`).** The `SchedulePublisher` /
  `RecurringScheduleClient` seam existed and had **NO in-tree implementor** —
  the only implementations anywhere in the tree were two anonymous classes
  inside tests — so `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` and
  `TELEFRAME_APP_SCHEDULES=temporal` could only ever throw, and a recurring
  dump or a recurring app call had no reachable path that was not host-written
  PHP. Capability 3 below already carried the correction to that driver's
  description; the entry describing the delivery was the missing one.
  - **`SdkTemporalScheduleClient`**
    (`src/Teleframe/Store/Schedules/SdkTemporalScheduleClient.php:67`) is a
    production `RecurringScheduleClient` over `temporal/sdk`.
    `getHandle()->describe()` upserts, so re-installing refreshes ONE schedule
    instead of colliding with itself; the cadence is a real `IntervalSpec` and
    `intervalSeconds <= 0` is refused.
  - **`TemporalClientResolver`**
    (`src/Teleframe/Store/Schedules/TemporalClientResolver.php:51`) is where a
    client comes from: a **host binding wins verbatim** (existing third-party
    implementors keep working untouched) → the SDK-backed client → a named
    `RuntimeException`. It never returns `null`, because a silently-disabled
    backup schedule while the host believes it is armed is the worst available
    failure. Both providers resolve through it, and resolve it from the
    container when a host binds one
    (`src/Laravel/StoreBackupServiceProvider.php:412`,
    `src/Laravel/AppServiceProvider.php:248`).
  - **`temporal/sdk` stays an OPTIONAL runtime dependency** — `require-dev` in
    this package, on purpose, reached only behind `class_exists` guards, so
    `bin/standalone-smoke.php` still constructs everything containerless.
    Nothing dials at boot; the client is constructed lazily.
  - **Honest limit: no live-Temporal-server verification exists in this
    repo.** The schedule path is pinned by tests against an injected client
    (`tests/Store/SdkTemporalScheduleClientTest.php`) and by the lazy / no-dial
    construction gate, never by a run against a real Temporal server.
  - **Still not wired to Temporal:** the `teleframe.tstore.schedules` and
    `teleframe.notify.schedules` keys. Both remain bound to a hardcoded
    `NullSchedulePublisher` (`src/Laravel/Providers/TeleframeServiceProvider.php:667`,
    `src/Laravel/NotifyServiceProvider.php:126`), so their `temporal` value is
    read by nothing. This entry does not claim them.

- **Telegram Passport can now LOG A USER IN, not just attach to one
  (2026-09-26).** The owner's requirement — "it must be able to let laravel
  users **login** using it when one configures them" — was not met by what
  shipped: all three Passport routes sat inside the host's `auth` guard and
  `PassportController::callback()` refused 401 without a session, so the flow
  ATTACHED a verified Telegram identity to an already-authenticated user and
  authenticated nobody. That was the one real blocker in Version One.
  - **A second route pair, outside the `auth` group**:
    `{prefix}/passport/login/start` and `{prefix}/passport/login/callback`,
    registered in their own `web` + throttle group. The attach routes stay
    behind `auth` exactly as they were.
  - **A separate capability switch, OFF by default**:
    `teleframe.passport.anonymous_login` (env `TELEFRAME_PASSPORT_ANONYMOUS_LOGIN`,
    default `false`). Enabling Passport to attach identities to accounts a host
    already manages does **not** thereby hand sessions to strangers. With the
    switch off both routes exist and answer 403, so the refusal is a decision
    you can see rather than a 404 you have to infer.
  - **A binding that cannot be skipped.**
    `PassportAnonymousLoginService::login()` takes a non-nullable
    `string $sessionFingerprint` with no default, a blank fingerprint is
    refused rather than digested into a constant, and both endpoints refuse 401
    before minting or consuming anything. A pending marker
    (`PendingLoginRegistry` — memory or PSR-16, atomically consumed when the
    store allows it) binds the trip to a SHA-256 fingerprint of the session that
    started it plus a digest of that trip's passport nonce, single-use and
    expiring. The `flow` handle travels in a URL, so it lands in referrer logs;
    this is why holding it, even with valid credentials, logs nobody in from
    another browser. This is the class of bug that produced two account
    takeovers in this module, so it is pinned at the signature as well as by
    behaviour.
  - **Identity comes from the Telegram-VERIFIED field, and only that.** Only
    `secure_data` entries are read; a bare top-level email/phone scalar is a
    claim Telegram never vouched for and is refused — unconditionally on this
    path, since the field is the entire identity. Exactly one vetted field, one
    equality, one host column (`email_column` / `phone_column`). A payload
    naming two vetted fields, or whose vetted value contradicts its own plain
    scalar, is refused rather than resolved. `require_verified` is still
    honoured and makes the path stricter, never weaker. An already
    authenticated browser is refused at both endpoints; the attach flow owns
    that case.
  - **`unknown_user`, defaulting to `refuse` and to the only shipped policy.**
    A verified field matching no user is refused loudly and NO row is written:
    whether any Telegram account holder may appear in the host's users table is
    the host's product decision. Any other value of the key is refused *and
    named back to you* rather than silently ignored.
  - **Throttled from birth**: `teleframe.passport.login_rate_limit` (default
    10/min per IP) on both anonymous routes, covering marker minting as well as
    credential presentation. `0` removes the package ceiling; the host's global
    limiter still applies.
  - Nothing sensitive is written anywhere: no session id, flow token, nonce,
    credential envelope or decrypted field value reaches a log line, a response,
    a redirect or a URL, and the marker store holds two digests.
  - **Tests**: `tests/Laravel/Http/Dashboard/PassportAnonymousLoginFlowTest.php`
    drives the whole journey through the real routes with no session — plus the
    cross-browser refusal, the replay, the expiry, the foreign-flow
    credentials, the unvetted claim, the ambiguous and self-contradicting
    payloads, the unmatched field, the leak scan, the throttle, and the
    default-off case. `tests/Passport/PendingLoginRegistryTest.php`,
    `SessionFingerprintTest.php` and `PassportAnonymousLoginServiceTest.php`
    pin the store, the formula and the signature. The 270 pre-existing Passport
    and dashboard tests are unchanged and green. New classes are registered in
    `bin/standalone-smoke.php`.
  - Two defects in the first draft of this feature were fatal and are fixed: a
    missing `use` for the shipped `PassportDecryptor` (so the path would have
    fataled on its first successful decrypt) and four missing `use` statements in
    `PassportServiceProvider` (so every anonymous binding was registered under a
    `Teleframe\Laravel\…` key nothing resolves, and both routes answered 500).
    Both are exactly what shipping an untested authentication path looks like.

- **Package-owned process supervision — `php artisan teleframe:supervise`
  (2026-09-26).** The host now writes nothing: no `config/horizon.php`, no
  polling job, no per-process unit file. One command supervises the shipped
  long-running commands out of a closed catalog — `daemon`, `ingest`,
  `tstore-work`, `relay` — from `teleframe.supervisor.processes` (default
  `daemon,ingest`), restarting with an exponential backoff ladder
  (5 s base, 300 s ceiling, `max_restarts=0` = unlimited) and **reaping its
  children on teardown** rather than orphaning them. `SuperviseCommand`
  (`src/Laravel/Console/SuperviseCommand.php`), the catalog
  (`src/Teleframe/Supervisor/ProcessCatalog.php`) and the loop
  (`src/Teleframe/Supervisor/Supervisor.php`); wired by
  `SupervisorServiceProvider`, a sixth auto-discovered provider. Three
  optional seams, none resolving a process at boot:
  `teleclient.supervisor.spawner`, `teleclient.supervisor.redis`,
  `teleclient.supervisor.specs`. An unknown `--process` name is a **loud**
  failure, never a silent skip.
  **Known limit, stated up front:** a supervisor's rows live in the memory
  of the supervising process, so `teleframe:supervise --status` and the
  dashboard ops snapshot report `supervised: []` from any *other* process
  while your supervisor is running perfectly well.
  `teleclient.supervisor.state` is **unbound by default** — binding a shared
  cross-process reader is the host's decision. Everything else (spawning,
  liveness, backoff, teardown) is real and exercised.
- **Live-test ready (2026-09-09):** `examples/.env.example` + quickstart live ladder (`doctor → login → me → walkthrough → test-e2e`); Pg collation ops note; credential-gated dry-run matrix in `docs/superpowers/plans/2026-09-09-live-readiness.md`. Offline gates green (999 tests); live gates remain opt-in, never CI.
- **PSR-3 logging seam (`psr/log` ^3, silent by default):** `LoggerInterface` is
  now resolved by the provider (host binding → `teleframe.logging.logger` FQCN →
  `NullLogger`). `UpdateDispatcher` logs handler failures and `TeleframeClient`
  logs MTProto RPC/batch failures at `error` level with structured context
  (method, constructor, account, exception); failures are ALWAYS rethrown —
  logging never swallows. Unconfigured, engine behavior is byte-for-byte
  unchanged (NullLogger no-ops).
- **`msg_container` batching — N independent RPCs in ONE round-trip.**
  `EncryptedConnection::callBatch()` packs up to 1020 prebuilt request bodies
  (32 KB container limit) into a single naked `msg_container` — one encrypted
  packet, one TCP write — and demultiplexes the per-request `rpc_result`s by
  inner msg_id: gzip-aware result unwrap, typed `rpc_error` resolution carrying
  the failing request's method context, one whole-batch resend after
  `bad_server_salt`, poison-frame cap. The container envelope uses the
  protocol-correct even (non-content-related) seq_no — live DC4 rejects an odd
  one with `bad_msg_notification` code 34. `Client::callMany()` validates and
  builds every body up front and returns key => result with input order
  preserved (fresh connections still pay the `invokeWithLayer` init once; warm
  connections send all N in one container); `TeleframeClient::callMany()`
  passes it through on the default user scope. Live-measured on production DC4:
  3 requests in one 21–33 ms container vs 58–81 ms sequential (**2.3–2.7×**,
  `php examples/batch-bench.php`).

### Fixed
- **Two schedule tests no longer let the ENVIRONMENT pick the branch
  (2026-09-27, `ee21e334` + `97413884`).** Both provider tests asserted that
  the `temporal` driver arms a recurring client, which is only true where
  `temporal/sdk` AND `ext-grpc` are present. The CI image has no `ext-grpc`,
  so the resolver refused and the tests errored — GitLab pipelines #203 and
  #204 — while the very same tests were green on the machine that wrote them.
  A green local suite is evidence about the machine, not about the code, and
  `ee21e334` claimed to pin both tests when it had pinned one (a rename script
  threw partway through and left the first test's old body in place).
  - Both providers now resolve `TemporalClientResolver` from the container
    when a host binds one and construct the default otherwise. That is a real
    seam for a host with an exotic connection strategy, and it lets a test pin
    the SDK outcome explicitly instead of inheriting the machine's.
  - The tests bind the resolver: one with a client, one with a factory
    returning `null` to model "`temporal/sdk` is not installed here". The
    SDK-absent refusal is now pinned where it is actually decided, in BOTH
    suites, rather than only where the SDK happens to be missing.
  - **Still outstanding, and NOT caused by this:**
    `BackupQueueDriverTest` has the identical environment-shaped defect for
    `ext-amqp`, and
    `RepositoryGuardTest::test_the_two_receipted_exposures_are_printed_on_every_run`
    expects two receipted exposures where the guard now prints one.
- **Generic TL method support — 823 methods parsed (was 812):** `TLSignatureParser`
  now understands schema type-variable declarations (`{X:Type}`) and generic
  bound-variable uses (`!X`/`%X`). All 11 previously-skipped methods are parsed,
  including `invokeWithLayer`, `initConnection`, `invokeWithBusinessConnection`,
  and `invokeWithReCaptcha` — zero regex added.
- **Test autoloading:** `composer dump-autoload` no longer warns; dev PSR-4 now
  maps the `Core/Bot/Laravel/Schema\Tests` sub-namespaces to their `tests/` dirs.
- **Docs + branding polish:** `AGENTS.md`, user-facing docs (`quickstart`,
  `bot-client`, `user-client`, `scaling`, `bus`, `telegram-passport`) and the
  changelog now carry the shipped `Teleframe` API (`TeleframeClient`,
  `TF`/`Teleframe` facades, `TELEFRAME_LIVE`, `teleframe:*` commands) —
  the legacy `Teleproto` identifiers are gone from user-facing material.

---

## [1.3.0] - 2026-09-25

First release assembled to answer one question honestly: **what does version one
actually contain?** Every verdict below is checked against code in this
repository (file:line), not against the goals ledger — the ledger has been wrong
before. Where a capability is partial, the exact boundary is stated so an
integrator never has to discover a limit by reading source.

The five capabilities are the ones declared verbatim in
`docs/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md:6-77`.

### Release scope at a glance

| # | Capability | Verdict |
|---|---|---|
| 1 | Notification/logger system → Telegram | **SHIPPED** |
| 2 | Telegram as permanent backup storage ("the s3 is good") | **PARTIAL** |
| 3 | Laravel DB auto-dump + hash-reference backup kind | **PARTIAL** |
| 4 | "whatever telegram activity needed using mtproto call both for user and bot" | **PARTIAL** |
| 5 | Telegram Passport login for Laravel users | **PARTIAL** |
| 6 | Bot and user apps that respond to updates | **SHIPPED** |

### 1. Notification / logger system → Telegram — SHIPPED

Log levels are Telegram forum topics. The 8 PSR-3 levels are the enum
(`src/Teleframe/Notify/Level.php:18-25`) and each level routes to a topic named
`app.{level}` (`src/Teleframe/Notify/Level.php:52`). Topics are a registry, not a
hardcoded table: `Topic::defaults()` (`src/Teleframe/Notify/Topic.php:58`) seeds
the predefined rows and `Topic::define()` (`src/Teleframe/Notify/Topic.php:69`)
adds/overrides any row with `name/level/description/chat/thread_id/throttle_per_hour`.
Host rows come from `teleframe.notify.topics` and are applied at resolve time
(`src/Laravel/NotifyServiceProvider.php:72-84`).

Long content is split into an inline section and files, exactly as declared.
`MessageSegmenter::split()` (`src/Teleframe/Notify/MessageSegmenter.php:56`)
keeps a brief + scalar metadata block within an inline budget (default 800
bytes, `src/Laravel/NotifyServiceProvider.php:104`) and pushes everything over
budget into an attachment; the full brief moves to the file and the inline copy
is trimmed with a truncation marker (`src/Teleframe/Notify/MessageSegmenter.php:99-109`).
`exception`/`trace` context keys are always diverted to the file, never inlined
(`src/Teleframe/Notify/MessageSegmenter.php:124`).

The files are browser-renderable HTML. `HtmlAttachment::render()`
(`src/Teleframe/Notify/HtmlAttachment.php:45`) emits a standalone
`<html lang="en">` document (`src/Teleframe/Notify/HtmlAttachment.php:108`) with
trace frames rendered as their own sections
(`src/Teleframe/Notify/HtmlAttachment.php:31`); it embeds no external
references, so the file renders identically straight off disk.

"Enhanced check on what one can send", including premium, is implemented:
`CapabilityCheck` + `CapabilityTable` are merged from
`teleframe.notify.capabilities` and shipped to the service
(`src/Laravel/NotifyServiceProvider.php:87`, `src/Laravel/NotifyServiceProvider.php:99`).

Sending is queued through a seam. The shipped default is in-process
`SyncNotificationQueue` (`src/Laravel/NotifyServiceProvider.php:135`); hosts
rebind `teleclient.notify.queue`. The AMQP driver is **not** shipped — selecting
it throws loudly (`src/Laravel/NotifyServiceProvider.php:142-144`).

The logger surface, reporting and the backup-gap alert all exist:
`Psr3NotifyBridge` (PSR-3 → topics), `ExceptionReporter` for Laravel exceptions
— **inert by default**, `teleframe.notify.exceptions.enabled` defaults to `false`
(`src/Laravel/NotifyServiceProvider.php:54-56`) because auto-sending every
exception to a chat is an operator decision — and `NoShowWatchdog`, which raises
`backup.missing` when a scheduled dump does not arrive
(`src/Laravel/StoreBackupServiceProvider.php:174-184`).

**Boundary:** AMQP queue driver not shipped (throws loud). Exception auto-report
is off unless explicitly enabled.

### 2. Telegram as permanent backup storage — PARTIAL

The storage engine is real: `StoreService` exposes `put/get/exists/buckets/objects/
size/delete` (`src/Teleframe/Store/StoreService.php:95-265`), a data-channel
index (`src/Teleframe/Store/StoreService.php:310`), integrity verification
(`src/Teleframe/Store/StoreService.php:571`) and pruning
(`src/Teleframe/Store/StoreService.php:589`). Content is sealed with a
passphrase before it leaves (`src/Teleframe/Store/StoreService.php:190`,
`src/Teleframe/Store/Queue/UploadJob.php`), and chunking is planned against
TDLib's real limits: 512 KiB parts, a **FREE_CAP of 2097152000 bytes
(1.953125 GiB) and a PREMIUM_CAP of 4194304000 bytes (3.90625 GiB)** — the free cap
is the default (`premium: false`), and neither is a round GiB figure
(`src/Teleframe/Store/ChunkPlanner.php`).

**The boundary:** the verbatim asked for "ftp or rsync or s3 api" and settled on
"the s3 is good" as the *external* large-object tier. That external tier is
**PARTIAL, not shipped end-to-end**. What exists is the S3/MinIO *hot cache*
seam — `S3GatewayContract` with a MinIO hot-cache driver
(`src/Laravel/Providers/TeleframeServiceProvider.php:474-502`) — which is a cache
in front of Telegram, **not** a durable offsite replica. Its default gateway
points at a local MinIO with default credentials
(`src/Laravel/Providers/TeleframeServiceProvider.php:493-502`), and it is a
cache: it is dropped under memory pressure, so it is not a backup tier. If you
need a true second copy off the Telegram DC, you bring your own S3 and drive the
store seams yourself. Channels/groups as backup peers are PARTIAL too:
`PeerMembership` exists and is composed at the call site by design, not as a
container seam (`src/Laravel/StoreBackupServiceProvider.php:54-63`).

### 3. Laravel DB auto-dump + hash-reference backup kind — PARTIAL

The hash-reference kind — the one the verbatim called "very important" for
keeping local storage free — **is shipped**. `publishByReference()` re-posts an
already-stored file to another peer by its Telegram hash with no download, no
upload and no temp file (`src/Teleframe/Store/StoreService.php:358`); a missing
index row throws loudly rather than silently re-uploading
(`src/Teleframe/Store/StoreService.php:368-372`). `reclaimLocal()` frees the
local file only *after* the hash row is proven durable, and returns `refused`
rather than claiming the disk was freed when it is not
(`src/Teleframe/Store/StoreService.php:413`, `src/Teleframe/Store/StoreService.php:452-467`).
The indexing side is `indexDataChannel()` and `archiveMessageMedia()`
(`src/Teleframe/Store/StoreService.php:310`,
`src/Teleframe/Store/StoreService.php:510`).

The DB dump is shipped: `MysqlDumper` / `PostgresDumper` behind a driver seam
(`src/Laravel/StoreBackupServiceProvider.php:117-141`), orchestrated by
`DatabaseBackupService` with a run ledger, a restore path
(`src/Teleframe/Store/Backup/DatabaseRestorer.php`), a gap watchdog, and a
Temporal schedule adapter (`src/Laravel/StoreBackupServiceProvider.php:275-286`).

**The boundary:** the dump is **driver-gated and schedule-gated**. With no
driver configured the bound dumper throws `BackupException` at `dump()` time
rather than silently no-opping (`src/Laravel/StoreBackupServiceProvider.php:130-139`).
The *scheduling* is the partial part, and it is now config-driven: the
`SchedulePublisher` is resolved from
`TELEFRAME_STORE_BACKUP_SCHEDULES` (`null` — the default, "off" — or `temporal`).
**This was dead config until Cycle 19**: the key shipped with the module,
documented as "'null' (off) or 'temporal'", and was read by nothing — the
provider hardcoded `NullSchedulePublisher`, so `install()` could only ever
report "skipped (null schedule driver)" and the recurring dump never fired
itself no matter how it was configured. Out of the box you now get the dump
engine, the ledger, the restore and the missed-dump alert; the schedule itself
is still **off by default** (a package that silently starts dumping a
production database on install would be a surprise). Setting
`TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` arms the recurring dump through
`TemporalClientResolver` (`src/Teleframe/Store/Schedules/TemporalClientResolver.php`):
a **host-bound** `RecurringScheduleClient`/`TemporalScheduleClient` wins verbatim
(existing third-party implementors keep working untouched), and with no host
binding the package's own **optional** `temporal/sdk`-backed client is used when
that package is installed. `temporal/sdk` is **not** a production dependency of
this package (require-dev only) and the SDK-backed client is constructed
lazily — nothing dials at boot. With **neither** a host binding nor
`temporal/sdk` installed, `temporal` **refuses at config time by name**, stating
what to bind or install; it never degrades to the `null` publisher, and an
unknown driver value also fails loudly instead of degrading to `null` — for a
backup, silently disabling the schedule while the host believes it is armed is
the worst available failure. On a stock install nothing is armed, and the driver
reports that instead of throwing. The same resolver backs the app-call schedule
driver (`TELEFRAME_APP_SCHEDULES`, `AppServiceProvider::registerSchedules()`).
**No live-Temporal-server verification exists in this repo**: the schedule path
is pinned by tests with an injected client and by the lazy/no-dial construction
gate, never by a run against a real Temporal server. There is also no multi-peer
target registry by default: `teleclient.store.backup.targets` binds `null`
unless `PeerTargetRegistry` and configured rows are present
(`src/Laravel/StoreBackupServiceProvider.php:247-263`), so the default posture is
a single owned channel.

### 4. "whatever telegram activity needed using mtproto call both for user and bot" — PARTIAL

This is the capability with the largest gap between the aspiration and what can
be called, so the boundary is stated precisely.

Both account kinds are wired. User MTProto is `UserAccountScope` and bot MTProto
is `auth.importBotAuthorization` (`CHANGELOG.md` v1.0.0, "Bot MTProto Scope");
`TeleframeClient` exposes `user` / `fromSession` / `bot` / `botMtproto`. The wire
speaks **Layer 227** (`src/Core/MTProto/Connection/EncryptedConnection.php`).

The real ceiling — there are **two** different numbers here and conflating them
is the trap:

- **All 1,008 method names in the registry are callable by string** through
  `call($method, $params)`. That is **823 MTProto + 185 Bot API** methods
  (`src/Schema/schema/methods-mtproto.json`,
  `src/Schema/schema/methods-botapi.json`, the two files
  `MethodRegistry::load()` merges; the merged map has 1,008 distinct names, so
  there are no cross-API collisions). Verified live: `messages.sendMessage`,
  `messages.getHistory`, `channels.getChannels`, `contacts.resolveUsername`,
  `upload.saveFilePart` and others all resolve ids from outside the curated
  set.
  **An earlier draft of this entry said "1,113 methods … 2,452 signatures
  total — 1,113 methods plus 1,339 type constructors", and claimed the
  823 figure was superseded. That was wrong on both counts, and it is
  retracted here.** The artifacts carry a `methods` map and **no `types`
  map at all** (`count($json['types']) === 0` for both files), so there is
  no constructor population to add to a method count, and the registry
  resolves 1,008. Anyone who sized a migration or a QA matrix off the
  1,113/2,452 numbers was working from a figure the package never had.
  **One related correction:** `call()` does **not** validate against
  `MethodRegistry`. It validates by encoding the body —
  `TLEncoder::encodeObject($method, $params)`
  (`src/Core/MTProto/Client.php:187`) — which resolves the constructor
  against **TLRegistry**, the loaded `.tl` scheme mirror, *not* the JSON
  method artefacts. Both cover the full catalogue, but they are different
  sources, and the honest statement is "any constructor the packaged TL layer
  knows", not "any name the JSON artefacts list".
- **Only 20 curated MTProto method groups and 10 curated bot-HTTP groups have
  fluent typed builders** (`src/Schema/config/curated-methods.json`). Asking
  `Methods::whatever()` for an uncurated group throws with instructions to
  curate it (`src/Core/Methods/Methods.php:68-70`).

So: broad wire reach is real, ergonomic typed coverage is deliberately narrow.
Note also that the schema catalogue is **Layer 229** while the wire speaks
**227** (`extra.telegram-layer` in `composer.json`); that gap is intentional and
must not be "fixed".

**The boundary:** there is no "whatever you want" convenience layer. Expect to
call uncurated methods as string + array, and add to
`src/Schema/config/curated-methods.json` when you want a typed builder. Bot HTTP
API and user MTProto are both available, but they are separate transports and
methods valid on one are not valid on the other.

### 5. Telegram Passport login for Laravel users — PARTIAL

The module is substantially built: `PassportLoginService::login()`
(`src/Teleframe/Passport/PassportLoginService.php:68`) runs the validate →
decrypt → import → bind → confirm chain; nonce registry, key loader, identity
resolver, binding writer and password confirmer are all bound as fixed
`teleclient.passport.*` seams (`src/Laravel/PassportServiceProvider.php:63-77`).
The wire seams are real and inert by default — with no bound scope the wire
refuses loudly instead of dispatching
(`src/Teleframe/Passport/UserScopePassportWire.php`). The package never calls
`Auth::login()` itself; the host-auth ride is the boundary and is installed only
when a Laravel auth factory is actually bound
(`src/Laravel/PassportServiceProvider.php:269-278`).

**The boundaries, all three real at release — but TWO of them have since
closed (in place, below) and a fourth has since opened:**
1. **Off by default.** `teleframe.passport.enabled` defaults to `false`
   (`src/Laravel/config/passport.php:27`). **Still true**, and it is a
   deliberate default: turning on Passport must not attach a verified Telegram
   identity to accounts the host already manages.
2. ~~**Session lookup only.** `user_lookup=field` throws because
   `FieldIdentityResolver` is not in the release.~~ **RETRACTED — this is no
   longer true.** `src/Teleframe/Passport/FieldIdentityResolver.php` **is
   shipped**, and `PassportServiceProvider::registerIdentity()`
   (`src/Laravel/PassportServiceProvider.php:154-178`) builds it. The real
   boundary is narrower and more interesting: `field` mode binds a login to a
   claim *inside* the decrypted payload, so the provider **refuses to build
   the resolver at all** unless `teleframe.passport.require_verified=true`
   (`src/Laravel/config/passport.php:61`) — the host must assert it has
   already verified that email/phone itself. Without the flag you get a
   `RuntimeException` naming `require_verified`, not a missing class. The
   resolver matches only `email` and `phone` and **refuses** a payload
   carrying both. `session` remains the shipped default
   (`src/Laravel/config/passport.php:52`). Anyone who read the old boundary
   and wrote a `FieldIdentityResolver` implementation, or deferred a feature
   because "the class does not exist", should re-read this entry.
3. ~~**No HTTP route ships.** The package registers no Passport controller or
   route — the ingest webhook carries the update, but the host must wire the
   login entry point itself. There is no `passport` dashboard controller under
   `src/Laravel/Http/Controllers/Dashboard/`. Still true.~~ **RETRACTED — this
   is no longer true, and it stopped being true before this section was
   written.** `PassportController`
   (`src/Laravel/Http/Controllers/Dashboard/PassportController.php`) **is
   shipped**, with three routes inside the `auth` group (`passport`,
   `passport/start`, `passport/callback` —
   `src/Laravel/Http/Controllers/Dashboard/DashboardRoutes.php:111-113`) plus
   the anonymous `passport/login/{start,callback}` pair in its own `web` +
   throttle group, OUTSIDE `auth` (`:177-180`), behind
   `teleframe.passport.anonymous_login` (default `false`, so the two login
   routes exist and answer 403 until a host opts in). See the *Added* entry
   above and `docs/usage/passport.md`.
4. **The MTProto claim channel — closed on the claim, still open on the
   documents.** `PassportVerifiedClaims`' docblock used to document the
   `SecureValue` traversal as ABSENT and refuse a field-mode login on an
   MTProto-shaped update. It is implemented: `SecureValueTraversal::fromValues()`
   (`src/Teleframe/Passport/SecureValueTraversal.php:146`) plus
   `PassportVerifiedClaims::fromSecureValues()`
   (`src/Teleframe/Passport/PassportVerifiedClaims.php:221`) read the §6
   `plain_data` leaves under the §7 type table and return the SAME value object
   the Bot API path returns. **What is still absent is everything the
   traversal deliberately does not do:** it COUNTS the document, file and
   selfie branches, it does not decrypt them, so `getFile` download and
   `PersonalDetails` / `IdDocumentData` / `ResidentialAddress` modelling are
   still not there, and the chain's edges are still Bot-API-shaped (the sender
   telegram id comes from `message.from.id`, which a pure MTProto update does
   not have). **No live Telegram run exists for either shape.**

**The verdict above stays PARTIAL**, and deliberately so: boundaries 1 and 4
are real, no re-request path ships (`setPassportDataErrors` /
`users.setSecureValueErrors` are not called), the module is identity-fields-
only, and nothing in it has been run against live Telegram.

### 6. Bot and user apps that respond to updates — SHIPPED

The sixth thing the release scope names (verbatim 2026-09-24:68, *"also we need
a way to make bot and user apps that are going to respond to"*) was built and
shipped, but the previous draft of this changelog listed only five capabilities
and omitted it — so a reader of the release notes would have concluded a
delivered feature did not exist.

`src/Teleframe/App/` (11 classes) is a declarative app substrate: `AppDefinition`
declares what an app listens for and how it answers, `AppRegistry` resolves a
routing rule to an app, `AppCall`/`AppCallSpec` describe an outbound call, and
`AppMode` separates bot from user behaviour. It is reachable from the host via
the `TelegramApp` facade, and the same `SchedulePublisher` seam that the
Temporal work now fills powers its scheduled side. Hot-reload of a running app's
account/bot bindings is served by the existing reload channel.

The honest limit: an app still needs its own handler, and browser E2E over it
cannot be validated without the maintainer in the loop (below).

### Known non-goals and limits for this release

- **Browser E2E cannot be validated without the maintainer.** The interactive
  surfaces (Mini App init-data, the `tg/` embedded web client, the dashboard)
  need a real browser and a real user session. Per the maintainer: "you cant
  validate it without me it needs interaction". Those paths are covered only by
  unit/integration tests and the plain-PHP smoke gate, never by an automated
  browser run. `bin/test-e2e.php` is a manual, credential-gated script.
- **GitLab CI is committed and complete; "it is running and green" is a claim
  about a runner this repository cannot see.** `.gitlab-ci.yml` ships four
  stages (`guard` / `verify` / `regen` / `pg`) and five jobs
  (`credential-guard`, `verify:8.3`, `verify:8.4`, `regen-idempotence`,
  `pg-tests` on a `postgres:17` service). The file is real and complete.
  Whether a self-hosted runner executes it is **not verifiable from the repo**,
  so this entry no longer asserts that the pipeline "IS RUNNING" and "is
  green" — a reader must point at an actual pipeline for that, exactly as the
  root README says. What the CI run *did* find, on the run that did go green,
  is durable and worth keeping: **seven real defects that locally-green runs
  could not** — the committed config had never been valid (an unquoted brace
  group parsed as a YAML flow mapping), `ext-sockets` was missing for
  `temporal/sdk`, the container's 128M `memory_limit` was fatal where local
  PHP runs `-1`, one test allocated 2 GiB to assert a chunk boundary, and —
  worst — three `catch (RuntimeException)` guards around the redis bus could
  not catch `Error`, so **any consumer without `ext-redis` would have failed
  to boot this package**. A local green run is evidence about a different
  environment, not about the gate.
- **The curated-method ceiling** is described in full under capability 4 above:
  **1,008 method names** callable by string (823 MTProto + 185 Bot API, no
  type-constructor population in the artefacts), against 20 + 10 curated
  fluent builder groups. A previous revision of this bullet claimed 1,113
  methods out of 2,452 total signatures and said the 823-method figure "the
  registry now resolves 1,113" superseded; both numbers were wrong.
- **Live gates are opt-in and never CI.** `composer verify` runs offline only.
  Postgres truth-track tests need `TELEFRAME_PG=1`.

### Fixed

- **Mirror schema artifacts now resolve from the package, not the host app.**
  `MirrorIngesterFactory` and `IngestGateCommand` both located their TL scheme
  and mirror catalogue with `base_path('schema/sources/...')` and
  `base_path('docs/superpowers/specs/...')`, which only resolves when the package
  *is* the application root. On a stock install
  (`vendor/merezarezaei/teleframe`) the mirror-ingester seam fataled with
  `scheme file not found`. Both now resolve through the new
  `SchemaArtifacts` locator, which reads from the package directory and keeps a
  `base_path()` fallback only for running the package as the app root
  (`src/Laravel/SchemaArtifacts.php`, used at
  `src/Laravel/MirrorIngesterFactory.php:48` and
  `src/Laravel/Console/IngestGateCommand.php:68`).

### Added

- **The version is declared as a git TAG, not in `composer.json`.** An earlier
  draft of this entry added `"version": "1.3.0"` to `composer.json`; the first
  real GitLab pipeline failed on it, because `composer validate --strict`
  promotes the warning *"the version field is present, it is recommended to
  leave it out if the package is published on Packagist"* into a non-zero exit.
  Composer is explicit that a published package takes its version from a tag,
  so the field was removed and the version is `v1.3.0` + subsequent tags. The
  field goes stale and can disagree with the actual commit; a tag cannot.
  Rationale for the release itself: the last prior tag was `v1.2.1` and this
  release is 310 commits ahead of it, carrying five new capability modules that
  no changelog entry ever described;
  a minor bump is the honest next number in the 1.x line. It is not a
  marketing "2.0" and not a re-used `1.0.0` — that tag already exists.
  **Where the tip actually is:** this package has **no version field**, by the
  decision recorded above, so the only authority on what you installed is the
  git tag (`git describe --tags`) and Packagist's resolution of it. At the
  time of this correction the newest tag is **`v1.3.2`**, and the supervisor
  work above is **after** it — i.e. untagged, and therefore not in any
  `composer require` resolution yet. `v1.3.1` and `v1.3.2` have no sections of
  their own in this file; the `[1.3.0]` section below is the last one with
  prose. Do not read `[1.3.0]` as "the current version".
- **Zero-config boot proof** as a permanent gate:
  `tests/Laravel/Support/ZeroConfigBootTest.php` mounts all **six**
  auto-discovered providers together and asserts that every `teleclient.*`
  seam resolves with an empty config, that no teleframe singleton is eagerly
  resolved at boot, and that the mirror schema artifacts resolve inside the
  package.

### Zero-config boot — what a fresh install actually does

Measured against a real host Laravel 13 app that installed the package from a
path repository (so the providers came from
`vendor/composer/installed.json` via
`Illuminate\Foundation\PackageManifest`, with no manual registration
anywhere). The provider list has since grown from five to six —
`SupervisorServiceProvider` joined it in `[Unreleased]` — so the measurement
below is a point-in-time reading, and the **permanent** proof is the test
named `test_all_six_declared_providers_are_mounted_and_boot_unconfigured`,
which passes today:

- **All 6 providers auto-discover** and all 6 register + boot with an empty
  config, along with the 7 facades (`TF`, `Teleframe`, `TStore`, `Notify`,
  `StoreBackup`, `TelegramApp`, `Passport`).
- **"56 of 58 resolved container bindings build with nothing configured" was
  an ad-hoc census of that one host app, and it cannot be re-derived from this
  repository** — there is no such count in the test, and the denominator
  moved when the sixth provider and the supervisor seams were added. Treat the
  *shape* of the finding as the durable claim, not the arithmetic: exactly two
  documented host contracts do not build unconfigured, and both fail
  **loudly**, never silently.
  - `teleclient.notify.service` needs the host's bot transport and template
    compiler. `NotifyServiceProvider` resolves `NotificationSender` from the
    container (`src/Laravel/NotifyServiceProvider.php:102`), and
    `NotificationSender` requires a `BotClient`, whose constructor takes a
    `string $botToken` that the container cannot autowire
    (`src/Bot/Services/BotClient.php:26-27`). This is a documented host
    binding, not a bug — but note the consequence: **with a literally empty
    app, the `Notify` facade throws a `BindingResolutionException` on first
    use** rather than resolving to an inert service. It never silently drops.
  - `teleclient.bus.redis` (`RedisConnectionContract`) dials the host's Redis
    connection when resolved. This is by design for a bus seam and it throws
    loudly on a misconfigured Redis; it is **not** a boot-time effect.
- **Nothing dials Telegram, connects to Redis, or writes to the filesystem at
  boot.** Zero network dials and zero files written were measured across
  `register()` + `boot()` for every provider present at the time of that
  measurement. `SupervisorServiceProvider` is deliberately lazy in the same
  way: it binds the spawner as a singleton whose default is inert, so
  resolving it opens no process (`proc_open` lives in `spawn()`, which only
  the supervision loop reaches), and its Redis binding degrades to `null`
  rather than throwing.

**One honest exception — a single eager resolve at boot.**
`teleclient.store.backup.watchdog` is resolved during `boot()`, not lazily, via
`StoreBackupServiceProvider::boot()` → `registerBackupWatchdog()`
(`src/Laravel/StoreBackupServiceProvider.php:107`,
`src/Laravel/StoreBackupServiceProvider.php:349-358`). It is reachable by
default because the shipped config turns the schedule on
(`teleframe.store.backup.schedule.enabled` defaults to `true`,
`src/Laravel/config/store-backup.php:35`), and that is deliberate — the
verbatim asked for a missed-dump alert without requiring Temporal. The resolve
itself performs **no** Telegram dial, no Redis call and no file write (measured:
0 network dials), and it is null-safe — a null watchdog simply skips
(`src/Laravel/StoreBackupServiceProvider.php:358-361`). The one caveat worth
knowing: the watchdog is built over the host's PSR-16 cache
(`src/Laravel/StoreBackupServiceProvider.php:174-180`), so a host whose *cache
driver* is Redis will reach Redis while booting Artisan. Set
`TELEFRAME_STORE_BACKUP_SCHEDULE_ENABLED=false` to make the boot fully lazy.

## [v1.0.0] - 2026-08-28

### ⚡ Highlights & Overview
- **Unified Telegram Protocol Engine**: First PHP & Laravel engine to seamlessly support both **Telegram Bot API (HTTP)** and **Native MTProto 2.0 (Binary TCP Sockets)** for both **Bots** and **User Accounts**.
- **100% Stateless Architecture**: No local SQLite databases or file locks; cryptographic state is encapsulated in portable session strings.
- **Zero-Reinvention Principle**: Cryptography, networking, and DOM parsing delegate directly to PHP extensions (`openssl`, `hash`, `dom`, `mbstring`), Laravel Http, and `phpseclib3`.

---

### Added

#### 1. Core MTProto 2.0 Binary Protocol & Cryptography
- Full MTProto 2.0 binary packet encoder/decoder (`PacketCodec`) with AES-256-IGE encryption and SHA-256 message keys.
- Real MTProto 2.0 wire path: TCP framing (`FrameCodec`), auth-key DH handshake (`AuthKeyFactory`), encrypted RPC (`EncryptedConnection`) with gzip_packed + bad_server_salt handling.
- Diffie-Hellman Key Exchange (`DiffieHellman`) with Pollard's Rho GCD composite prime factorization (`PqFactorizer`, verified against official doc vectors); live mode is opt-in on `Client` (`->live()`).
- 2FA Cloud Password (`PasswordCalculator`) using SRP-6a with 100,000 PBKDF2 iterations and modular exponentiation.
- Telegram Passport KYC Decryptor (`PassportDecryptor`) with AES-256-CBC and RSA private key decryption.
- Telegram Mini App (TMA) HMAC signature validation middleware (`VerifyMiniAppInitData`).
- Large media chunked stream reader (`StorageMedia`) supporting multi-gigabyte uploads (up to 4GB) from Flysystem/S3.

#### 2. Bot & User Capabilities
- **Bot HTTP Client (`BotClient`)**: High-performance HTTP client powered by Laravel's Http factory with retry logic and proxy support.
- **Bot MTProto Scope (`BotAccountScope`)**: Direct bot authentication on Telegram Data Centers via `auth.importBotAuthorization`.
- **User MTProto Scope (`UserAccountScope`)**: Full suite of user methods including message reactions, message pinning, address book contact imports, channel administration, profile editing, and active session management.

#### 3. Type Language (TL) & Input Type Builders
- Binary TL Wire Codec (`TLSerializer`) for `int32`, `int64`, `double`, `int128`, `int256`, length-prefixed `bytes`/`string`, and `Vector<T>`.
- Schema-driven TL engine (`TLRegistry`/`TLEncoder`/`TLDecoder`) with golden id vectors.
- Fluent builders: `InlineKeyboard`, `ReplyKeyboard`, `InputPeer`, `InputUser`, `InputChannel`, `InputContact`, `InputFile`, and `InputMedia`.

#### 4. Decoupled Ingestion & Polling Engine
- `UpdatePollerService` with pluggable `UpdateSinkInterface` to stream updates into Redis Streams, PostgreSQL, or Spatie Laravel-Data DTOs.
- Standard 1-line Webhook macro (`Route::telegramWebhook`) with secret token verification and `TelegramUpdateReceived` event dispatching.
- Gap detection and resync events: `TelegramGapDetected` (kind `slice|too_long|hole` + context) and `TelegramResynced` (adopted `{pts, date, qts, seq}` state), both dispatched with the same Laravel-availability guard as `TelegramUpdateReceived`.
- Interactive terminal development poller (`php artisan teleframe:poll`).

#### 5. Interactive CLI & Programmatic Authentication
- Interactive Artisan Login Wizard (`php artisan teleframe:login`):
  - 📱 Phone number + code verification.
  - 🔒 2FA Cloud Password auto-retry loop.
  - 📷 ANSI terminal QR Code Scan (`TerminalQr`).
  - 🤖 Bot MTProto Token authorization.
  - 💾 Automatic `.env` session persistence.
- `teleframe:doctor` live verification command (no Telegram account needed).
- Standalone `TeleframeAuthService` for programmatic login flows in web controllers, Livewire components, or background jobs.

#### 6. Developer Infrastructure & CI
- ~~GitHub Actions CI matrix testing PHP 8.2, 8.3, and 8.4.~~ **Retracted.**
  There is no GitHub Actions workflow in this package: `.github/` holds only
  `ISSUE_TEMPLATE/`, `pull_request_template.md` and `SUPPORT.md`, and
  `/.github` is on `composer.json`'s `archive.exclude`. CI is GitLab
  (`.gitlab-ci.yml`, PHP 8.3/8.4). A "GitHub Actions matrix" in the v1.0.0
  notes was never a shipped artifact. Note also that PHP 8.2 is now below the
  package floor — `require` is `^8.3`, and the matrix that does exist tests
  8.3 and 8.4 only.
- GitHub Issue & Pull Request templates.
- Open Source `CONTRIBUTING.md`, `SECURITY.md`, and `LICENSE` (MIT). All three
  files are present.

### Changed
- `ext-zlib` now required; inert proxy context removed from `StreamSocket` (direct connections only until tunneling ships).
- Abridged TCP transport (`0xef` + varint length/4) is the default wire framing — production DCs silently drop intermediate framing.
- Handshake uses `p_q_inner_data_dc` (#a9f55f95) with RSA-PAD encryption (raw RSA over the temp_key/aes construction), required by current servers.
- Live-verified against production Telegram DC2 (both 149.154.167.50 and .51): full DH handshake + encrypted `help.getNearestDc` in ~1.2s (`php examples/live-doctor.php`), cross-checked against MadelineProto as ground truth. Offline verification additionally uses official transcript byte vectors.
- **BC break (pre-release)**: `UpdateSinkInterface::handle()` now returns `bool` (true = processed, false = skip/not-now backpressure) instead of `void`; `EventDispatcherSink` returns true after dispatching. Custom sinks written against earlier pre-release builds must be updated.
- `TelegramUpdateReceived` is enriched with `?int $accountId` and `string $source` (`'mtproto-user'|'bot-http'`); `EventDispatcherSink` derives both automatically from the sink source string (numeric account id → mtproto-user, bot token → bot-http) or takes them explicitly via constructor.
- `pollUser()` now implements the full `updates.getDifference` state machine: `differenceSlice` adopts `intermediate_state` and keeps fetching without ever re-requesting the same window (fixes an infinite-refetch loop), `differenceTooLong` hard-resets pts to the server's, `new_encrypted_messages` (qts items) are wrapped as `updateNewEncryptedMessage` and streamed to the sink instead of being dropped, and sequence state is exposed via `getSequenceState()`/`setSequenceState()` plus a per-channel pts map via `getChannelPts()`.
- Presentation pass for the 1.0 release: Packagist metadata (description, keywords, support links), ~~`bin/teleframe` exposed via `vendor/bin`~~ **retracted: `composer.json` declares no `bin` key**, so nothing in `bin/` is exposed to a consuming app — every `bin/` path in this changelog and in the docs is a repo-working-copy path, and `archive.exclude` drops `/bin` from the dist; `SUPPORT.md`, and this changelog finalized in Keep-a-Changelog format.

### Removed
- Deprecated BC alias `MeRezaRezaei\Teleframe\Core\Exceptions\FloodWaitException` — use `MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException` (pre-release internal BC break; the alias never shipped in a tagged release).
- Dead config keys `redis_connection`, `update_stream`, `command_queue_prefix`, and `bot_username` from `config/teleframe.php` (referenced nowhere in the codebase).
