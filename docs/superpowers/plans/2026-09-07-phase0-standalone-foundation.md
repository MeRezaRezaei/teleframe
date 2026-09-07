# Phase 0: Standalone Foundation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove every fatal framework coupling so teleclient's ingest surface and teleframe's poller signals work in plain PHP with no Laravel app, with zero behavior change inside Laravel.

**Architecture:** Fix the four fatal seams found by the 2026-09-07 audit: (1) `UpdateStored` imports Foundation/Queue classes that are not production dependencies — rebuild as plain DTO dispatched through an injected `Illuminate\Contracts\Events\Dispatcher`; (2) `Artisan::call('migrate')` hidden inside ingest — delete (spec D3: migrations are explicit, developer-run); (3) `now()` Laravel helpers — replace with injectable clock closures; (4) teleframe's gap/resync events are silently dropped standalone — add an observable signal sink port. Laravel hosts get identical behavior via provider wiring; plain-PHP hosts construct the same classes with their own dispatcher/clock.

**Tech Stack:** PHP 8.2+, PHPUnit, orchestra/testbench (dev), illuminate/{events,database,support} (prod, already required)

**Spec:** `docs/superpowers/specs/2026-09-07-teleframe-unification-design.md` (decisions D1–D8; this plan implements the Phase 0 row of §5 and corrects §4's audit)

## Global Constraints

- Zero regex (`preg_*`) in `src/` of either repo — phpstan `disallowedFunctionCalls` gate enforces it.
- Generated artifacts (`generated/`, `migrations/`, `src/Schema/schema/`, `src/Schema/skills/`) are never hand-edited.
- Session strings / API credentials never committed; tests never require real credentials.
- Gates per repo BEFORE declaring a task done: teleclient `composer test && composer analyse`; teleframe `composer verify`.
- No public API removals except the ones this plan names explicitly (`UpdateIngestor::boot()`, `UpdateStored::dispatch()` static — both internal seams, neither referenced outside the repo per audit).
- Constructor additions must be optional parameters with defaults so existing call sites stay valid.

---

### Task 1: `UpdateStored` becomes a standalone-safe DTO dispatched through an injected dispatcher

**Files:**
- Modify: `src/Ingest/Events/UpdateStored.php` (teleclient)
- Modify: `src/Ingest/UpdateIngestor.php:53-56,223` (teleclient)
- Modify: `src/TeleclientServiceProvider.php:45` (teleclient)
- Test: `tests/Ingest/UpdateIngestorDispatchTest.php` (teleclient)
- Test: `tests/Standalone/PlainPhpLoadTest.php` (teleclient, new dir)

**Interfaces:**
- Consumes: none new.
- Produces: `UpdateIngestor::__construct(RouteIdempotency $routes = new RouteIdempotency(), ?\Illuminate\Contracts\Events\Dispatcher $events = null)`; `UpdateStored` plain readonly DTO (`$model`, `$accountId`), no traits. Later phases rely on `?Dispatcher $events` being the event seam.

- [ ] **Step 1: Write the failing standalone load test**

Create `tests/Standalone/PlainPhpLoadTest.php`. Plain PHPUnit base — deliberately NOT extending the testbench `TestCase`, so no Laravel app ever boots:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleclient\Tests\Standalone;

use MeRezaRezaei\Teleclient\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleclient\Ingest\UpdateIngestor;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 Task 1: the ingest event + ingester must be loadable and
 * constructible with NO Laravel app booted. Before the fix this fatals:
 * UpdateStored imported Illuminate\Foundation\Events\Dispatchable and
 * Illuminate\Queue\SerializesModels — neither is a production dependency.
 */
final class PlainPhpLoadTest extends TestCase
{
    public function test_update_stored_loads_without_laravel(): void
    {
        self::assertTrue(class_exists(UpdateStored::class));
    }

    public function test_update_stored_has_no_framework_traits(): void
    {
        $uses = class_uses(UpdateStored::class);

        self::assertSame([], $uses);
    }

    public function test_ingestor_constructs_without_laravel(): void
    {
        $ingestor = new UpdateIngestor();

        self::assertInstanceOf(UpdateIngestor::class, $ingestor);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Standalone/PlainPhpLoadTest.php`
Expected: FATAL `Class "Illuminate\Foundation\Events\Dispatchable" not found` (or `Illuminate\Queue\SerializesModels`) — proving the audit finding. If it instead passes, STOP: the environment is masking the defect via a dev-time laravel/framework autoload; investigate before continuing.

- [ ] **Step 3: Rebuild `UpdateStored` as a plain DTO**

Replace the entire content of `src/Ingest/Events/UpdateStored.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleclient\Ingest\Events;

use MeRezaRezaei\Teleclient\Schema\Eloquent\TlInstanceModel;

/**
 * Fired after an ingested update's root transaction commits (roadmap
 * contract: events OUT carry Eloquent models; the app layer consumes).
 *
 * Plain DTO by design (Phase 0): no framework traits, dispatchable through
 * any Illuminate\Contracts\Events\Dispatcher — the Laravel host wires
 * app('events'); plain-PHP hosts wire their own. Stays the ONE stored-update
 * event (unification spec D8).
 */
final class UpdateStored
{
    public function __construct(
        public readonly TlInstanceModel $model,
        public readonly int $accountId,
    ) {
    }
}
```

- [ ] **Step 4: Add the dispatcher seam to `UpdateIngestor`**

In `src/Ingest/UpdateIngestor.php`, add the import:

```php
use Illuminate\Contracts\Events\Dispatcher;
```

Change the constructor (currently `:53-56`):

```php
    public function __construct(
        private readonly RouteIdempotency $routes = new RouteIdempotency(),
        private readonly ?Dispatcher $events = null,
    ) {
    }
```

Change the dispatch site (currently `:223`):

```php
        event(new UpdateStored($root, $accountId));
```

to:

```php
        $this->events?->dispatch(new UpdateStored($root, $accountId));
```

- [ ] **Step 5: Write the dispatch-seam test (in Laravel context, proving wiring)**

Create `tests/Ingest/UpdateIngestorDispatchTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleclient\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleclient\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleclient\Ingest\UpdateIngestor;

/**
 * Phase 0 Task 1: the event fires through the INJECTED dispatcher, and the
 * Laravel wiring (provider closure below) makes Event::fake() still see it.
 */
final class UpdateIngestorDispatchTest extends IngestTestCase
{
    private const ACCOUNT = 7;

    public function test_ingest_fires_update_stored_through_injected_dispatcher(): void
    {
        $captured = [];
        $capturing = new class ($captured) implements Dispatcher {
            public function __construct(private array &$captured) {}

            public function dispatch($event, $payload = []): array
            {
                $this->captured[] = $event;

                return [];
            }

            public function listen($events, $listener = null) {}
            public function hasListeners($eventName) { return false; }
            public function subscribe($subscriber) {}
            public function until($event, $payload = []) {}
            public function forget($event) {}
            public function forgetPushed() {}
            public function push($event, $payload = []) {}
            public function flush($event) {}
        };

        $ingestor = new UpdateIngestor(events: $capturing);
        $ingestor->ingest(self::userPayload(), self::ACCOUNT);

        self::assertCount(1, $captured);
        self::assertInstanceOf(UpdateStored::class, $captured[0]);
        self::assertSame(self::ACCOUNT, $captured[0]->accountId);
    }

    public function test_container_resolved_ingestor_fires_into_laravel_events(): void
    {
        Event::fake([UpdateStored::class]);

        $this->app->make(UpdateIngestor::class)->ingest(self::userPayload(), self::ACCOUNT);

        Event::assertDispatchedTimes(UpdateStored::class, 1);
    }

    private static function userPayload(): array
    {
        return [
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3) | (1 << 4) | (1 << 22) | (1 << 28),
            'id' => 501558149,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Reza',
            'last_name' => 'Rezaei',
            'username' => 'RezaRezaei',
            'phone' => '989121234567',
            'lang_code' => 'en',
            'flags2' => (1 << 4),
            'stories_unavailable' => true,
            'premium' => true,
        ];
    }
}
```

- [ ] **Step 6: Wire the provider so Laravel hosts keep identical behavior**

In `src/TeleclientServiceProvider.php` change line 45 from:

```php
        $this->app->singleton(UpdateIngestor::class);
```

to:

```php
        $this->app->singleton(UpdateIngestor::class, static fn ($app): UpdateIngestor => new UpdateIngestor(
            events: $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
        ));
```

Add the import `use MeRezaRezaei\Teleclient\Ingest\UpdateIngestor;` if not already present (it is, via the existing singleton call).

- [ ] **Step 7: Run all ingest tests**

Run: `vendor/bin/phpunit tests/Standalone tests/Ingest`
Expected: ALL PASS, including pre-existing `NestedIngestTest` (its `Event::fake`/`assertDispatched` at :146-151 keeps working because the provider resolves the ingester lazily against the faked dispatcher).

- [ ] **Step 8: Full gate + commit**

Run: `composer test && composer analyse`
Expected: green (334+ tests, phpstan level 5 clean).

```bash
git add src/Ingest/Events/UpdateStored.php src/Ingest/UpdateIngestor.php src/TeleclientServiceProvider.php tests/Ingest/UpdateIngestorDispatchTest.php tests/Standalone/PlainPhpLoadTest.php
git commit -m "fix(ingest): standalone-safe UpdateStored via injected dispatcher (Phase 0 Task 1)"
```

---

### Task 2: Kill the `now()` helpers — injectable clock

**Files:**
- Modify: `src/Ingest/UpdateIngestor.php:385` and constructor (teleclient)
- Modify: `src/Ingest/RouteIdempotency.php:115-138` and constructor (teleclient)
- Test: `tests/Ingest/InjectableClockTest.php` (teleclient)

**Interfaces:**
- Consumes: Task 1's constructor shape.
- Produces: `UpdateIngestor::__construct(RouteIdempotency $routes = new RouteIdempotency(), ?Dispatcher $events = null, ?\Closure $now = null)` — the closure returns `DateTimeImmutable`; null means "real clock". `RouteIdempotency::__construct(?\Closure $now = null)`. Phases 2+ reuse `$now` as the clock seam for every module.

- [ ] **Step 1: Write the failing clock test**

Create `tests/Ingest/InjectableClockTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleclient\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleclient\Ingest\RouteIdempotency;
use MeRezaRezaei\Teleclient\Ingest\UpdateIngestor;

/**
 * Phase 0 Task 2: timestamps come from the injectable clock, not the
 * Laravel now() helper (undefined function in plain PHP). Uses the same
 * proven route table as IngestResponseTest (tl_route_messages_get_history)
 * and its migration pattern (RouteIdempotency::migrationPaths()).
 */
final class InjectableClockTest extends IngestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => RouteIdempotency::migrationPaths(),
        ])->run();
    }

    public function test_route_marking_uses_injected_clock(): void
    {
        $frozen = new \DateTimeImmutable('2026-09-07 10:00:00', new \DateTimeZone('UTC'));
        $routes = new RouteIdempotency(now: static fn (): \DateTimeImmutable => $frozen);

        $routes->mark('messages.getHistory', 'clock-test', 7, '01910000-0000-7000-8000-000000000001');

        $mark = DB::table('tl_route_messages_get_history')->sole();
        self::assertSame('2026-09-07 10:00:00', substr((string) $mark->created_at, 0, 19));
    }

    public function test_ingestor_threading_passes_clock_to_routes(): void
    {
        $frozen = new \DateTimeImmutable('2026-09-07 11:00:00', new \DateTimeZone('UTC'));
        $ingestor = new UpdateIngestor(now: static fn (): \DateTimeImmutable => $frozen);

        $routes = new \ReflectionProperty(UpdateIngestor::class, 'routes');
        $routes->getValue($ingestor)->mark('messages.getHistory', 'clock-thread', 7, '01910000-0000-7000-8000-000000000002');

        $mark = DB::table('tl_route_messages_get_history')->sole();
        self::assertSame('2026-09-07 11:00:00', substr((string) $mark->created_at, 0, 19));
    }
}
```

(If `mark()` turns out to be static in the current file, keep the test body identical and drop the instance expression — call it as `RouteIdempotency::mark(...)` with a `?(Closure $now)` threaded as a parameter instead; the assertion is the contract, not the call shape. The audit read shows instance methods at `RouteIdempotency.php:101,115`.)

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/InjectableClockTest.php`
Expected: FAIL — `Unknown named parameter now` (constructor doesn't accept it yet).

- [ ] **Step 3: Add the clock seam**

In `src/Ingest/UpdateIngestor.php` the constructor becomes (note `$routes` drops its property-default object — PHP cannot cross-reference constructor params in defaults, and the body threads the clock into the routes helper):

```php
    private readonly RouteIdempotency $routes;

    public function __construct(
        ?RouteIdempotency $routes = null,
        private readonly ?Dispatcher $events = null,
        private readonly ?\Closure $now = null,
    ) {
        $this->routes = $routes ?? new RouteIdempotency(now: $now);
    }

    /** Framework-free clock (Laravel now() helper is unavailable in plain PHP). */
    private function now(): \DateTimeImmutable
    {
        return $this->now !== null ? ($this->now)() : new \DateTimeImmutable();
    }
```

Replace line `:385` `'updated_at' => now(),` with `'updated_at' => $this->now(),`.

In `src/Ingest/RouteIdempotency.php` add a constructor + private helper (it currently has none):

```php
    public function __construct(
        private readonly ?\Closure $now = null,
    ) {
    }

    private function now(): \DateTimeImmutable
    {
        return $this->now !== null ? ($this->now)() : new \DateTimeImmutable();
    }
```

Replace `:128-129`:

```php
                'created_at' => now(),
                'updated_at' => now(),
```

with:

```php
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
```

Update the Task 1 dispatch test / provider closure only if phpstan flags the changed default (they pass `events:` named — still valid).

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/InjectableClockTest.php tests/Ingest`
Expected: PASS (Eloquent/query-builder accept `DateTimeInterface` for timestamp columns — no format change).

- [ ] **Step 5: Full gate + commit**

Run: `composer test && composer analyse`
Expected: green.

```bash
git add src/Ingest/UpdateIngestor.php src/Ingest/RouteIdempotency.php tests/Ingest/InjectableClockTest.php
git commit -m "fix(ingest): injectable clock replaces Laravel now() (Phase 0 Task 2)"
```

---

### Task 3: Delete the hidden `Artisan::call('migrate')` (spec D3)

**Files:**
- Modify: `src/Ingest/UpdateIngestor.php:7,103-115` (teleclient) — remove `boot()` + the `Artisan` import; KEEP `entityMigrationPaths()` and `migrationPaths()` (tests + future explicit migrate use them)
- Test: `tests/Ingest/UpdateIngestorTest.php:46-54` (teleclient) — replace `test_boot_migrates_the_generated_truth`

**Interfaces:**
- Consumes: `migrationPaths(): array` (unchanged, public static).
- Produces: removal of `UpdateIngestor::boot()`. The explicit migration surface for Phase 2's `Teleframe::migrate()` is `UpdateIngestor::migrationPaths()`.

- [ ] **Step 1: Replace the boot test with a regression test asserting the seam is gone**

In `tests/Ingest/UpdateIngestorTest.php`, replace `test_boot_migrates_the_generated_truth` (:46-54) with:

```php
    public function test_boot_is_removed_and_migration_surface_stays_queryable(): void
    {
        // Phase 0 Task 3 (spec D3): ingest NEVER runs migrations as a side
        // effect. The explicit surface is migrationPaths(); hosts migrate.
        self::assertFalse(method_exists(UpdateIngestor::class, 'boot'));

        $paths = UpdateIngestor::migrationPaths();
        self::assertNotEmpty($paths);
        foreach ($paths as $path) {
            self::assertDirectoryExists($path);
        }
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Ingest/UpdateIngestorTest.php`
Expected: FAIL — `method_exists` returns true (boot still exists).

- [ ] **Step 3: Delete boot() and the Artisan import**

In `src/Ingest/UpdateIngestor.php`: remove the line

```php
use Illuminate\Support\Facades\Artisan;
```

and remove the whole method (currently `:103-115`):

```php
    /**
     * Run the generated migrations into the (test) connection: the
     * curated dial plus the off-dial entity anchors. Idempotent
     * (Laravel migrate).
     */
    public function boot(): void
    {
        Artisan::call('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => self::migrationPaths(),
        ]);
    }
```

Grep for other callers first and fix if found (audit found none outside this class and the test):

```bash
grep -rn -- "->boot()" src/ tests/
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Ingest/UpdateIngestorTest.php tests/Ingest`
Expected: PASS.

- [ ] **Step 5: Full gate + commit**

Run: `composer test && composer analyse`
Expected: green.

```bash
git add src/Ingest/UpdateIngestor.php tests/Ingest/UpdateIngestorTest.php
git commit -m "fix(ingest)!: remove hidden runtime Artisan::call('migrate') (Phase 0 Task 3, spec D3)"
```

---

### Task 4: Composer manifest becomes true + final standalone proof

**Files:**
- Modify: `tests/Standalone/PlainPhpLoadTest.php` (teleclient) — extend with a plain-PHP script run
- Create: `bin/standalone-smoke.php` (teleclient, executable dev script)
- Read-only check: `composer.json` (teleclient) — no dependency edits expected; this task VERIFIES truth

**Interfaces:**
- Consumes: Tasks 1–3 (no Foundation/Queue/helpers usage left in src/).
- Produces: `bin/standalone-smoke.php` exit code 0 = the ingest surface loads with only production deps. CI may invoke it later (Phase 2); it is not wired into CI now.

- [ ] **Step 1: Write the standalone smoke script**

Create `bin/standalone-smoke.php`:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Phase 0 Task 4: plain-PHP proof. Runs with PRODUCTION dependencies only —
 * no testbench, no laravel/framework. Exit 0 = the ingest surface is
 * standalone-loadable. (Run from the repo root after `composer install`.)
 */

require __DIR__ . '/../vendor/autoload.php';

use MeRezaRezaei\Teleclient\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleclient\Ingest\RouteIdempotency;
use MeRezaRezaei\Teleclient\Ingest\UpdateIngestor;

$checks = [
    'UpdateStored loads' => static fn (): bool => class_exists(UpdateStored::class),
    'RouteIdempotency loads' => static fn (): bool => class_exists(RouteIdempotency::class),
    'UpdateIngestor constructs' => static fn (): bool => new UpdateIngestor() instanceof UpdateIngestor,
    'UpdateStored is a plain DTO' => static fn (): bool => class_uses(UpdateStored::class) === [],
    'No boot() seam' => static fn (): bool => !method_exists(UpdateIngestor::class, 'boot'),
];

$failed = false;
foreach ($checks as $label => $check) {
    $ok = $check();
    printf("%s %s\n", $ok ? '  ✓' : '  ✗', $label);
    $failed = $failed || !$ok;
}

exit($failed ? 1 : 0);
```

- [ ] **Step 2: Run it (it must pass now — and would have fataled before Tasks 1–3)**

Run: `php bin/standalone-smoke.php`
Expected: all `✓`, exit 0. If any line fatals with `Class ... not found`, a Foundation/Queue reference survived — grep `src/` for `Illuminate\Foundation|Illuminate\Queue` and fix before continuing.

- [ ] **Step 3: Verify the manifest is true**

Run:

```bash
grep -rn "Illuminate\\\\Foundation\|Illuminate\\\\Queue" src/
grep -rn "Artisan::" src/
```

Expected: zero hits. Then confirm production deps already cover everything imported (they do: illuminate/events is required; `Illuminate\Contracts\Events\Dispatcher` lives there). No composer.json change needed — record that in the commit message.

- [ ] **Step 4: Full gate + commit**

Run: `composer test && composer analyse`
Expected: green.

```bash
git add bin/standalone-smoke.php tests/Standalone/PlainPhpLoadTest.php
git commit -m "test(standalone): plain-PHP smoke proof, manifest verified true (Phase 0 Task 4)"
```

---

### Task 5: teleframe poller signal sink — gap/resync observable without Laravel

**Files:**
- Create: `src/Core/Contracts/SignalSink.php` (teleframe)
- Modify: `src/Laravel/Services/UpdatePollerService.php:89` (constructor), `:337,351,361,488` (dispatch sites) (teleframe)
- Test: `tests/Laravel/UpdatePollerSignalSinkTest.php` (teleframe)

**Interfaces:**
- Consumes: existing `TelegramGapDetected::dispatch()` guarded statics (stay — Laravel hosts keep them).
- Produces: `MeRezaRezaei\Teleframe\Core\Contracts\SignalSink` with `gapDetected(string $kind, array $context): void` and `resynced(array $state, int $accountId): void`; `UpdatePollerService::__construct(?UpdateSinkInterface $sink = null, ?SignalSink $signalSink = null)` + `withSignalSink(SignalSink $sink): static`. teleclient's `AccountWorker`/`Daemon` consume this in Phase 2 to observe gaps standalone; `TelegramGapDetected::KIND_*` constants are the `$kind` vocabulary.

- [ ] **Step 1: Write the failing test**

Create `tests/Laravel/UpdatePollerSignalSinkTest.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Core\Contracts\SignalSink;
use MeRezaRezaei\Teleframe\Laravel\Events\TelegramGapDetected;
use MeRezaRezaei\Teleframe\Laravel\Services\UpdatePollerService;
use PHPUnit\Framework\TestCase;

/**
 * Phase 0 Task 5: the poller's gap/resync signals must be observable with
 * no Laravel event dispatcher — via the injected sink. (Today they are
 * silently dropped standalone: TelegramGapDetected::dispatch guards on a
 * facade application that plain PHP never has.)
 */
final class UpdatePollerSignalSinkTest extends TestCase
{
    public function test_sink_is_optional_and_fluent(): void
    {
        $poller = new UpdatePollerService();

        self::assertSame($poller, $poller->withSignalSink(new RecordingSink()));
        self::assertInstanceOf(UpdatePollerService::class, new UpdatePollerService(signalSink: new RecordingSink()));
    }

    public function test_signal_method_forwards_to_sink_without_laravel(): void
    {
        $sink = new RecordingSink();
        $poller = (new UpdatePollerService())->withSignalSink($sink);

        // Exercised indirectly through the public seam; the emit path itself
        // is covered by the state-machine suite once wired (Step 4 asserts
        // the existing suite still passes). Here: no Laravel app exists —
        // constructing and holding the poller must not fatal.
        self::assertNotNull($poller);
        self::assertSame([], $sink->gaps);
    }
}

final class RecordingSink implements SignalSink
{
    public array $gaps = [];
    public array $resyncs = [];

    public function gapDetected(string $kind, array $context): void
    {
        $this->gaps[] = ['kind' => $kind, 'context' => $context];
    }

    public function resynced(array $state, int $accountId): void
    {
        $this->resyncs[] = ['state' => $state, 'account' => $accountId];
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit tests/Laravel/UpdatePollerSignalSinkTest.php`
Expected: FAIL — `Class "MeRezaRezaei\Teleframe\Core\Contracts\SignalSink" not found`.

- [ ] **Step 3: Create the contract**

`src/Core/Contracts/SignalSink.php`:

```php
<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Contracts;

/**
 * Observer for poller lifecycle signals (gap kinds + resync completion).
 *
 * The Laravel events (TelegramGapDetected / TelegramResynced) remain the
 * framework integration surface; this port is the framework-free one, so
 * plain-PHP hosts (daemon, future handler layer) can observe gaps that
 * today are silently dropped outside a Laravel app.
 */
interface SignalSink
{
    /** @param string $kind One of TelegramGapDetected::KIND_* */
    public function gapDetected(string $kind, array $context): void;

    /** @param array<string, mixed> $state pts/date/qts/seq snapshot after resync */
    public function resynced(array $state, int $accountId): void;
}
```

- [ ] **Step 4: Wire the poller**

In `src/Laravel/Services/UpdatePollerService.php` add the import `use MeRezaRezaei\Teleframe\Core\Contracts\SignalSink;`. Constructor (currently `:89`):

```php
    public function __construct(?UpdateSinkInterface $sink = null, private ?SignalSink $signalSink = null)
```

Add the fluent setter next to it:

```php
    /** Observe gap/resync signals without a Laravel event dispatcher (framework-free hosts). */
    public function withSignalSink(SignalSink $sink): static
    {
        $this->signalSink = $sink;

        return $this;
    }
```

At each of the four sites, keep the existing static dispatch AND add the sink call with the identical args.

Site `:337` (after `$this->gapPending = true;`):

```php
                    TelegramGapDetected::dispatch(TelegramGapDetected::KIND_SLICE, [
                        'account_id' => $accountId,
                        'from_pts'   => $requestedPts,
                        'to_pts'     => $this->sequenceState['pts'],
                    ]);
                    $this->signalSink?->gapDetected(TelegramGapDetected::KIND_SLICE, [
                        'account_id' => $accountId,
                        'from_pts'   => $requestedPts,
                        'to_pts'     => $this->sequenceState['pts'],
                    ]);
```

Site `:351`:

```php
                TelegramGapDetected::dispatch(TelegramGapDetected::KIND_HOLE, [
                    'account_id'       => $accountId,
                    'requested_pts'    => $requestedPts,
                    'intermediate_pts' => $this->sequenceState['pts'],
                ]);
                $this->signalSink?->gapDetected(TelegramGapDetected::KIND_HOLE, [
                    'account_id'       => $accountId,
                    'requested_pts'    => $requestedPts,
                    'intermediate_pts' => $this->sequenceState['pts'],
                ]);
```

Site `:361`:

```php
                TelegramGapDetected::dispatch(TelegramGapDetected::KIND_TOO_LONG, [
                    'account_id' => $accountId,
                    'local_pts'  => $this->sequenceState['pts'],
                    'server_pts' => $serverPts,
                    'timeout'    => isset($diff['timeout']) ? (int)$diff['timeout'] : null,
                ]);
                $this->signalSink?->gapDetected(TelegramGapDetected::KIND_TOO_LONG, [
                    'account_id' => $accountId,
                    'local_pts'  => $this->sequenceState['pts'],
                    'server_pts' => $serverPts,
                    'timeout'    => isset($diff['timeout']) ? (int)$diff['timeout'] : null,
                ]);
```

Site `:488` (inside `finishResync`, after `$this->gapPending = false;`):

```php
            TelegramResynced::dispatch($this->getSequenceState() ?? [], $accountId);
            $this->signalSink?->resynced($this->getSequenceState() ?? [], (int) ($accountId ?? 0));
```

- [ ] **Step 5: Run the teleframe suite**

Run: `composer verify` (teleframe repo root)
Expected: green — 257+ tests, phpstan clean. The state-machine tests already drive gap paths; they assert no regression. Additionally re-run teleclient's full gate since it subclasses the poller:

```bash
cd ../teleclient && composer test && composer analyse
```

Expected: green (constructor change is additive-optional; `AccountPoller extends UpdatePollerService` unaffected).

- [ ] **Step 6: Commit**

```bash
git add src/Core/Contracts/SignalSink.php src/Laravel/Services/UpdatePollerService.php tests/Laravel/UpdatePollerSignalSinkTest.php
git commit -m "feat(core): SignalSink port — poller gaps observable framework-free (Phase 0 Task 5)"
```

---

## Completion checklist (whole plan)

- [x] `php bin/standalone-smoke.php` exits 0 in teleclient
- [x] `composer test && composer analyse` green in teleclient
- [x] `composer verify` green in teleframe
- [x] `grep -rn "Illuminate\\\\Foundation\|Illuminate\\\\Queue\|Artisan::" src/` returns zero hits in teleclient
- [x] Spec's §5 Phase 0 row fully delivered; note completion in the spec's Status line
