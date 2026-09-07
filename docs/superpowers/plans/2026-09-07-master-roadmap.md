# Teleframe Master Roadmap — Phase 0 → Vision (End-to-End)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. NOTE: this is a ROADMAP-OF-PLANS — each phase below names its own detailed plan file (written in full writing-plans format when the phase starts, per the vision's planning rule). Executing a phase = write/execute that phase's plan, then tick its gate here.

**Goal:** Take the two repos from today (unified engine package + standalone-capable client package) to the full vision: one framework where Telegram updates are first-class requests (uprates) with routing, validation, middleware, identity bindings, keyboard objects, message templates, a stage machine, and the bot map — Laravel patterns inherited, never rewritten.

**Architecture:** Substrate-first: Phases 0–4 (unification spec) make teleframe a single standalone-capable package with a handler substrate. Framework layers 5a–5g then bolt onto that substrate, sequenced by dependency: routing first (everything dispatches through it), identity second (keyboards/stages/mini-apps need bindings), then presentation, then composition. Every layer's spec is gated by the open questions in the gap-analysis doc.

**Tech Stack:** PHP 8.2+, illuminate/* components (standalone), PSR-3/11/16/18 seams, Redis (bus/limiter/stages), Postgres (mirror), ext-hash HMAC (ext-sodium post-Phase-2).

**Spec:** `2026-09-07-teleframe-unification-design.md` (substrate, D1–D8) · `2026-09-07-teleframe-vision-verbatim.md` (upstream truth, 12-layer index) · `2026-09-07-framework-layers-gap-analysis.md` (research, Q1–Q20, hard constraints 1–8)

## Global Constraints (bind every phase)

- Vision doc is upstream truth; plans that conflict with it are wrong.
- Gap-mining before every layer plan (vision's rule) — Phase 5+ plans cite the gap doc.
- Zero-regex in src/ (both repos); generated artifacts never hand-edited; sessions never committed.
- Gates: teleframe `composer verify`; teleclient `composer test && composer analyse` (until Phase 2 absorbs it).
- No runtime schema adoption; migrations always explicit (D3/D4).
- Hard constraints 1–8 of the gap-analysis doc (callback 64B budget, schemaLayer cache salt, tenancy contract, at-least-once dedup key, no-Vue-precedent, webhook-200 contract, sodium split-brain, zero-regex matcher).

---

### Phase 0: Standalone Foundation — READY TO EXECUTE

**Plan:** `plans/2026-09-07-phase0-standalone-foundation.md` (full detail, 5 tasks)

- [ ] Gate: all 5 tasks green; `bin/standalone-smoke.php` exit 0; both repos' gates pass; committed + pushed

### Phase 1: Schema Upgrade Pipeline

**Delivers (spec §6, D4/D5):** unified `teleframe:schema-update` command (diff + regenerate + stamp, NO implicit migrate), `Teleframe::schemaLayer(): int`, composer `extra.telegram-layer`, skill v2 activation. Also: cache-salt primitive that Phase 5d depends on.

- [ ] Write plan `plans/YYYY-MM-DD-phase1-schema-upgrade-pipeline.md` (writing-plans format)
- [ ] Gate: command runs end-to-end on a synthetic layer bump; layer stamp queryable; gates green

### Phase 2: Module Merge

**Delivers:** Ingest/Bus/Daemon/Backfill/Backup move into `teleframe/src/Teleframe/*` behind PSR seams (one module per task, tests move with modules); ext-sodium arrives here (gap constraint 3 clears); teleclient repo archived after.

- [ ] Write plan `plans/YYYY-MM-DD-phase2-module-merge.md`
- [ ] Gate: single package, both suites green inside teleframe, plain-PHP construct of every module proven

### Phase 3: Handler Substrate

**Delivers:** handler-as-data registry + middleware onion (26-line chain) + `onMessage()` subscription + single composed `Teleframe` facade + Fake RunningMode testing surface (dissolves frictions I.1/.2/.3 partially).

- [ ] Write plan `plans/YYYY-MM-DD-phase3-handler-substrate.md`
- [ ] Gate: fake uprate flows the real pipeline in tests; facade one-class DX documented; gates green

### Phase 4: Laravel Thin Bridge

**Delivers:** provider shrinks to delegate-container wiring; `extra.laravel` final shape; Packagist publish readiness; dead code deleted (HotReloadRouter, unwired hooks, src-compat).

- [ ] Write plan `plans/YYYY-MM-DD-phase4-laravel-bridge.md`
- [ ] Gate: Laravel app + plain-PHP script both drive the identical facade; docs refreshed (AGENTS/README/llms)

---

## FRAMEWORK LAYERS (each: decide open questions → spec → plan → execute)

### Phase 5a: Uprate Router + Loop Prevention — FIRST, everything dispatches through it

**Pre-decided by gap analysis:** send-time registry is the only design that survives the phone-typing subtlety; mirror stays truth-complete (two-layer separation).
**Decide first:** Q1 (routes truth), Q2 (elimination placement), Q3 (registry key), Q4 (uprate construction), Q5 (execution model), Q6 (response contract) — gap doc §I.
**Delivers:** Uprate object; declarative route registration (code-declared, compiled/cached, zero-regex matching with sscanf-style params); uprate validation object (FormRequest analog); loop-prevention registry + elimination middleware; uprate identity/dedup key; reply path with account context preserved.

- [ ] Spec written with RULINGS Q1–Q6 applied (gap doc §RULINGS); owner gate = spec review only
- [ ] Spec: `specs/YYYY-MM-DD-uprate-router-design.md`
- [ ] Plan + execution
- [ ] Gate: `/start {arg}`-style routing works zero-regex; self-echo eliminated while mirror still stores it; replay dedup proven by test

### Phase 5b: Identity & Laravel Bindings

**Decide first:** Q7–Q12 — gap doc §II. Structural recommendation from research: package-owned `tl_user_bindings` with nullable morph (works standalone AND in Laravel).
**Delivers:** binding table + write hooks (UpdateStored/login); `findTF` (with chosen tenancy strategy + tl_id index migration); `HasTelegram` (+ Telegram notification channel + sender resolution); `HasUserTelegram`; guard trio (auth.php guards over the three scopes); mini-app initData freshness fix + RequestGuard upgrade.

- [ ] Spec written with RULINGS Q7–Q12 applied; owner gate = spec review only (Q7 veto-able)
- [ ] Spec + plan + execution
- [ ] Gate: `User::findTF()` resolves in a Laravel test app; a Notification delivers via Telegram; replayed initData rejected

### Phase 5c: Keyboard Objects

**Decide first:** Q15 (callback format), Q16 (id lifecycle) — gap doc §III. Depends: 5a (routing), 5b optional (per-user scoping).
**Delivers:** keyboard objects with deterministic identity; key→action table = uprate routes; callback_data format within 64B (signed or handle per Q15); rotation/expiry ("menu expired"); injection test suite (forge/replay/relay vectors).

- [ ] Spec written with RULINGS Q15–Q16 applied · Plan · Execution
- [ ] Gate: forged callback_data rejected; old menus expire; keyboard code separated from handler code in example app

### Phase 5d: Message Templates (Blade-for-Telegram)

**Decide first:** Q13 (syntax), Q14 (cache artifact) — gap doc §III. Depends: Phase 1 (schemaLayer salt — hard constraint 4).
**Delivers:** MessageCompiler (compile→cache in bootstrap/cache pattern, salted by schemaLayer); `message('name')` finder; entity-plan compilation over EntityParser; sendMessage typecheck vs MethodRegistry; view:clear-style ops.

- [ ] Spec written with RULINGS Q13–Q14 applied · Plan · Execution (Q13 veto-able)
- [ ] Gate: template renders to entities; layer bump invalidates cache without mtime change; gates green

### Phase 5e: Stage Machine (same route, telegram-paced)

**Decide first:** Q17 (state home), Q18 (context flag), Q20 (submit transport) + dispatch precedence (echo → stage → keyboard → handler, gap F5). Depends: 5a router, 5c keyboards, 5d templates.
**Delivers:** declarative stage sets (form-fields shape, plain-array state); per-stage template-shaped validation failures; final submit to THE SAME Laravel route reusing its FormRequest, response flipped to telegram engine (above-controller interceptor, hard constraint 8).

- [ ] Spec written with RULINGS Q17/Q18/Q20 applied (dispatch precedence set: echo → stage → keyboard → handler) · Plan · Execution
- [ ] Gate: one controller+FormRequest serves a web form AND a telegram staged flow identically; state survives process restart

### Phase 5f: Bot Map

**Decide first:** Q19 (discovery). Depends: none heavy (independent of 5b–5e).
**Delivers:** named third-party bot registry; capability manifest; `BotMap::for('x')->command()` invocation; token vault; outbound HTTP exposure of bots as API calls (webhook controller precedent).

- [ ] Spec written with RULING Q19 applied · Plan · Execution
- [ ] Gate: third-party bot invoked as function call; exposed via authenticated HTTP endpoint

### Phase 5g: Mini-App Hosting

**NOTE from research:** the Vue precedent does NOT exist in the repo — this phase BUILDS it (constraint 7). Depends: 5b (identity/guards).
**Delivers:** publishable Vite preset + Blade host + telegram-web-app.js bridge + theme CSS vars (per Q12 decision); mini-app session guard wired to bindings.

- [ ] Spec written with RULING Q12 applied (stub-based hosting) · Plan · Execution
- [ ] Gate: example Vue mini app runs inside Telegram, authenticated as the bound Laravel user

---

## Autonomy protocol (binding — vision doc §Autonomy verbatim)

1. **Owner decides RESULTS** (what a layer does, its public behavior, spec/phase acceptance). **AI decides PATHS** (task order, approach, mechanisms, refactors, test structure — anything where the result is unchanged).
2. **No option menus mid-execution.** Executors never ask the owner "which approach"; they rule, record the ruling + assumption in the task report, and continue. Wrong rulings surface at the result gate, where they cost a review comment instead of a stalled plan.
3. **Ordering autonomy:** where phases/tasks are order-independent, the executor chooses the order that minimizes risk and rework (this roadmap's arrows are dependency constraints, not a queue the owner manages).
4. **Execution mode is a PATH decision** — chosen by the executing agent (default: subagent-driven), never asked of the owner.
5. **Repo is static truth:** every decision, ruling, spec, plan, and result lands committed + pushed. Memory is derived from repo facts, never the reverse.

## Roadmap rules

1. Phases 0→4 strictly ordered (each lands green before next). 2. Layer 5a gates 5c/5e; 5b gates 5g; 5d needs Phase 1 only — within those arrows, ordering is executor's choice. 3. Any new friction discovered mid-phase gets gap-doc'd with an AI ruling, not inline-designed and not owner-asked. 4. Every phase ends: gates green → spec/plan committed → pushed → ticked here → owner reviews the RESULT. 5. Vision doc amendments require owner verbatim additions only. 6. All Q1–Q20 rulings live in the gap-analysis doc §RULINGS; a ruling may be revisited by a NEW ruling that supersedes it in writing, never by an untracked conversation.
