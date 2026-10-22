# AGENTS.md — working in the Teleframe repo

Guidance for coding agents and contributors editing this repository. Facts below
describe `src/` as shipped; when docs and code disagree, code wins.

CI runs on GitHub Actions. GitLab is used only to separate the private
repository from this public one; it is not a gate.

## TWO STANDING RULES — bind every session, including one that opens with no instruction

### 1. Set the goal that is given to you

When the operator states an objective, set it with the goal tool **before doing any work**, not
after the first task and not only if asked. A session that reads an objective and keeps working
without recording it has no stop condition, so it cannot tell finished from unfinished.

- `goal_set` / `set_goal` with the operator's objective as the text.
- **`maxTurns`, `maxTokens`, `maxDurationMs`, `maxCostUsd` must be set to their largest accepted
  value explicitly.** Blank is not infinite — blank hands the decision to the harness default, and
  the default is a limit.
- The goal is **resumed, not restarted**, after a crash or a provider error:
  `goal_resume`, then `update_goal` to replace the objective text if it changed.
- Update it when the objective genuinely changes. Do not silently drift the work past the goal.

### 2. You must collect every subagent you dispatch — there is no auto-notify

**Nothing tells you a subagent finished.** There is no wake-up, no callback, no inbox. An agent you
dispatched and did not collect is a **silent result**: the work happened, and its output lives
somewhere you never see. There is no notification to wait for, because the mechanism does not
exist.

So the obligation is yours, immediately, at dispatch:

```
delegate_to_agent(...)  ->  returns task_id
get_delegation_status(task_ids=[id], wait_ms=55000)   # re-arm until terminal
```

- **Re-arm, do not poll once.** After each return, if any task is not `completed`/`failed`/`canceled`,
  call `get_delegation_status` again. Do not substitute `sleep`, `tail -f`, or a status-file loop.
- **Poll 1-2 task ids per call.** Six or more times out at the MCP layer, so a wide fan-out is
  collected in small batches.
- **Never end a turn with a dispatched task still `running`.** The words "waiting", "in flight" and
  "re-arming" are not a stopping point — they describe the next tool call you owe.
- **A quiet lane is not a dead lane.** Cancel only on real evidence: a terminal status plus a tree
  with no file on disk. Never cancel a lane merely for being slow or silent.
- **A lane with no file on disk is not evidence of anything**, and a completion report with no
  artifact is not evidence either.

## THE BRAIN LOOP — read before you stop, park, or report "done"

The `brain` skill stack is the operating contract for this repo's agent work. Its rules bind
every session, including one that opens with no instruction. **These are not optional, and none
of them waits on a human.**

### 0. PROBE FIRST — the router's paths are relative to the skill-stack repo, not to here

This repo is an **application repo**, so the router's relative paths (`brain-doctrine/SKILL.md`,
`scripts/brain-query.py`, …) are **dead links here**. Probe before following any of them:

```bash
timeout -k 5 30 bash -c 'test -f brain-doctrine/SKILL.md && echo "LOCAL: stack present" || echo "LOCAL: absent"'
timeout -k 5 30 bash -c 'for d in ~/.config/opencode/skills ~/.agents/skills ~/.claude/skills; do test -d "$d/brain-doctrine" && echo "GLOBAL: $d"; done'
```

⛔ **Measured on this host: the skill roots hold 88 symlinked entries and only 30 real
directories.** `find -maxdepth 1 -type d -name X` matches **none** of them, because `-type d`
excludes symlinks. Measured on the same directory: `find -type d` → **30**, plain `find` → **119**,
shell glob → **117**. ⛔ **Never conclude a skill is absent from a `find` that carried `-type d`.**
Use `test -f "<root>/<name>/SKILL.md"`, which follows the link. An agent that hit this once
reported 13 of 13 skills "unresolved" and diagnosed a broken install **while every skill was
loadable**.

⛔ **A failed lookup means the rule is elsewhere, never that it does not exist.** That is the
router's own negative contract, and it is what fires when every path fails.

⛔ **Printing a required marker is not performing the action.** `nmem-skill-used: brain` on a
commit does not mean the skill ran, and a gate named in a document that you cannot execute is an
unverifiable claim — obeying it while being unable to detect that you cannot is the failure.

### 1. The seven-stage ladder — no stage stops

```
VERBATIM ─▶ MISSION ─▶ GOAL ─▶ PLAN +logs ─▶ EXECUTION ─▶ VERIFY ─▶ back UP
```

`PLAN` carries **four logs** in `.brain/plans/logs/` — summary · changelog · risks ·
considerations — stored **separately**, so a risk buried in a paragraph of achievement is still
findable.

⛔ **An empty stage is a RE-READ of the stage above it, never a park.** A finished task, a passing
gate, one empty round, or a per-mission completion is **none** of them a finished mission. Read an
early stop as a **broken gate, not a finished mission** — diagnose the gate, fix it, continue.

### 2. The never-stop invariant — the only condition that ends this

The goal may be declared achieved **only** when **both** hold:

- **(a)** three consecutive fan-out extraction rounds, agents launched in **ONE step in parallel**,
  in which **every** agent returned *"no other goal"*, evidenced by the round logs; **AND**
- **(b)** the verbatim **compared against reality**, double checked, working, verified.

⛔ **Do not stop to ask for the next instruction.** Findings, never a request. The cycle's exit is
the exhausted hunt, not an empty queue.

### 3. Routing — load exactly the ONE skill your loop point names

`brain-doctrine` (the seventeen laws, exact wording) · `brain-orient` (work-item selection) ·
`brain-verbatim` (capture and re-read the operator's raw words) · `brain-ladder` (phases, the loop
itself) · `brain-agents` (levels, registration, the dispatch prompt block) · `brain-recovery` (a
failed lane) · `brain-verify` (the independent verifier, evidence before claims) · `brain-gates`
(CI offload, the ask gate, the figure-it-out gate) · `brain-friction` (extraction returned nothing) ·
`brain-private-state` · `brain-report` · `brain-debug` (root cause before any fix) · `brain-audit`
(is a doc value an operational contract or an incidental mention, **before writing a gate**).

⛔ **Never ask what you can find.** Deciding what is derivable is the agent's job. Only a genuine
fork — no derivable answer by any route — reaches the operator.

⛔ **ADHD skills and plugins are banned in this project** by the operator's decision, not by
preference. Adding one back is a regression: those instructions tell the agent to **stop and ask**
things, which is the opposite of this stack's doctrine. Do not install one globally, vendor one,
or re-create the content under another filename.

### 4. VERIFY is independent, and evidence precedes every done-claim

The judge is **never the executor**. A done-claim without a pasted command output is a claim, not
evidence. ⛔ **A gate never observed failing is not a gate** — break it, paste the red, restore,
paste the green. A hand-fixed instance proves the sweep was **incomplete**, never that it was
thorough.

### 5. Stack integrity, run from the skill-stack repo (`~/Documents/projects/brain`)

```bash
timeout -k 5 120 python3 scripts/brain-query.py verify       # PASS required
timeout -k 5 120 python3 scripts/check-citations.py          # exit non-zero is reported, not worked around
timeout -k 5 120 python3 scripts/check-one-home.py           # report-only unless --fail
```

**Known, measured, recorded 2026-10-13:** `check-citations.py` is **red with 4656 findings, and 0
of them are in a live skill directory** — every one is in `docs/` provenance (verbatim archives,
lane sweeps, dated audits, coverage records) citing pre-split names. The gate is working as
designed; its exemption is deliberately narrow (`docs/verify-evidence*` only). **A gate that is
permanently red at that scale trains you to ignore it**, which is the failure
`brain-audit` exists to prevent. Treat this as **gate scope to fix**, not a broken skill.

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
| Consumer modules | `src/Teleframe/{AccountLifecycle,Ingest,Bus,Daemon,Backfill,Backup,Control}/` | Tenant-scoped Ingest (Postgres truth, `UpdateStored` event), Redis stream Bus (`tg:stream:updates`, consumer group `teleclient`, route table `tg:bus:routes`, DL `tg:stream:dead-letter`), multi-account `Daemon`, quota-aware `BackfillWorker`, encrypted `Backup` vault, `AccountLifecycle` (TfAccount's Eloquent observer → `AccountAdded`/`AccountRemoved`/`BotAdded` lifecycle events + best-effort reload signal on the bus reload channel for daemons). `Control` = relay seam ("sit in the middle"): `RelayMessage`/`RelayFilter`/`RelayPipeline` onion + lazy `AccountRelay` (built by `RelayFactory`/`RelaySessionResolver` in `src/Laravel/Services/`; vault scope, never dials at construction), `AccountUpdateStream` (bus → INBOUND relay message), PSR-16 `RelaySessionRegistry`, transport terminal `RelayTerminal` (OUTBOUND → bound client `call()`, reply → INBOUND) wired by `RelayFactory::terminalFor`, client-in frame-dial seam `RelayFrameDialBridge` (managed vault `SessionData` → lazily-dialed `EncryptedConnection`, `withFramePump()`, `swapScopeResolver()` = multi-account switch; `lazy()`/`RelayFactory::bridgeFor()` derive the connection from a live scope's `getSession()` on first use — both plain-PHP-constructible), obfuscated2 transport terminal `RelayServer`/`Obfuscated2Codec` in `src/Core/MTProto/Transport/`, presence API + feed + switch (`GET accounts/{id}/presence`, `POST accounts/{id}/presence/touch`, `POST accounts/{id}/switch` → `RelayPresenceController`/`RelaySwitchController` in `src/Laravel/Http/Controllers/Dashboard/`, routes at `DashboardRoutes.php:87-93`; **there is no bare `POST accounts/{id}/presence`** — the POST carries `/touch`, and the line range `:71-77` that an earlier version of this file cited is the dashboard/apps block, not these; feed via `PresenceFeed` in `src/Teleframe/Realtime/` → `account:{id}:updates` through `CentrifugoBridge`, `Control\RelayAccountsCatalog` switch menu; `Laravel\Services\RelayActiveAccount` persists the ACTIVE context in PSR-16, never a session string), `tgweb-patches/` client getDC hook. |
| More Teleframe | `src/Teleframe/{BotMap,Identity,Message,Mirror,Realtime,Repositories,Stage,Store,Testing,Vault}/` | `BotMap` (bot entry/transport/token vault), `Identity` (bindings, `TgUser`/`TlUserBinding`), `Message` (`MessageFactory`/`MessageCompiler`/`EntityParser`), `Mirror` (mirror models), `Realtime` (`PresenceFeed` + `CentrifugoBridge`/`ChannelNames`/`HttpCentrifugoBridge`), `Repositories` (`MessagesUnion`), `Stage` (stage-state registry + middleware), `Store` (S3/media store service), `Testing` (plain-PHP `FakeDispatcher`), `Vault` (credential vault models + `Vault` service). |
| Handlers | `src/Teleframe/Handler/` (+ `Middleware/`, `Subscriptions/`) | Handler-as-data `HandlerRegistry` (`on`/`onMessage`), zero-regex `HandlerMatcher` (exact / `prefix*` / `*`, first-match + priority), `Update` uprate (`fromBus`/`fromMirror`), 26-line `Pipeline` onion, `UpdateDispatcher` (DI-flavored invoke, lazy model hydration), `EchoEliminator` (Q2d send-time PSR-16 registry, `onOwn` escape hatch), `HandlerSink`/`UpdateStoredHandler` (both intake rows → one pipeline), faked-transport dispatcher in `Teleframe/Testing/` (`FakeDispatcher`). |
| Console | `src/Laravel/Console/` | `teleframe:login`, `teleframe:poll`, `teleframe:doctor`, `teleframe:schema-audit`, `teleframe:schema-update`, `teleframe:regenerate`, `teleframe:ingest`, `teleframe:ingest:gate`, `teleframe:backfill`, `teleframe:backup`, `teleframe:relay`, `teleframe:daemon`, `teleframe:tstore`, `teleframe:vault-add-account`, `teleframe:vault-add-app`, `teleframe:vault-list`, `teleframe:vault-use-default`, `teleframe:app`, `teleframe:clues`, `teleframe:passport`, `teleframe:reindex-search`, `teleframe:routing`, `teleframe:supervise` (23 commands; one class per file under `src/Laravel/Console/`). |
| Faces | `src/Teleframe/Teleframe.php`, `src/Teleframe/Teleclient.php` | `Teleframe` = one-class compose-not-own facade (also `docs/handlers.md`); `Teleclient` = ingest-only face, byte-stable. |
| Laravel glue | `src/Laravel/` | `TeleframeServiceProvider` (delegate-container wiring only), `Route::telegramWebhook` macro, `tg.miniapp` middleware, `TF`/`Teleframe` facades, config key `teleframe` (env `TELEFRAME_*`). |
| More Laravel | `src/Laravel/{Events,Http,Media,Models,Realtime,Store,Stubs}/` + `src/Laravel/{config,resources}/` | `Events` (`TelegramUpdateReceived`/`TelegramResynced`/`TelegramGapDetected`), `Http` (`Controllers/Dashboard/` incl. `DashboardRoutes.php` + relay/presence controllers; `Middleware/` `tg.miniapp`), `Media` (`StorageMedia`), `Models` (routing rules), `Realtime` (bus listeners, e.g. `UpdateStoredCentrifugoListener` — distinct from `Teleframe\Realtime`), `Store` (S3 gateway + hot cache), `Stubs` (`miniapp` stub), `config/` (published `teleframe.php`), `resources/` (views). |

## Generated artifacts — never hand-edit

Every path below is **owned** by the schema pipeline: editing one by hand makes the
regeneration-idempotence gate fail. Most also carry an `@generated` marker. **The
exception is `src/Laravel/Migrations/` — it carries NO marker, and `regen-gates.yml`
says so on purpose** ("the curated dial is hand-authored, so `--ship` is a no-op by
design"). Measured 2026-10-18, goal 46.84: `grep -rlE "@generated|do not edit"
src/Laravel/Migrations/` returns **0 files**. A blanket "every path carries a marker"
claim was wrong here and is corrected rather than inherited.

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

An `@generated` marker names the tool. It does **not** name the *environment* the
tool was run in, and where the output depends on that environment the marker is
not enough — see **"The engine that regenerates a committed artefact must be
named in the artefact"** under Hard rules, which carries the two measured
instances and the one shipped pattern (`| engine |` in
`docs/schema/fidelity-audit.md`'s Headline, enforced by `reportedEngine()`).

## Hard rules

- **The working tree is not the record — history is.** This file had no rule about
  repository state at all until 2026-10-18, and the cost was measured: goals
  46.73–46.80 were check-marked done in `.brain/verbatim-goals.md` while **eight of
  them produced no commit**, so all seven defect fixes existed only in the working
  tree, and `HEAD` was provably red (`tests/Config` — the config declared one
  `sets.default` key while `ConfigKeyRegistry` classified five) while two committed
  CI budget files asserted it green. Two agent-wipeout events are on record in
  `.brain/verbatim/2026-09-29-session-crashes.md:38-42`, where the operator's ruling
  — *"The working tree is the only trustworthy record"* — is **backwards for this
  project**. Therefore, binding:
  - A goal is **not complete** until `git status --porcelain` is empty for the
    files it touched, and its evidence has been re-measured **against a pinned
    commit** (`git rev-parse HEAD`), not against a dirty tree.
  - **No committed number may describe a tree that was never committed.** Timings,
    budgets and pass counts in `ci/shard-timings-raw.tsv`, `ci/shard-budgets.json`
    and `.brain/evidence/` are statements about a commit. Re-measure them after the
    tree is clean, or they are a falsehood every later reader inherits.
  - **A gate never observed failing is not a gate.** Re-break it, paste the red,
    restore, paste the green — and paste *both*. A hand-fixed instance proves the
    sweep was incomplete, never that it was thorough.
  - `git add` **explicit paths**, never `-A`: an unstaged change surviving *inside* a
    commit is recorded at `.brain/verbatim/2026-09-29-session-crashes.md:42`.
  - The operator's standing instruction
    (`.brain/verbatim/2026-10-17-notes.md`, 07:52:26 UTC) — *"i need all works of any
    agent in this project gets maanged by you and you decide the progress and commit
    and push all of them to a state i can say work is clear"* — is why this rule is
    a hard rule and not a preference.
- **The engine that regenerates a committed artefact must be named in the
  artefact.** This file had no rule about it until 2026-10-18, and the cost was
  paid **twice** — because a check's correctness depends on an *environment*, and
  nothing recorded which one. A window is an environment too, so all three
  instances below are one shape:
  - **2026-10-18, `bin/audit-fidelity.php`.** The tool is ENGINE-DEPENDENT and
    says so about itself, inside the `tf_dialog_filters.id` row of the committed
    report (`docs/schema/fidelity-audit.md`:110): *"SQLite cannot judge this
    column at all … which is why a SQLite run reports COPYING-GAP=0 and only the
    MySQL engine surfaces the row."* RE-MEASURED on this tree:
    `vendor/laravel/framework/src/Illuminate/Database/Schema/Grammars/SQLiteGrammar.php`
    — `typeInteger()` returns `'integer'` (:776) and `typeBigInteger()` returns
    the **same word** `'integer'` (:787), the two declared at :774 and :785. So
    SQLite cannot judge integer width or signedness at all, and a report
    regenerated there and committed was **not a stale report, it was a different
    document** — the same findings rendered by an engine that cannot see them.
    Only CI's `php bin/audit-fidelity.php --engine=mysql --check` refused, which
    is the only reason it was ever visible.
  - **2026-10-17, `bin/build-verbatim-teleframe.py`.** Its docblock advertised a
    regeneration check that **cannot run on the host it shipped on**: the default
    store `/home/me/.local/share/opencode/opencode.db` (:69) is another user's
    home, so the run dies on a permissions wall wearing the costume of a typo
    (`sqlite3.OperationalError: unable to open database file`). **A gate nobody
    could run was documented as if it ran** — the failure the "a gate never
    observed failing is not a gate" bullet above warns about, wearing a different
    hat. Repaired in place: the docblock now reads *"NOT RUNNABLE ON THIS HOST —
    MEASURED 2026-10-18 … an UNRUN gate, not a passing one. No agent may quote it
    as evidence"* (:22-36).
  - **Goal 46.82, the same shape one layer down.** A hardcoded 50-commit
    publish-baseline window answered an **unbounded** question, expired, and
    silently darkened every CI gate for three days. Nothing named the window's
    premise, so nothing noticed it had gone out of scope.
  - ⛔ Therefore, before committing anything a tool regenerated: **the
    environment that produced it is inside the artefact**, and a gate proves it is
    *readable* there. An engine named only in a sibling document, a CI YAML, a
    commit message, or the memory of whoever ran it is **not named in the
    artefact** — that is the definition this rule is fixing.
  - **The one shipped instance, and the pattern to copy.**
    `docs/schema/fidelity-audit.md` publishes `| engine | mysql |` in its
    Headline (`bin/audit-fidelity.php`:2252 emits that row), and
    `tests/Schema/FidelityAuditIsCurrentTest.php`:900-926 (`reportedEngine()`)
    treats the row's **absence as a hard failure** and re-runs every drift
    comparison on the engine the artefact names. Both predate the incident:
    `762cd0b9` (2026-10-06) added the row, `b5a28de7` (2026-10-11) added the
    gate, and `git merge-base --is-ancestor b5a28de7 f2b795d4` **measures** that
    the gate landed *before* `f2b795d4` — the 46.87 narrative which claims
    *"nothing in `docs/schema/fidelity-audit.md` says it was produced by MySQL"*
    (`.brain/verbatim-goals.md`:10420-10421), a claim false when written, since
    the row was in the artefact from its first commit. So the rule was
    **satisfied and unwritten**, which is precisely why the agent that
    regenerated the report did not think to look for the row. **Both halves are
    required: write the rule down AND put the name in the artefact.** Either one
    alone has now failed here.
  - ⛔ **A hand-edit is not a stamp.** `bin/audit-fidelity.php`:2397 rewrites
    `docs/schema/fidelity-audit.md` with a whole-file `file_put_contents()` built
    from an empty string (:2234), and `--check` compares that whole file with a
    strict `!==` (:2390-2391). MEASURED, so do not infer it: any hand-added line
    is **wiped by the next regeneration and reddens the gate in the meantime**. A
    stamp the generator does not emit is a falsehood with a maintenance window.
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
- A bus entry's `update` field deliberately carries the **committed row key**
  `(account_id, seq, position)`, not a decoded update array, because `tf_updates`
  stores zero JSON and has no surrogate primary key (S1 inversion, `docs/decisions.md`
  D-039…D-044). Do not "fix" it back to a payload — a consumer hydrates the row.
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
those parts as separate steps. The regeneration idempotence gate below **is**
invoked by CI — `.github/workflows/regen-gates.yml` declares the
`regen-idempotence` job and `.gitlab-ci.yml` runs it in the `regen` stage — so it
is a backstop you can run locally, not a step only you can run.

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
