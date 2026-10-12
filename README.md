<div align="center">

# Teleframe ⚡

**Telegram power with almost zero friction — for PHP & Laravel.**

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.3-8892BF.svg?style=flat-square)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20.svg?style=flat-square)](https://laravel.com/)

</div>

One package for the whole Telegram surface: native MTProto 2.0 for users *and*
bots, the Bot HTTP API, Mini App auth, Passport KYC, and — merged in as
modules — the consumer layer (tenant-scoped Ingest, Redis stream Bus + daemon +
backfill, encrypted backup vault) plus a one-class handler facade.

```bash
composer require merezarezaei/teleframe
```

**Platform floor: PHP `^8.3` and Laravel `^13.0`** (from `composer.json` —
that file is the authority, not this README). You also need the extensions
`bcmath`, `dom`, `fileinfo`, `gmp`, `hash`, `json`, `libxml`, `mbstring`,
`openssl`, `pdo`, `sodium`, `zlib`. `temporal/sdk ^2.19` is in **`require`**,
so a production install has the Temporal SDK — you do **not** run
`composer require temporal/sdk` yourself. `ext-amqp` is declared in
**`suggest`**, not `require`: it is the hard requirement of the two RabbitMQ
drivers (`teleframe.tstore.queue=amqp` and `teleframe.notify.queue=amqp`),
and without it those two drivers refuse to arm. Package discovery registers
five providers and seven facades automatically.

**Installing into a host app?** Read
[docs/usage/integration.md](docs/usage/integration.md) first — it covers
the publish tags, the `teleframe:*` command surface, a per-facade quick
start, and the first-run failures.

## One class composes everything

```php
use MeRezaRezaei\Teleframe\Teleframe;

$teleframe = app(Teleframe::class);

$teleframe->onMessage(function ($update) use ($teleframe) {
    // every stored update reaches you through one pipeline
    $teleframe->send($update->accountId, ['random_id' => random_int(1, PHP_INT_MAX), 'text' => 'got it']);
});
```

- `Teleframe` — compose-not-own facade: `ingest`, `ingestResponse`,
  `onMessage` / `on` (route by constructor, zero-regex), `run` (fixtures
  through the REAL pipeline), `user`/`chat`/`channel`, `route`, `backup`,
  `schemaLayer`, `send` (writes the Q2d echo-elimination registry).
- `Teleclient` — the ingest-only public face, retained byte-stable.

## What's inside

| Area | Namespace / docs | What it is |
|---|---|---|
| MTProto engine | `MeRezaRezaei\Teleframe\Core\` | Raw binary wire: transport, crypto (DH + 2FA SRP), TL codec (zero-regex), method registry, typed RPC exceptions, Layer 227 wire |
| Bot HTTP API | `MeRezaRezaei\Teleframe\Bot\` | `BotClient`, scopes, fluent builders, webhooks & polling |
| Schema pipeline | `MeRezaRezaei\Teleframe\Schema\` + `generated/` | `.tl` → models/`Methods` builders, `teleframe:schema-update` (diff + regenerate + stamp, no migrate), Layer 229 catalog |
| Laravel glue | `MeRezaRezaei\Teleframe\Laravel\` | `TeleframeServiceProvider`, `teleframe:*` commands, webhook route macro, Mini App middleware, facades |
| Consumer modules | `MeRezaRezaei\Teleframe\{Ingest,Bus,Daemon,Backfill,Backup}\` | [ingest.md](docs/ingest.md) · [bus.md](docs/bus.md) · [backup.md](docs/backup.md) |
| Handlers | `MeRezaRezaei\Teleframe\Handler\` | [handlers.md](docs/handlers.md) — registry + 26-line onion + echo elimination + `Testing\FakeDispatcher` |

Docs: [quickstart.md](docs/quickstart.md) · [index.md](docs/index.md) ·
`php artisan teleframe:doctor`.

## Documentation

Usage guides — every class, facade and command verified against the shipped
source:

- [docs/usage/README.md](docs/usage/README.md) — index of the five release
  areas (NOTIFY · STORE + BACKUP · RAW MTProto · APP FRAMEWORK · PASSPORT)
- [docs/usage/notify.md](docs/usage/notify.md) — client acquisition
  (`user`/`fromSession`/`bot`/`botMtproto`), sending, keyboards, notify core
- [docs/usage/store-backup.md](docs/usage/store-backup.md) — TStore, backup
  vault, credential vault, DB store-backup
- [docs/usage/mtproto.md](docs/usage/mtproto.md) — the raw MTProto wire
- [docs/usage/app-framework.md](docs/usage/app-framework.md) — handler
  substrate + compose facade
- [docs/usage/passport.md](docs/usage/passport.md) — Telegram Passport KYC
  and login

Schema reference:

- [docs/schema/mirror-relation-graph.md](docs/schema/mirror-relation-graph.md) —
  the mirror's relation graph, **generated** by
  [`bin/generate-relation-graph.php`](bin/generate-relation-graph.php) from the
  executed migrations (per table: parent, join columns, cardinality, FK-or-not,
  polymorphic-or-soft, plus mermaid `erDiagram`s). Measured: 266 `tf_*` tables,
  53 carrying a FOREIGN KEY, **213 carrying none** — so the mirror does not in
  fact guarantee orphan-free facts, and this spec-adjacent document says which
  relations are enforced in the database and which only in application code.

## What version one does not do yet

Read this before you plan around a feature. Every claim below was checked
against the shipped source; the evidence is named so you can re-check it.

### 1. There is no browser end-to-end test, and one cannot be added without a human

`tests/` contains no browser, Dusk, Playwright, Cypress or Selenium suite,
and `composer.json` requires no such driver. `bin/test-e2e.php` — which
`./bin/teleframe test-e2e` runs — is **not** a browser test: it is a live
MTProto + Bot API verification script that needs real credentials in
`TELEGRAM_USER_SESSION` / `TELEGRAM_API_ID` / `TELEGRAM_API_HASH` /
`TELEGRAM_BOT_TOKEN` and a real Telegram account.

The one surface that *would* need a browser is the embedded web client. It
cannot be validated here: it is a third-party Telegram Web client that you
must sign into interactively, and **you cannot validate it without me —
it needs interaction.** No amount of headless automation substitutes for
the human who is already signed in and watching the screen.

Practically: `tgweb/` is **gitignored and not shipped** (`git ls-files
tgweb` is empty). `Route::telegramWeb()` 404s until *you* run
`bin/build-tgweb.sh` (which clones and builds the client, baking in your own
`api_id`/`api_hash`) **and** set `teleframe.telegram_web.enabled` to
`true`. Once it is up, the route is **deny-by-default**: it ships
`['tg.auth', 'tg.readonly']`, and an unauthenticated request gets a **401**
before the controller runs (failing *closed* if the auth resolver itself
throws). Treat the embedded web client and the relay as *unvalidated*
until you have driven them yourself.

### 2. CI does not run; the full suite runs bounded-locally

`.gitlab-ci.yml` **is committed and complete** — four stages (`guard`,
`verify`, `regen`, `pg`), a PHP 8.3/8.4 matrix, and a `postgres:17`
service. It is not missing or half-written.

What is missing is a runner that can execute it, so the pipeline does not
currently produce a green signal. The operating posture is the
**bounded-local-run branch**: the gates are run on the machine, and CI
evidence is only ever claimed for a specific pipeline that actually went
green — never inferred from a local pass. Expect to run these yourself
after `composer install`:

```bash
composer verify                              # credential guard + phpunit + phpstan + smoke
php bin/regenerate && git status --porcelain # regeneration idempotence
TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg   # Postgres truth track
```

Treat "CI is green" as a claim you must be able to point at a pipeline for,
not a default assumption.

### 3. The curated MTProto method set is a ceiling — on the *builder* surface only

Be precise about which surface is limited:

- **Full catalogue available for lookup.** `MethodRegistry::has($name)` /
  `get($name)` resolve against the packaged artifacts, which today list
  **823 MTProto** and **185 Bot API** methods. Reachability is derivable
  from the registry today.
- **A dynamic call path IS shipped.** Both `UserAccountScope::call(string
  $method, array $params = [])` and `BotClient::call(string $method, array
  $params = [])` execute any method by name, today. You are not blocked at
  20 methods.
- **What is a ceiling is the typed fluent builder surface.**
  `Methods::messages()->sendMessage()->…` only covers what is listed in
  `src/Schema/config/curated-methods.json` — 20 MTProto names and 10
  Bot API names. `Methods::__callStatic` forwards any group that already
  has a generated class and otherwise throws `BadMethodCallException`
  telling you to extend the curated list and re-run
  `bin/generate-method-builders.php`. There is no shipped dynamic
  *builder* generator that turns the other ~800 catalogue entries into
  fluent chains on demand.

So: raw calls are open, hand-written builder chains are not. Prefer
`->call('namespace.method', $params)` for anything outside the curated set.

### 4. The Passport routes ship with the package

The package ships the Passport **service**, the `Passport` facade and
`php artisan teleframe:passport` (`status`/`nonce`/`flush`/`import`) — and
the HTTP half too. `PassportController` lives under
`src/Laravel/Http/Controllers/Dashboard/`, and
`DashboardRoutes::register()` registers its routes: the authenticated pair
`teleframe.passport.show` / `.start` / `.callback`, plus the opt-in anonymous
login pair `teleframe.passport.login.start` / `.login.callback`. Call
`DashboardRoutes::register()` (the same call the dashboard uses) and you are
done — do not hand-roll a parallel pair, which skips the anonymous-login rate
limit and the nonce's session binding. The route table, the middleware each
one carries, and the nonce/session binding are documented in
[telegram-passport.md](docs/telegram-passport.md).

Two configuration facts make or break this in production: the module is
**inert by default** (`teleframe.passport.enabled` is `false`, and
`login()` throws until you flip it), and `teleframe.passport.nonce_store`
defaults to `''`, which binds an **in-process** nonce registry. Set it to a
PSR-16 cache key so a nonce survives from the web request that issued it to
the separate webhook request that consumes it.

### 5. Other limits worth knowing before you start

- **The embedded web client bundle is not in the package** (see §1), and
  `composer.json`'s `archive.exclude` list drops `docs/`, `tests/`,
  `examples/`, `schema/`, `bootstrap/` and `storage/` — a dist install gets
  the `src/` package, not the repo working copy. `bin/` is **not** dropped
  wholesale any more: the dev scripts are excluded individually, but
  **`bin/teleframe-worker` ships**, because it is the only way to serve a
  Temporal tick. A dist install therefore has
  `vendor/merezarezaei/teleframe/bin/teleframe-worker`; you do not copy it in
  from a repo checkout.
- **The wire speaks Layer 227; the method catalogue is Layer 229.** That
  gap is intentional. Do not "fix" it.
- **Published config for four of the five modules now lands under the right
  config key.** `config/teleframe/notify.php`, `config/teleframe/app.php`,
  `config/teleframe/store/backup.php` and `config/teleframe/passport.php`
  mirror the `teleframe.notify` / `teleframe.app` /
  `teleframe.store.backup` / `teleframe.passport` keys their providers
  merge under, so editing a published file takes effect. Earlier revisions
  published flat `teleframe-notify.php`-style names, which Laravel loaded
  under the wrong key and which were therefore inert — if you published
  under those names, move your edits across. Worked through in
  [docs/usage/integration.md §2](docs/usage/integration.md).
- **Rate-limit enforcement for Notify is a later wave.** The capability
  table and its premium multipliers exist, but the `send_rate` /
  `per_chat_rate` / `topic_budget` rows are not enforced.
- **Scheduling is still opt-in, but it is no longer a dead end.** All four
  schedule seams (`notify`, `tstore`, `app`, `store.backup`) default to
  `'null'` and arm nothing until you set the key *and* run the explicit
  `--install`; that refusal to arm implicitly is deliberate. Under
  `temporal` the SDK-backed client is used automatically — `temporal/sdk` is
  in `require` — and it **refuses by name at resolve time** rather than
  degrading to a null publisher that reports `skipped` while you believe the
  job recurs. A schedule that is armed but has no worker polling its queue
  is the silent failure this package cannot see for you: the worker names
  its own task queue and its source on startup, and you must compare that
  with the queue your arm command reported. `TELEFRAME_TEMPORAL_TASK_QUEUE`
  is the one knob that moves both sides. See
  [docs/usage/integration.md §6.6](docs/usage/integration.md).
- **The engine is silent by default** (`teleframe.logging.logger` defaults
  to `''` ⇒ `NullLogger`). If you want to see anything, bind a
  `Psr\Log\LoggerInterface`.

---

## Influence & credit

Teleframe draws heavily on the Nutgram-style handler ergonomics and follows the
owner's unification design (see `docs/superpowers/specs/`). The original
`teleclient` package that held the consumer layer is archived.

MIT licensed.