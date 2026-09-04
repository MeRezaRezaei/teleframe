# Teleproto Package Split — Design Specification

Adds to: `2026-08-27-teleproto-core-design.md`, `2026-08-28-mtproto-wire-path-design.md`

## Problem

The two-package layout (`teleproto` wire engine + `teleclient` integration) has no
intermediate concern boundaries. `teleproto` mixes four separable concerns in one
package — the pure wire engine, the Laravel glue, the Bot API surface, and the
schema pipeline — so consumers cannot attach only the pieces they need, and the
two repos are simultaneously too entangled to separate and too far apart to merge.

## Decisions

1. **Namespace-preserving split.** Every class keeps its exact FQCN under
   `MeRezaRezaei\Teleproto\*`. Packages are split by PSR-4 subnamespace mapping,
   not by renaming. Consumer code (teleclient) does not change imports.
2. **Monorepo.** The existing `teleproto` repo becomes a monorepo with
   `packages/core`, `packages/laravel`, `packages/schema`, `packages/bot`.
   Git history is preserved via `git mv`. Publishing to Packagist (via subtree
   split or per-package release) is a final, separately-gated step.
3. **Four runtime concerns:**
   - `teleproto-core` — pure PHP wire engine: `MTProto/`, `Schema/` (registry +
     differ), `Methods/`, `Contracts/`, `Exceptions/`, `Types/`, `Entities/`,
     `Support/`, `Passport/`. Deps: PHP extensions, `phpseclib/phpseclib`,
     `vlucas/phpdotenv`, `symfony/console`, `composer-runtime-api: ^2.0`.
     **No illuminate/* packages.**
   - `teleproto-laravel` — illuminate-coupled glue: `Services/`, `Events/`,
     `Http/`, `Console/`, `Facades/`, `Media/`, root `TeleprotoServiceProvider`.
     Requires `teleproto-core`.
   - `teleproto-schema` — schema pipeline: `schema/` artifacts + sources,
     `bin/generate-*`, `config/curated-methods.json`, generated `skills/`.
     Own namespace `MeRezaRezaei\TeleprotoSchema\`. Core requires it (runtime
     artifact lookup); it is zero-dependency apart from PHP extensions.
   - `teleproto-bot` — Bot API surface: `Services/BotClient.php`,
     `Services/BotAccountScope.php`, `Methods/Generated/Bots.php`. Requires
     core (and illuminate/http, matching current implementation).
4. **Artifact lookup.** `Schema/MethodRegistry` (core) no longer resolves
   `dirname(__DIR__, 2) . '/schema/...'`. It calls
   `MeRezaRezaei\TeleprotoSchema\SchemaArtifacts::path('methods-mtproto.json')`,
   which resolves via `Composer\InstalledVersions::getInstallPath()`.
5. **Dev linking.** `teleclient` consumes the new packages via untracked path
   repositories (`../teleproto/packages/*`), same discipline as today's
   `repositories.teleproto` link. Published constraints on `teleclient` main
   stay on `^1.2.1 || ^1.1` until the new packages are released.
6. **Pinned install paths.** A small root composer plugin pins
   `packages/*` to `vendor/merezarezaei/teleproto-{core,schema,bot}` so
   overlapping test expectations and the dev-link flow stay deterministic.

## Non-Goals

- No behavior changes to any class. This is a structural extraction only.
- No new public API apart from `SchemaArtifacts::path()`.
- No Packagist release inside this plan (final task defines the gate only).
- `UserAccountScope` stays in `teleproto-laravel` (all current consumers are
  Laravel-side); moving it to core is a possible future non-breaking follow-up.

## Acceptance

- `composer test` from repo root runs core, laravel, schema, bot suites green.
- `composer verify` (root) passes: install, pin check, all suites, phpstan ×3,
  regeneration idempotence (`git diff --exit-code` after running generators).
- teleclient suite green against path-linked new packages, zero diff in
  `teleclient/src`.
- `grep -r "Illuminate" packages/core/src` returns nothing.
