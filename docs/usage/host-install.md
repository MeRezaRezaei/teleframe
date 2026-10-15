# Installing Teleframe into a host application

> **This file is a deliverable, not a note.** The owner's definition of done
> for Version One is that this package integrates into *another project*
> ("since i need it to get integrated into other project i need you to
> organize task to reach that state" —
> [verbatim 2026-09-25](/.brain/verbatim/2026-09-25-notes.md)). The proof used to
> live in a throwaway directory that was deleted. It now lives in
> `bin/prove-host-install.php`, a gate, and this page is the friction list that
> gate produces. **Friction nobody wrote down is friction the next integrator
> rediscovers.**

The list below is not hand-maintained prose. It is the numbered list
`php bin/prove-host-install.php` prints at the end of every run, plus the
findings that run surfaced. If you change a provider, a publish tag or a
facade's contract, run the gate and update this file in the same commit.

---

## The proof, in one command

```bash
php bin/prove-host-install.php
```

It builds a **throwaway** `laravel/laravel` host in a temp dir, installs this
package into it as a **path repository** (so it exercises the live working
tree, not a stale copy), and asserts the onboarding path end to end. It is
plain PHP, adds no Composer dependency, prints no credential of any kind, does
not modify this repository (it asserts that itself, by comparing
`git status --porcelain` byte-for-byte before and after), and deletes its temp
host on every exit path including a crash.

The host is deliberately **unconfigured** — no API id, no api hash, no bot
token, no session — because that is the state the facade contract is written
about. The gate asserts the host really is unconfigured, so a green run cannot
be a green run by accident.

### Exit codes are distinct on purpose

| Code | Meaning |
| ---: | --- |
| `0` | Clean tree: the package installs and boots in a fresh host. |
| `1` | A real regression — one of the assertions below failed. |
| `2` | Usage/precondition: not run from the package root, or no `composer`. |
| `3` | **The host build could not reach the network.** Nothing was proven. This is deliberately *not* a pass and *not* a confusing composer stack trace. |
| `4` | The run **modified this repository**. That is a bug in the script, not in the package. |

### What it asserts

- all six providers are auto-discovered by the host's real `PackageManifest`;
- every declared provider class actually exists in `src/`;
- all seven facade aliases are declared, and the host alias loader registers
  them (seven aliases, **six** facades — `TF` and `Teleframe` are two names for
  one facade, which is why the count of aliases and the count of facades below
  differ);
- **every** `vendor:publish` tag lands its config file, the single umbrella tag
  lands **all five** of them, and the host still boots with that published
  config in place;
- `teleframe:doctor` *terminates* and fails loudly non-interactively — a hang
  is a distinct failure from an error;
- `php artisan list` carries the gate's 22 expected `teleframe:*` commands.
  *(This line used to claim 20, and to name `teleframe:notify`,
  `teleframe:tstore` and `teleframe:routing` as deliberately NOT asserted.
  All three have since been added — dropping a command from a provider's
  `$this->commands([...])` was invisible until it was gated — so the count and
  the exclusion list were both wrong.)*
- all six facades **resolve, are callable, throw on first real transport use,
  and never silently drop** (below).

---

## The six facades and their unconfigured contract

The package's rule is: *resolves and is callable — sends throw on first real
transport use, never silent-drop.* A facade that returns happily having done
nothing is the failure mode that matters, so the gate treats a successful
return where a throw is required as a **failure**.

| Facade | Accessor seam | Resolves to | Safe while unconfigured | First transport call | What it throws |
| --- | --- | --- | --- | --- | --- |
| `Teleframe` / `TF` | `TeleframeClient::class` | `Laravel\Services\TeleframeClient` | `schemaLayer()` → `229` | `user()` / `bot()` | `RuntimeException: Telegram API ID and API Hash are required…` / `Telegram Bot Token is required…` |
| `TStore` | `teleclient.tstore.service` | `Store\StoreService` | `buckets()` → `[]` | `put()` | `RuntimeException: tstore channel factory needs teleframe.tstore.account_id…` |
| `Notify` | `teleclient.notify.service` | `Notify\NotifyService` | *(resolution only)* | `log()` | `NotifyException: unknown topic "app.error" — no target chat on the topic row or the send` |
| `Passport` | `teleclient.passport.service` | `Passport\PassportLoginService` | `issueNonce()` | `login()` | `PassportLoginException: passport login not enabled (configure teleframe.passport)` |
| `TelegramApp` | `teleclient.app.service` | `App\AppRuntime` | `list()` → `[]` | `transport()` / `read()` / `answer()` | `AppException: app transport: cannot resolve user scope…` |
| `StoreBackup` | `teleclient.store.backup.service` | `Store\Backup\DatabaseBackupService` | `runs()` → `[]`, `schedule()` | `backup()` | `BackupException: no database driver configured…` |

---

## NUMBERED INTEGRATOR-FRICTION LIST

Every item is a step **the integrator has to perform that the package does not
do for them**, in the order they hit it.

1. **`composer require merezarezaei/teleframe`.** That is the only install
   step. Nothing else is needed for the boot to work: all six providers
   auto-discover and all seven aliases register, with no manual
   `providers.php` / `aliases.php` edit.

2. **`php artisan vendor:publish --tag=teleframe-config` — one umbrella tag
   publishes all five config files.** `--tag=teleframe-config` lands **all
   five** (`config/teleframe.php`, `config/teleframe/notify.php`,
   `config/teleframe/passport.php`, `config/teleframe/app.php`,
   `config/teleframe/store/backup.php`). **This item used to say the opposite**
   — *"five separate tags, no umbrella tag"* — and it was true when written,
   then false: `TeleframeServiceProvider::publishes()` now registers all five
   sources under `teleframe-config`, and `bin/prove-host-install.php` asserts
   the umbrella tag lands every one of them *and* that **no config file is
   unreferenced by any tag** (the tag list is hand-kept and can fall behind the
   tree). The per-file tags still exist and still land their file —
   `--tag=teleframe-notify-config`, `--tag=teleframe-store-backup-config`,
   `--tag=teleframe-app-config`, `--tag=teleframe-passport-config` — so a host
   that wants one file may still name one, but **a host that publishes only
   `teleframe-config` is no longer missing anything.** Publishing is still
   optional (the package merges its own defaults), which is why a host that
   publishes nothing discovers a settings file it never wrote only when it
   needs it.

3. **Set `TELEGRAM_API_ID` / `TELEGRAM_API_HASH` before any MTProto call.**
   Unconfigured, `Teleframe::user()` throws
   `"Telegram API ID and API Hash are required…"` — the facade resolves, the
   call does not. Note the guard fires *before* the session is even looked at,
   so an unconfigured host never sees a session-shaped error.

4. **Set `TELEGRAM_BOT_TOKEN` before Notify sends.** Unconfigured, `Notify`
   resolves and the send throws. As of the fix that shipped with this gate,
   that throw now happens **on the calling thread for every queue driver** —
   see finding **B** below; it used to happen only in a worker.

5. **Run `php artisan teleframe:doctor` as the first post-install check.** It
   is the one command that tells you whether the host can actually reach
   Telegram, and it needs no account. Unconfigured it refuses **fast**
   (non-zero exit, names the missing variables, does not hang) — the fix for a
   non-interactive hang shipped with this gate; the gate now times it and
   fails if it exceeds 30s.

6. **The host must run its own migrations.** The package ships its migrations
   in `src/Laravel/Migrations` and registers them via `loadMigrationsFrom`, so
   `php artisan migrate` in the host is what creates them. Until it runs, any
   database-backed surface (tstore index, backup run ledger, vault) fails with
   a raw database error rather than a friendly one — on a stock host the first
   symptom is a `SQLSTATE … no such table: cache` from a `Notify` call, because
   the notify budget guard reads the cache. **The gate itself runs
   `migrate --force` before probing**, precisely so it measures the supported
   post-install state instead of re-discovering this.

7. **Decide the queue driver for Notify.** `teleframe.notify.queue` defaults to
   `"auto"`, and `auto` now tries **RabbitMQ before the host's own Laravel
   queue**: it picks the AMQP driver when `ext-amqp` is loaded *and* a broker
   host is configured, then falls back to the host queue, then to inline
   `sync`. (It used to prefer the Laravel queue, so configuring a broker
   silently did nothing.) A stock Laravel 13 app is
   `QUEUE_CONNECTION=database`, i.e. async — which is the correct default (a
   notification must not block a web request) but means the *delivery* happens
   in a worker, so a Notify failure surfaces in `failed_jobs`, not at the call
   site. Once you have decided, set it explicitly (`sync` / `laravel` /
   `amqp`) rather than inheriting `auto`, and confirm what resolved with
   `teleframe:notify work`.

   If you do use `amqp`, note the credential guard: `amqp.user` and
   `amqp.password` default to **empty**, which is treated as `guest`/`guest`
   at the dial, and that stock pair is **refused against any non-loopback
   broker** — loudly, by name. Set `TELEFRAME_NOTIFY_AMQP_USER` /
   `TELEFRAME_NOTIFY_AMQP_PASSWORD` to a dedicated broker identity.

8. **Bring up Redis and start a process for updates to arrive at all.** The
   bus (`tg:stream:updates`, consumer group `teleclient`) is Redis-backed and
   nothing polls on its own: you run `teleframe:ingest` (or `teleframe:daemon`)
   in a separate process, under a supervisor. No facade call starts a
   listener, and no facade call is the thing that makes updates arrive — a
   host that only wires handlers receives nothing until a process runs.
   `teleframe:ingest` creates the consumer group itself
   (`XGROUP CREATE … MKSTREAM`), so a fresh Redis needs no manual
   `XGROUP ADD`.

9. **If you turn on `teleframe.*.schedules = temporal`, set
   `TELEFRAME_TEMPORAL_TASK_QUEUE` and run a worker.** `temporal/sdk` is in
   `require`, so the SDK is present after a plain `composer require`; the SDK
   is not the missing piece, a **polling worker** is. All four schedule seams
   (`notify`, `tstore`, `app`, `store.backup`) share one task-queue default
   read from that one env var, and `bin/teleframe-worker` **ships in the dist
   tarball** (you do not copy it in). A worker on a different queue than the
   one the schedules were armed on **blocks forever with no error** — so set
   the variable for both processes and compare the queue the worker prints
   against the queue your arm command reported. See
   [integration.md §6.6](integration.md).

10. **`onMessage`/`observe()` handlers need one opt-in row before they
    fire.** The dispatch default is `store_only`, and the only thing in the
    package that writes the `act_on` row is
    `php artisan teleframe:routing <account>`. Without it your handlers are
    registered, healthy, and never called. See
    [handlers.md §6](../handlers.md).

11. **The database backup schedule: pick ONE trigger, and delete the loser.**
    The package can own the `teleframe:tstore backup` dump itself, but it
    ships **default-off**, and the reason is worth stating before you flip it:
    the two triggers are **invisible to each other**. The posture a host may
    already have is a hand-written crontab line; cron is invisible to the
    Laravel scheduler; and no config key can report it. So the package cannot
    prove that enabling its own trigger will not double-fire. Set
    `TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_ENABLED=1` (time from
    `TELEFRAME_STORE_BACKUP_PACKAGE_SCHEDULE_DAILY_AT`, default `03:47`,
    deliberately off the file-vault sibling's `03:17` so a host running both
    does not stack two dumps on one minute) **and delete your cron line in the
    same change** — the host still needs `php artisan schedule:run` on its own
    cron for the trigger to fire. `withoutOverlapping()` and `onOneServer()`
    protect the package's trigger against a second scheduler or a second app
    server; they do nothing against a crontab, and a double-fired dump is two
    full database dumps a day into the same bucket with two run-ledger rows.
    This key is deliberately **not** gated by
    `teleframe.store.backup.schedule.enabled`, which governs the *watchdog*;
    it has its own gate and nothing else.

---

## Findings this gate surfaced (with the fix that shipped alongside it)

**A. `SupervisorServiceProvider` auto-discovery was unproven.** It was added
after the original manual install run, so nothing had ever booted it in a host.
It is now one of the six providers the gate asserts, in the host's own
`bootstrap/cache/packages.php`. *Proved red:* removing it from
`composer.json` fails **three** independent checks — the manifest check
(`not auto-discovered: SupervisorServiceProvider`), the declared-classes check,
and `php artisan list` (`missing from artisan list: teleframe:supervise`).

**B. `Notify` silently dropped on a stock Laravel 13 host — FIXED HERE.**
With `teleframe.notify.queue=auto` on the default `QUEUE_CONNECTION=database`,
`Notify::log()` returned a job id, queued the job, and threw **nothing**. The
"no target chat" refusal lived in `handle()` — which only the `sync` driver
ever reached — so on any async host the caller's thread saw a successful
string and the notification died later inside a worker. That is precisely the
silent drop the facade's own docblock forbids. Fixed in
`NotifyService::enqueue()` by refusing an undeliverable job at the boundary,
where the target chat is known, for **every** driver. A correctly configured
host is unaffected.

**C. The in-repo twin of this gate was a false proof — SINCE FIXED.**
`tests/Laravel/Support/ZeroConfigBootTest.php` called
`$service->log(Level::Error, …)` with no `use …\Notify\Level;`, so
`Level::Error` resolved to a non-existent class and raised `Error: Class not
found` — which satisfies `expectException(Throwable::class)` without ever
reaching the notify core. It also ran under Testbench's `QUEUE_CONNECTION=sync`,
a state a real host does not have, which was the single difference that hid
finding **B**. Both halves are now repaired in that file: the import is
present, and the test proves the failure came from the transport rather than
from a name that failed to resolve. *(This entry used to end "left in place …
it should not be trusted as evidence" — that is no longer true, and a finding
recorded as open when it is closed is the same defect as a claim that was
never true.)*

**D. Two advertised facade methods did not exist — SINCE FIXED.**
`Notify::service()` and `TStore::service()` were in their docblocks and in the
`Notify` example, but neither `NotifyService` nor `StoreService` implemented
`service()`, and a Laravel `Facade` is not `Macroable` — so both raised
`Error: Call to undefined method …`. `StoreBackup::service()` and
`Passport::service()` were real throughout. The gap is now closed at the
facade itself: `src/Laravel/Facades/Notify.php:54` and
`src/Laravel/Facades/TStore.php:53` each define a real static `service()`
rather than relying on the `@method` annotation, and each throws by name when
the facade root is not the expected service. All four accessors are now real.

**E. A `git status` drift is now a first-class failure (exit 4).** The gate
compares `git status --porcelain` before and after and fails if the bytes
differ, so a script bug that writes into the package tree cannot hide behind a
green run.

---

## What this gate does NOT prove

Stated so nobody reads a green run as more than it is. The script prints this
same footer on every run.

- **No live Telegram connection.** No account, session string, bot token or
  auth key is used anywhere. Reachability is never asserted.
- **No non-sqlite database.** The host is the stock sqlite skeleton, so the
  Postgres and MySQL tracks are unproven here.
- **No queue worker actually running.** Notify is asserted at the call
  boundary, not end to end through a worker.
- **No npm/build assets**, and the dashboard views only insofar as the config
  publish above lands.
- **One host only** — not a host with an existing queue, cache or Redis
  already configured.

---

## Keeping the friction list honest

`tests/Standalone/HostInstallGateTest.php` runs without a network and asserts
that the gate's own expectations still match `composer.json`: every provider
the gate asserts is still declared, **and** every provider declared is asserted
by the gate. Both directions matter — a gate whose hardcoded list can drift
away from the package is worse than no gate, because it gets trusted.

CI runs the real proof in the `host-install` stage (`.gitlab-ci.yml`).
