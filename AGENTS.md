# AGENTS.md — working in the Teleframe repo

Guidance for coding agents and contributors editing this repository. Facts below
describe `src/` as shipped; when docs and code disagree, code wins.

CI runs on GitHub Actions. GitLab is used only to separate the private
repository from this public one; it is not a gate.

## Where the upstream is, and how CI is followed — READ THIS BEFORE PUSHING

**`main`'s upstream is the self-hosted GitLab, not GitHub.** Push to `origin`
and you have pushed to `gitlab.pubg-sell.ir/MeRezaRezaei/teleframe`, which is
the PRIVATE, full-history repository. Nothing an agent does stops there.

GitLab's `publish-public` job then does the rest of its job by itself:

```
  agent commits + pushes  ──►  GitLab (private, full history)   ← origin, upstream
                                     │
                                     │  publish-public: exports a SANITISED tree
                                     │  (bin/public-manifest.txt allowlist),
                                     │  re-gates it, then pushes it
                                     ▼
                            GitHub  github.com/MeRezaRezaei/teleframe   (PUBLIC)
                                     │
                                     │  GitHub Actions runs here
                                     ▼
                              verify-php.yml and the rest
```

So the full-suite CI lives on **GitHub**, and GitLab is the place the work is
authored. Three consequences an agent must internalise:

- **Do NOT push to GitHub yourself.** The `public` remote's push URL is
  `DISABLED://no-direct-push-to-public`. That is deliberate and load-bearing:
  `.brain/` is tracked, and the mirror — not you — decides what reaches a public
  remote. Leave it disabled.
- **To follow CI, wait for the mirror, then use `gh`.** After the push to
  GitLab, `publish-public` runs (default-branch pushes only) and pushes the
  public tree. *After that job has succeeded*, GitHub Actions has a branch to
  run against and `gh` becomes the way to watch it:

  ```bash
  GITLAB_HOST=gitlab.pubg-sell.ir glab ci status              # is the mirror done?
  gh run list --repo MeRezaRezaei/teleframe --limit 5        # then GitHub CI
  gh run watch <run-id> --repo MeRezaRezaei/teleframe
  ```

  `gh` pointed at GitHub **before** the mirror finishes shows a stale branch and
  a red build that says nothing about your change. Check the mirror first.
- **`storage` (`github.com/MeRezaRezaei/teleframe-private`) is the private
  copy.** It is a separate backup of the full private tree, and it is not
  pushed by the pipeline — it is pushed explicitly. It is not CI and not a gate.

**A mirror that failed is not a green build.** `publish-public` reports failure
with a named reason when it cannot publish — most often a missing
`TF_PUBLIC_REPO_URL` or `TF_PUBLIC_PUSH_TOKEN` CI/CD variable. When the mirror
fails, GitHub never receives the commit, so GitHub CI never runs, and **the
absence of a GitHub run is not a pass.** Report the mirror as the gate it is.

## Single-package layout

`merezarezaei/teleframe` is ONE package: the engine and the merged consumer
layer (the former `teleclient` project is archived; its modules now live here
under `MeRezaRezaei\Teleframe\*`).

| Layer | Location | What it is |
| --- | --- | --- |
| MTProto wire | `src/Core/MTProto/` | Raw binary: `Client`, `Connection/EncryptedConnection` (handshake, blocking `call()` + `callBatch()`, `invokeWithLayer` wrap), `Crypto/` (DH, 2FA SRP), `TL/` (zero-regex registry + codec), `Transport/`, `SessionData.php` (a single class file, not a directory). Wire speaks Layer 227 (`EncryptedConnection::LAYER`). |
| Schema + Methods | `src/Core/Schema/`, `src/Core/Methods/` (+ `Generated/`), `src/Bot/Methods/Generated/` | `MethodRegistry` loads `src/Schema/schema/methods-mtproto.json` + `src/Schema/schema/methods-botapi.json`; `SchemaDiffer` audits; `Methods::__callStatic` resolves fluent groups. Schema catalog = Layer 229 (`composer extra.telegram-layer`). The 227-wire vs 229-schema gap is intentional — never "fix" it. |
| Services | `src/Laravel/Services/`, `src/Bot/Services/`, `src/Core/Services/` | `TeleframeClient` (entry: `user`/`fromSession`/`bot`/`botMtproto`), scopes, `TeleframeAuthService` (phone/QR/bot login), update polling. |
| Exceptions | `src/Core/Exceptions/` | `TelegramException` base, `DcMigrationException`, `Rpc/` typed catalog + `RpcExceptionResolver`. |
| Consumer modules | `src/Teleframe/{AccountLifecycle,Ingest,Bus,Daemon,Backfill,Backup,Control}/` | Tenant-scoped Ingest (Postgres truth, `UpdateStored` event), Redis stream Bus (`tg:stream:updates`, consumer group `teleclient`, route table `tg:bus:routes`, DL `tg:stream:dead-letter`), multi-account `Daemon`, quota-aware `BackfillWorker`, encrypted `Backup` vault, `AccountLifecycle` (TfAccount's Eloquent observer → `AccountAdded`/`AccountRemoved`/`BotAdded` lifecycle events + best-effort reload signal on the bus reload channel for daemons). `Control` = relay seam ("sit in the middle"): `RelayMessage`/`RelayFilter`/`RelayPipeline` onion + lazy `AccountRelay` (built by `RelayFactory`/`RelaySessionResolver` in `src/Laravel/Services/`; vault scope, never dials at construction), `AccountUpdateStream` (bus → INBOUND relay message), PSR-16 `RelaySessionRegistry`, transport terminal `RelayTerminal` (OUTBOUND → bound client `call()`, reply → INBOUND) wired by `RelayFactory::terminalFor`, client-in frame-dial seam `RelayFrameDialBridge` (managed vault `SessionData` → lazily-dialed `EncryptedConnection`, `withFramePump()`, `swapScopeResolver()` = multi-account switch; `lazy()`/`RelayFactory::bridgeFor()` derive the connection from a live scope's `getSession()` on first use — both plain-PHP-constructible), obfuscated2 transport terminal `RelayServer`/`Obfuscated2Codec` in `src/Core/MTProto/Transport/`, presence API + feed + switch (`GET/POST accounts/{id}/presence`, `POST accounts/{id}/switch` → `RelayPresenceController`/`RelaySwitchController` in `src/Laravel/Http/Controllers/Dashboard/`, routes in `DashboardRoutes.php:71-77`; feed via `PresenceFeed` in `src/Teleframe/Realtime/` → `account:{id}:updates` through `CentrifugoBridge`, `Control\RelayAccountsCatalog` switch menu; `Laravel\Services\RelayActiveAccount` persists the ACTIVE context in PSR-16, never a session string), `tgweb-patches/` client getDC hook. |
| More Teleframe | `src/Teleframe/{BotMap,Identity,Message,Mirror,Realtime,Repositories,Stage,Store,Testing,Vault}/` | `BotMap` (bot entry/transport/token vault), `Identity` (bindings, `TgUser`/`TlUserBinding`), `Message` (`MessageFactory`/`MessageCompiler`/`EntityParser`), `Mirror` (mirror models), `Realtime` (`PresenceFeed` + `CentrifugoBridge`/`ChannelNames`/`HttpCentrifugoBridge`), `Repositories` (`MessagesUnion`), `Stage` (stage-state registry + middleware), `Store` (S3/media store service), `Testing` (plain-PHP `FakeDispatcher`), `Vault` (credential vault models + `Vault` service). |
| Handlers | `src/Teleframe/Handler/` (+ `Middleware/`, `Subscriptions/`) | Handler-as-data `HandlerRegistry` (`on`/`onMessage`), zero-regex `HandlerMatcher` (exact / `prefix*` / `*`, first-match + priority), `Update` uprate (`fromBus`/`fromMirror`), 26-line `Pipeline` onion, `UpdateDispatcher` (DI-flavored invoke, lazy model hydration), `EchoEliminator` (Q2d send-time PSR-16 registry, `onOwn` escape hatch), `HandlerSink`/`UpdateStoredHandler` (both intake rows → one pipeline), faked-transport dispatcher in `Teleframe/Testing/` (`FakeDispatcher`). |
| Console | `src/Laravel/Console/` | `teleframe:login`, `teleframe:poll`, `teleframe:doctor`, `teleframe:schema-audit`, `teleframe:schema-update`, `teleframe:regenerate`, `teleframe:ingest`, `teleframe:ingest:gate`, `teleframe:backfill`, `teleframe:backup`, `teleframe:relay`, `teleframe:daemon`, `teleframe:tstore`, `teleframe:vault-add-account`, `teleframe:vault-add-app`, `teleframe:vault-list`, `teleframe:vault-use-default` (17 commands; one class per file under `src/Laravel/Console/`). |
| Faces | `src/Teleframe/Teleframe.php`, `src/Teleframe/Teleclient.php` | `Teleframe` = one-class compose-not-own facade (also `docs/handlers.md`); `Teleclient` = ingest-only face, byte-stable. |
| Laravel glue | `src/Laravel/` | `TeleframeServiceProvider` (delegate-container wiring only), `Route::telegramWebhook` macro, `tg.miniapp` middleware, `TF`/`Teleframe` facades, config key `teleframe` (env `TELEFRAME_*`). |
| More Laravel | `src/Laravel/{Events,Http,Media,Models,Realtime,Store,Stubs}/` + `src/Laravel/{config,resources}/` | `Events` (`TelegramUpdateReceived`/`TelegramResynced`/`TelegramGapDetected`), `Http` (`Controllers/Dashboard/` incl. `DashboardRoutes.php` + relay/presence controllers; `Middleware/` `tg.miniapp`), `Media` (`StorageMedia`), `Models` (routing rules), `Realtime` (bus listeners, e.g. `UpdateStoredCentrifugoListener` — distinct from `Teleframe\Realtime`), `Store` (S3 gateway + hot cache), `Stubs` (`miniapp` stub), `config/` (published `teleframe.php`), `resources/` (views). |

## Generated artifacts — never hand-edit

Every path below carries an `@generated` marker and is overwritten by the schema
pipeline. Editing one by hand makes the regeneration-idempotence gate fail.

`php artisan teleframe:*` commands run in the host Laravel app that has
teleframe installed. `php bin/*` and `vendor/bin/*` commands run in this
repository root.

- `src/Schema/schema/methods-*.json`, `src/Schema/schema/sources/*.tl` — update
  via `php artisan teleframe:schema-update` (diff + regenerate + stamp, does not
  migrate).
- `src/Schema/Generated/**` (models, data, factories) + `src/Laravel/Migrations/`
  (curated ship dial) — `php artisan teleframe:regenerate [--ship]` or
  `php bin/regenerate` from the repository root.
- `src/{Core,Bot}/Methods/Generated/*.php` — regenerate after editing
  `src/Schema/config/curated-methods.json`.
- `src/Schema/skills/telegram-methods/*.md` — generated from the catalog
  (`php bin/generate-skill-files.php`, 30 files, `<!-- @generated -->` stamped).
- `src/Core/Exceptions/Rpc/RpcErrorCatalog.php` — generated from the committed
  `src/Schema/schema/sources/errors.json` by `php bin/generate-rpc-catalog.php`.

## Hard rules

- Zero regex in `src/` outside the allow-list: `preg_*()` is banned and phpstan
  enforces it via `disallowedFunctionCalls`; it is allowed only in `src/Laravel/*`,
  `src/Schema/*`, `src/Bot/*`. **`src/Teleframe/Handler/**` and `src/Core/**` are
  strictly zero-regex** — parse with `sscanf`, string functions, or the TL
  tokenizer, never `preg_*()`.
- Session strings are credentials: `.env` (which holds session values) must never
  be committed; tests never require real credentials; live gates are opt-in.
  Enforced by `php bin/scan-credentials.php`, which scans the working tree
  tracked and untracked, binary files (to a prefix), fragmented runs, and git
  history from every ref. Its summary line states on every run what it did not
  reach. Exit 0 = clean, 1 = findings, 2 = usage, 3 = scan incomplete. Never add
  an entry to silence a new finding.
- Redis wire keys are opaque state: do not rename `tg:stream:updates`, consumer
  group `teleclient`, `tg:bus:routes`, the dead-letter stream, or the reload
  channel. They are forward-compatible with the archived teleclient project and
  renaming them breaks live streams.
- Public bind keys stay fixed: `teleclient.backfill.scope-resolver`,
  `teleclient.backfill.ingester`, `teleclient.backup.vault-factory`. They are
  part of the public API — renaming them is a breaking change.

## Gates

### Never run the full suite locally — that is CI's job, and it blocks development

**This machine cannot run the full automated test suite, and attempting it is a
defect, not diligence.** GitHub Actions owns the full suite. A local full-suite
run stalls for 15+ minutes and occupies the machine, which blocks every other
lane. Never run `composer verify`, never run `vendor/bin/phpunit` with no path
argument, and never run a whole heavy directory as a verification step.

**Know that "CI will catch it" is only true once the mirror has run.** GitHub
Actions sees a commit only after GitLab's `publish-public` job pushes the public
tree, and that job can fail on a missing `TF_PUBLIC_REPO_URL` /
`TF_PUBLIC_PUSH_TOKEN`. When it does, there is no GitHub run at all — not a red
one, none — so an unverified change can sit green-looking for as long as the
mirror stays broken. `glab ci status` is therefore part of verifying a push, and
a failed mirror is a blocker to report, not a detail to skip.

Verify **your change**, not the app:

```bash
vendor/bin/phpunit tests/<TheOneAreaYouChanged>   # targeted only
vendor/bin/phpunit path/to/TheOneTestFile.php     # narrowest form, preferred
vendor/bin/phpstan analyse --level=5 --no-progress src/<TheOnePackageYouChanged>
php bin/standalone-smoke.php                       # fast, safe, whole-tree
php bin/scan-credentials.php                       # fast, safe, whole-tree
```

Rules that follow from this:

- A whole-directory run is only acceptable for a directory you know is fast
  (for example `tests/Notify`, `tests/App`, `tests/Core`). `tests/Ingest`,
  `tests/MySql`, `tests/Pg` and `tests/Schema` are NOT fast — run the single
  test files you touched.
- Every command carries `timeout -k 5 <secs> <cmd>`. Size the number to the
  command's real expected duration. Exit 124 is a named failure to report.
- Do not add a test whose cost is paid by every future suite run. Booting the
  full migration dial once per test method costs ~48s and was a rejected change
  (see `docs/decisions.md` D-035).
- If your change genuinely cannot be verified without the full suite, say so
  and stop — do not fall back to running it.

These commands are the gates. Run them from the repository root after
`composer install`. GitHub Actions runs the credential guard, phpstan, the
standalone smoke and the sharded PHPUnit suite on PHP 8.3 and 8.4, and runs
`vendor/bin/phpunit tests/Pg` and `vendor/bin/phpunit tests/MySql` on PHP 8.4
with `postgres:17` and `mysql:8.4` service containers. `composer verify` is the
local shorthand for credential guard + suite + phpstan + smoke; CI invokes
those parts as separate steps. The regeneration idempotence gate below is not
currently invoked by any workflow — run it locally.

```bash
composer verify                                 # credential guard + phpunit + phpstan (level 5, src only) + standalone smoke
php bin/scan-credentials.php                    # exit 0 = no session string in tree or history, no committed .env
php bin/standalone-smoke.php                    # exit 0 = every module plain-PHP-constructible
vendor/bin/phpunit tests/Pg                     # Postgres truth track (needs TELEFRAME_PG=1 and a reachable postgres:17)
vendor/bin/phpunit tests/MySql                  # MySQL track (needs TELEFRAME_MYSQL=1 and a reachable mysql:8.4)
php bin/regenerate --out=/tmp/regen-a && php bin/regenerate --out=/tmp/regen-b && diff -r /tmp/regen-a /tmp/regen-b   # regeneration idempotence
```

The regeneration gate's committed-tree form is `diff -r /tmp/regen-a/src/Schema/Generated
src/Schema/Generated`: any non-zero exit means the committed generated tree
drifted, and the fix is to run `php bin/regenerate` and commit the result.

Live gates that need real credentials are opt-in and never part of CI.

Notes: LSP "Undefined type" diagnostics are stale-autoload noise — the final
authority is `composer verify`. `composer.lock` is gitignored. `tests/` mirrors
`src/` by coverage area, one test class per file, not a literal directory tree —
for example the Ingest and Bus suites sit flat under `tests/`, while only
Control, Daemon and Realtime nest under `tests/Teleframe/`.

## Known gaps / limitations

- The logging seam is PSR-3 and silent by default. The engine logs through
  `Psr\Log\LoggerInterface`, resolved by the provider: host binding wins, then the
  `teleframe.logging.logger` FQCN, then `NullLogger`. `UpdateDispatcher` logs
  handler failures; `TeleframeClient` logs RPC and batch failures at `error` level
  and always rethrows — logging never swallows. Unconfigured, the engine is
  byte-for-byte silent. The deep MTProto transport internals
  (`Client`, `PlainConnection`, `EncryptedConnection`, `StreamSocket`,
  `FrameCodec`, `AuthKeyFactory`) each accept an optional logger; log points are
  metadata only, and session keys, payloads and auth keys are never logged.
- `StreamSocket` supports SOCKS5 and HTTP CONNECT proxy hops
  (`connectViaProxy`, `httpTunnel`, `socks5Tunnel`), with TLS applied to the
  tunneled stream rather than the proxy hop. A proxy entry missing host or port
  falls back to a direct connection.
- PSR-7/17 are not used, by design: `illuminate/http` is the HTTP seam inside
  Laravel and MTProto is raw TCP. PSR-17 becomes relevant only if raw HTTP
  handling is added outside Laravel.
- The schema artifacts catalogue Layer 229 while the wire speaks Layer 227. This
  gap is intentional — never "fix" it (see the Schema + Methods row above).

## Specs and plans

Design specs, implementation plans, the roadmap, and the operator's verbatim
archive are kept in the operator's private repository and are deliberately not
published here. `docs/` therefore contains only user-facing module
documentation: `quickstart.md`, `usage/`, `reference/`, `schema/`, `skills/`,
and the per-module pages named in `llms.txt`. Treat "Hard rules" and "Generated
artifacts" above as the shipped contract for the wire layer, the schema
pipeline and the handler substrate — no design record is reachable from this
repository.
