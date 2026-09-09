# Live-Test Readiness Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring `teleframe` from green-offline to live-test-ready: every opt-in live gate runnable with documented credentials, no committed secrets, offline gates still green.

**Architecture:** No wire/schema/handler changes unless a live gate proves a bug. Work is checklist + docs + env + small DX repairs only. Offline gates stay the authority; live gates stay opt-in and never enter CI.

**Tech Stack:** PHP 8.2+, `composer verify`, `bin/standalone-smoke.php`, `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg`, `./bin/teleframe doctor|me|test-e2e`, `examples/live-doctor.php`, `examples/batch-bench.php`.

**Spec:** `AGENTS.md` (gates + hard rules + known gaps 2026-09-09) · `docs/superpowers/plans/2026-09-07-master-roadmap.md` (phases 0–5g ticked) · `bin/test-me.php` · `bin/test-e2e.php` · `bin/live-walkthrough.php` · `src/Laravel/Console/DoctorCommand.php`

## Global Constraints

- Zero regex in `src/` outside allow-list (`src/Laravel/*`, `src/Schema/*`, `src/Bot/*` only); `src/Teleframe/Handler/*` and `src/Core/*` strictly zero-regex.
- Never hand-edit generated artifacts (`schema/methods-*.json`, `generated/**`, `src/{Core,Bot}/Methods/Generated/*`, skill md, `RpcErrorCatalog.php`) — regenerate only.
- Session strings are credentials: `.env` never committed (gitignored line 4); tests never require real credentials; live gates opt-in only.
- Redis wire keys opaque: `tg:stream:updates` / group `teleclient` / `tg:bus:routes` / DL / reload channel — do not rename.
- Public bind keys fixed: `teleclient.backfill.scope-resolver`, `teleclient.backfill.ingester`, `teleclient.backup.vault-factory`.
- Layer 227 wire vs 229 schema gap intentional — never "fix".
- `composer.lock` gitignored; `tests/` mirrors `src/`.

---

### Task 1: Live-gate inventory + ready criteria

**Files:**
- Modify: `docs/superpowers/plans/2026-09-09-live-readiness.md` (this file, Task 1 section)
- Test: n/a (docs only)

**Interfaces:**
- Consumes: AGENTS.md gates, bin/teleframe actions, config/teleframe.php env keys
- Produces: READY checklist used by Tasks 2–5

- [ ] **Step 1: Record the live-gate inventory (verified 2026-09-09)**

Live gates (all opt-in, never CI):

| # | Gate | Command | Needs | Proves |
|---|---|---|---|---|
| L0 | Offline baseline | `composer verify` + `php bin/standalone-smoke.php` + `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg` | nothing | 998 tests / phpstan clean / smoke exit 0 / Pg 8 tests OK |
| L1 | Doctor, no account | `TG_API_ID=.. TG_API_HASH=.. php examples/live-doctor.php prod50` or `./bin/teleframe doctor` | API id+hash from my.telegram.org | TCP + framing + DH handshake + `help.getNearestDc` Layer 227 |
| L2 | Login wizard | `./bin/teleframe login` | phone/bot token, writes `.env` | session string persisted, never committed |
| L3 | Saved session | `./bin/teleframe me` (`bin/test-me.php`) | `TELEGRAM_USER_SESSION` in `.env` | `help.getNearestDc` + `users.getUsers inputUserSelf` live |
| L4 | Walkthrough | `php bin/live-walkthrough.php` (needs `.env`) | L3 session | getHistory/searchContacts/sendMessage+entities/deleteMessages |
| L5 | Full e2e | `./bin/teleframe test-e2e` (`bin/test-e2e.php`) | user session + `TELEGRAM_BOT_TOKEN` | user MTProto + bot API + exception guidance |
| L6 | Batch bench | `php examples/batch-bench.php` (needs `.env`) | L3 session | `callBatch` msg_container 2.3–2.7× vs sequential |

Env keys (from `src/Laravel/config/teleframe.php`): `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`, `TELEGRAM_DC_ID` (default 2), `TELEFRAME_LIVE`, `TELEGRAM_USER_SESSION`, `TELEGRAM_BOT_SESSION`, `TELEGRAM_BOT_TOKEN`, `TELEGRAM_WEBHOOK_SECRET`, `TELEFRAME_LOGGER`.

READY = L0 green (done) + L1 runnable documented + L2→L3→L4 path documented with `.env.example` + L5/L6 documented as optional + no secrets in git + Tasks 2–4 closed or logged as accepted gaps.

- [ ] **Step 2: Commit**

Run: `git add docs/superpowers/plans/2026-09-09-live-readiness.md && git commit -m "docs(plan): live-test readiness plan with gate inventory"`
Expected: commit created on working branch.

---

### Task 2: `.env.example` + live docs (the only files most users touch)

**Files:**
- Create: `.env.example`
- Modify: `docs/quickstart.md` (append live-test section only, leave existing content intact)
- Test: `tests/Standalone/EnvExampleTest.php` (new — asserts example exists, has every key, carries no session value)

**Interfaces:**
- Consumes: config/teleframe.php env keys (Task 1 table)
- Produces: copy-paste live bootstrap; test guards key drift

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Standalone;

use PHPUnit\Framework\TestCase;

final class EnvExampleTest extends TestCase
{
    public function testExampleExistsWithEveryKeyAndNoSecrets(): void
    {
        $path = dirname(__DIR__, 2) . '/.env.example';
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        foreach (['TELEGRAM_API_ID=', 'TELEGRAM_API_HASH=', 'TELEGRAM_DC_ID=', 'TELEFRAME_LIVE=', 'TELEGRAM_USER_SESSION=', 'TELEGRAM_BOT_SESSION=', 'TELEGRAM_BOT_TOKEN=', 'TELEGRAM_WEBHOOK_SECRET=', 'TELEFRAME_LOGGER='] as $key) {
            $this->assertStringContainsString($key, $content, "missing {$key}");
        }
        $this->assertStringNotContainsString('6ad060e', $content);
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9_-]{40,}/', $content);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Standalone/EnvExampleTest.php -v`
Expected: FAIL with "Failed asserting that file exists" (no `.env.example` yet).

- [ ] **Step 3: Write minimal implementation (`.env.example`)**

```ini
# Copy to .env and fill in — NEVER commit .env (gitignored).
TELEGRAM_API_ID=
TELEGRAM_API_HASH=
TELEGRAM_DC_ID=2
TELEFRAME_LIVE=false
TELEGRAM_USER_SESSION=
TELEGRAM_BOT_SESSION=
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
TELEFRAME_LOGGER=
# Live ladder: ./bin/teleframe doctor → ./bin/teleframe login → ./bin/teleframe me → php bin/live-walkthrough.php → ./bin/teleframe test-e2e
```

- [ ] **Step 4: Append live-test section to `docs/quickstart.md`** (add at end, change nothing else):

```markdown
## Live test (opt-in, needs Telegram credentials)

1. `cp .env.example .env` and fill `TELEGRAM_API_ID` + `TELEGRAM_API_HASH` (my.telegram.org).
2. `./bin/teleframe doctor` — no account needed (handshake + `help.getNearestDc`).
3. `./bin/teleframe login` — writes the session string into `.env` (never commit it).
4. `./bin/teleframe me` — live `users.getUsers inputUserSelf`.
5. Optional: `php bin/live-walkthrough.php`, `./bin/teleframe test-e2e`, `php examples/batch-bench.php`.
```

- [ ] **Step 5: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Standalone/EnvExampleTest.php -v`
Expected: PASS.

- [ ] **Step 6: Run gates**

Run: `composer verify 2>&1 | tail -5`
Expected: still green (999 tests now), phpstan clean.

- [ ] **Step 7: Commit**

```bash
git add .env.example docs/quickstart.md tests/Standalone/EnvExampleTest.php docs/superpowers/plans/2026-09-09-live-readiness.md
git commit -m "feat(live): .env.example + quickstart live ladder + key-drift test"
```

---

### Task 3: Pre-live offline hardening (fix or accept the three known non-blockers)

**Files:**
- Modify: only what the gate proves broken; default = docs note, no code
- Test: existing suites (no new tests unless a bug is proven)

**Interfaces:**
- Consumes: Task 1 inventory
- Produces: Pg collation note + proxy/layer-gap docs OR minimal fixes with tests

- [ ] **Step 1: Pg collation warning (accept + document, 2 min)**

Run: `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg 2>&1 | tail -4`
Expected: OK (8 tests) with `collation version mismatch 2.39 vs 2.43` warning.

Action: append 3 lines to `docs/scaling.md` (or `docs/ingest.md` if scaling lacks a Pg ops spot — check first, pick exactly one):

```markdown
<!-- ops note: Pg collation warning `2.39 vs 2.43` on dev boxes is benign; on prod run `ALTER DATABASE <db> REFRESH COLLATION VERSION`. -->
```

Do NOT rebuild the cluster in this task.

- [ ] **Step 2: Proxy + Layer-gap + transport-logging triage (document, no code unless live proves otherwise)**

Verify the three AGENTS.md gaps are still accurately described (grep `@todo` in `StreamSocket`, `LAYER` const in `EncryptedConnection`, logger uses in `Core/MTProto`):

Run: `grep -rn "@todo" src/Core/MTProto/Transport/ | head -5; grep -rn "const LAYER" src/Core/MTProto/ | head -5; grep -rln "LoggerInterface" src/Core/MTProto/ | head -5`
Expected: proxy `@todo` present, `LAYER = 227`, no logger in `Core/MTProto`.

Action: if all three match, no code — the gaps stay as documented accepted limitations for live test (proxy users must run direct; layer gap intentional; transport runs silent). If any differ, update AGENTS.md wording to match code (code wins).

- [ ] **Step 3: Run gates**

Run: `composer verify 2>&1 | tail -3 && php bin/standalone-smoke.php 2>&1 | tail -3`
Expected: green.

- [ ] **Step 4: Commit**

```bash
git add docs/scaling.md docs/ingest.md AGENTS.md docs/superpowers/plans/2026-09-09-live-readiness.md
git commit -m "docs(live): Pg collation ops note + pre-live gap triage (proxy/layer/logging accepted)"
```

---

### Task 4: Live dry-run matrix (credential-gated; SKIP with evidence when absent)

**Files:**
- Modify: `docs/superpowers/plans/2026-09-09-live-readiness.md` (append results table)
- Test: live scripts themselves (not phpunit)

**Interfaces:**
- Consumes: `.env` (absent → skip with evidence; present → run ladder)
- Produces: results table proving READY or naming the blocker

- [ ] **Step 1: Attempt L1 (doctor — needs only API id+hash)**

Run: `ls .env 2>&1; TG_API_ID=${TG_API_ID:-0} TG_API_HASH=${TG_API_HASH:-} php examples/live-doctor.php prod50 2>&1 | head -5`
Expected A (no creds): usage error → record `L1 SKIPPED (no credentials)` with output.
Expected B (creds): `OK in Nms — full handshake + encrypted help.getNearestDc`.

- [ ] **Step 2: Attempt L3/L4 only if `.env` has a session**

Run: `[ -f .env ] && ./bin/teleframe me 2>&1 | head -10 || echo "L3 SKIPPED (no .env session)"`
Expected: either live profile output or clean SKIP line. Never invent credentials.

- [ ] **Step 3: Record results in this plan file** (append):

```markdown
## Live dry-run 2026-09-09

| Gate | Result | Evidence |
|---|---|---|
| L1 doctor | SKIPPED/OK | ... |
| L3 me | SKIPPED/OK | ... |
| L4 walkthrough | SKIPPED/OK | ... |
| L5 e2e | SKIPPED/OK | ... |
```

- [ ] **Step 4: Commit**

```bash
git add docs/superpowers/plans/2026-09-09-live-readiness.md
git commit -m "docs(live): dry-run matrix 2026-09-09 (credential-gated results)"
```

---

### Task 5: Merge to main + READY stamp

**Files:**
- Modify: `CHANGELOG.md` (Unreleased → add live-readiness entry), `docs/superpowers/plans/2026-09-07-master-roadmap.md` (append live-ready gate line)
- Test: full gates

**Interfaces:**
- Consumes: Tasks 1–4 commits
- Produces: main is live-test-ready; tag optional

- [ ] **Step 1: Run FULL verification on the branch**

Run: `composer verify 2>&1 | tail -3`
Run: `php bin/standalone-smoke.php 2>&1 | tail -3`
Run: `TELEFRAME_PG=1 vendor/bin/phpunit tests/Pg 2>&1 | tail -3`
Expected: all green (Pg warning accepted).

- [ ] **Step 2: Merge (fast-forward if alone, PR if collaborators)**

Run: `git checkout main && git merge --ff-only <branch> || git merge <branch>`
Expected: main updated, no conflicts (branch was cut from fresh main).

- [ ] **Step 3: Stamp CHANGELOG + roadmap**

CHANGELOG Unreleased `### Added`:

```markdown
- **Live-test ready (2026-09-09):** `.env.example` + quickstart live ladder (`doctor → login → me → walkthrough → test-e2e`); Pg collation ops note; credential-gated dry-run matrix in `docs/superpowers/plans/2026-09-09-live-readiness.md`. Offline gates green (999 tests); live gates remain opt-in, never CI.
```

Roadmap append: `- [x] Live-test ready 2026-09-09: ladder documented, dry-run matrix recorded, main green.`

- [ ] **Step 4: Run gates once more on main + push**

Run: `composer verify 2>&1 | tail -3 && git push origin main`
Expected: green + pushed.

- [ ] **Step 5: Declare READY**

READY = L0 green on main + ladder documented + dry-run matrix recorded (OKs or credential-gated SKIPs with evidence) + no secrets in `git log --all -- .env` + working tree clean.
