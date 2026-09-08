# Phase 5e — Stage Machine (execution plan)

**Parent:** `specs/2026-09-08-stage-machine-design.md` (this layer's spec) +
`docs/superpowers/plans/2026-09-07-master-roadmap.md` Phase 5e.
**Created:** 2026-09-08. **Depends on:** Phase 5a uprate router (the shared
`HandlerRegistry`/`UpdateDispatcher` the stage middleware plugs into),
Phase 5d message templates (the prompt/error plans the flow sends).

## RULINGS enacted (gap doc §III)

- **Q17 (a):** plain-array PSR-16 state `{stageSet, currentStage, data[], msgIds[]}`
  under `teleframe.stage.state.{accountId}` — never serialized objects;
  deterministic key so supervisor restarts resume verbatim; one write per
  advance. The key prefix uses `.` (not `:`) because
  `FilesystemCache::assertKey()` rejects PSR-16 reserved characters
  (`{}()/\@:`) and caps keys at 64 bytes.
- **Q18 (b):** the in-flow flag `teleframe.stage.in_telegram_flow` is a
  container binding raised around the whole exchange (enter/leave stack) and
  mirrored on a flagged `TelegramContext` (`inStageFlow()`, additive field on
  the existing Handler class).
- **Q20 (c):** finished flows submit in-process — `stageErrors()` against the
  `StageFormRequest`'s own rules first, then
  `Request::create($uri, $method, $data)` resolved through the host bridge
  closure. No socket, no HTTP, loop-safe by construction; submit failures
  keep the state for a retry.
- **F5:** active stage sits between echo/dedup and the keyboard/general
  handlers (onion order echo → dedup → stage → recorder/general).

## Findings recap

- `FakeDispatcher` hardwires `[$recorder]` as its only extra middleware
  (worker-owned, uneditable), so stage precedence is proven through a real
  `UpdateDispatcher(..., [$stage, $recorder])`; the FakeDispatcher suite
  covers only echo → keyboard → handler ordering with no active stage.
- `HandlerMatcher` is first-match over priority-then-registration order, so
  the catch-all `onMessage()` **must be registered last** or it swallows the
  callback and confirm routes.
- `StageFormRequest::rulesFor()` must guard with
  `is_subclass_of($class, self::class)`; the concrete guard throws because a
  stale autoload made a fixture class invisible (namespace path
  `tests/Stage/Fixtures/` is case-sensitive — `fixtures/` broke PSR-4).
- `stage()` after an explicit final is **allowed** (no ordering
  restriction); only a second explicit `final()` is rejected; the
  walker reports the flagged stage as complete regardless of position.
- phpstan treats array-shape PHPDocs as certain — the stages-map parameter
  is typed `array<string, mixed>` and checked at runtime, otherwise level 5
  flags the guards as tautologies.

## Tasks

- [x] **Task 1: `StageSet` value object.** Declarative map + fluent builder
      (exact same features, §3 of the spec): fields, rules (default
      `required|string`), expects (default = set name), prompts/errors,
      single explicit final, submit wiring + submit-error template (default
      `stages.invalid_submit`), `compile()` freeze, the full introspection
      surface. Tests: walker, defaults, explicit overrides, duplicate and
      mis-declaration rejection, final/submit rules, unknown-stage defaults.
- [x] **Task 2: `StageRegistry`.** `on(set, submit?)`, `compile()` freeze,
      `match`/`setFor` (exact name first, then pattern), `submitFor`, full
      HandlerMatcher grammar via `patternMatches` (zero-regex `sscanf`).
      Tests: precedence, submit closures, defensive unknown handling.
- [x] **Task 3: `StageState` (Q17).** Plain-array PSR-16 state with
      `start`/`advance` (one write, safe fallback when the set can't be
      resolved)/`current`/`has`/`touch`/`finish`; per-account keys. Tests:
      survival across a second instance AND a real separate PHP process
      (`exec` supervisor-restart proof), touch dedup, finish, per-account
      isolation, PSR-16-safe key format.
- [x] **Task 4: `StageValidator` + `StageFormRequest`.** Dependency-free
      fallback rules engine (`required`/`email`/`accepted`/`array`/`bool`/
      `int`/`string`/`in:`), trimming, typed violations; abstract
      `StageFormRequest` with static `rulesFor`/`validateStageData`/
      `stageErrors` bridging the abstract `rules()`. Tests: rules, trimming,
      subclass gate, agreement with the Web validator (tests/Stage/Support).
- [x] **Task 5: `StageMiddleware` (F5 + Q20).** Pass-through surfaces (no
      state, unknown set, expects mismatch), capture+advance+prompt via
      MessageFactory plans, per-stage error templates, all three submit
      modes (closure / FormRequest + in-process Request / error+finish), the
      in-flow container flag + flagged TelegramContext with stack restore,
      outbox fallback when no reply seam. Tests mirror every branch; the
      invalid-form leg uses a permissive per-stage rule so the FormRequest's
      own `accepted` rule is the one that rejects at submit time.
- [x] **Task 6: Precedence suite.** F5 ordering on the real dispatcher: active
      stage consumes before the general handler; echo stays first; idle
      stage leaves keyboard and general routing untouched.
- [x] **Task 7: docs.** `docs/stages.md` (compose/start/advance/prompt/
      submit/finish + F5 story), this spec + plan. Roadmap Phase 5e ticks
      are the coordinator's step (`docs/superpowers/plans/2026-09-07-
      master-roadmap.md` is on the do-not-touch list for this worker).

## Gate evidence (2026-09-08)

- `vendor/bin/phpunit tests/Stage` → `OK (53 tests, 165 assertions)` —
  includes the Q17 restart proofs, the Q20 in-process Request leg, all
  submit modes, and the F5 precedence suite.
- `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress` →
  `[OK] No errors` (zero-regex enforced; no `preg_*` in `src/Teleframe/Stage`).
- `composer verify` → `Tests: 827, Assertions: 13736, Skipped: 5` OK,
  phpstan `[OK] No errors`, regeneration idempotence `179/179` clean.

**Commits:** none (coordinator commits; Phase 5e files in the tree remain
owned by the 5e worker). Roadmap Phase 5e ticks are the coordinator's step
(`docs/superpowers/plans/2026-09-07-master-roadmap.md` is on the do-not-
touch list for this worker).