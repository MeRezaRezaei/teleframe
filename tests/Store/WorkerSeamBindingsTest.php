<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use Closure;
use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\MemoryBackupRunIndex;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\CheckNoShowActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\ContainerResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\LaravelWorkerBindings;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\RunAppCallActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreBackupDumpActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StorePruneActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\StoreSyncActivity;
use MeRezaRezaei\Teleframe\Store\Schedules\Worker\TemporalWorker;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * THE ADAPTER'S OWN CONTRACT — the half a tick test cannot see.
 *
 * `tests/Store/WorkerTickReachabilityTest.php` already walks the whole path:
 * host container → `LaravelWorkerBindings` → `new TemporalWorker(…)` → the real
 * `Temporal\Worker` from the real `WorkerFactory` → the SDK's own handler, and
 * proves a tick taken from the SDK's registry under a real activity-type name
 * reaches the real service the CLI reaches. That is the right file for that
 * claim and the wrong file for this one, twice over. It needs the SDK installed
 * and a worker registered before it can say a word, and — more to the point —
 * it binds ALL FIVE seams and only ever asserts that a tick gets THROUGH. So
 * every way this adapter could rot leaves it green: bind the wrong seam, bind
 * a seam self-mapping when the real host binds something else, bind a seam whose
 * factory returns `null`, resolve a seam at worker boot. Each of those fails an
 * operator, not a test in that file.
 *
 * So this file takes the ADAPTER apart, with plain PHPUnit over a bare
 * `Illuminate\Container\Container` — no booted application, no Temporal class
 * in sight, not one activity instance constructed. It pins four properties a
 * host is silently trusting the moment it hands this object to
 * `new TemporalWorker(…)`:
 *
 *  1. THE SEAM SET. Five, not six, because prune and sync declare the SAME
 *     seam string: `StorePruneActivity::SERVICE_SEAM` and
 *     `StoreSyncActivity::SERVICE_SEAM` are both
 *     `TstoreCommand::SERVICE_KEY` (TstoreCommand.php:58). A sixth entry would
 *     be a duplicated literal, and a duplicated literal is precisely the bug
 *     this package keeps killing: two sides agreeing on a name no provider
 *     binds, so the tick refuses on a seam that LOOKS wired.
 *  2. THE ONE DERIVATION. Four seams resolve from themselves; the target seam
 *     resolves from `DatabaseTarget::class` (the deepest assertion here, and the
 *     one a "tidy it up into a self-map" refactor would silently undo).
 *  3. THE HONESTY HINGE. An unbound seam is left OUT of the resolver, so the
 *     worker's own named refusal fires instead of a host
 *     `BindingResolutionException` about a binding the operator never wrote.
 *  4. THE LAZINESS. Nothing is built until an activity asks, because a worker
 *     is a long-running process that may serve a task queue nothing ever ticks.
 *
 * WHY THE CONTAINERS HERE ARE BARE AND UNPOPULATED. `Illuminate\Container\Container`
 * is the concrete, application-less container, and it is enough: this adapter
 * asks exactly one question of a host — `bound()` — and then calls `make()` on
 * a key that answer approved. Bootstrapping an application to test that would
 * test the application. Note also that the objects behind the seams below are
 * real package classes but deliberately ARBITRARY ones (a hot cache, a run
 * index, a target): the sibling file owns the claim "the right service is
 * behind the right seam", and duplicating it here would be a second, weaker
 * copy of a claim that already has a test. What is under test here is the
 * WIRING, and the wiring does not care what object arrives.
 *
 * WHAT IS NOT PROVEN HERE, in the same words the adapter uses in its own
 * operator-facing output:
 *
 *  - This repo has NO live Temporal server. Binding a seam says a tick REACHES
 *    a service method. It is not evidence that a server dispatches the tick.
 *  - This repo has NO live Telegram run. Nothing here shows a message was sent.
 *
 * The honest claim is the narrow one: the adapter asks the container the right
 * question, joins the right keys, refuses in the worker's own voice, and builds
 * nothing until a tick asks it to.
 */
final class WorkerSeamBindingsTest extends TestCase
{
    // ── the seam set, read back off the activities that declare it ───────

    #[Test]
    public function it_knows_the_five_seams_the_five_registered_activities_need(): void
    {
        // THE MIRROR, not a second opinion. `SEAM_SOURCES` is built from the
        // activities' own constants, so these assertions are not re-deriving the
        // answer — they are the check that the constants and the map still agree,
        // which is the one thing building a map from constants cannot prove.
        // The literals are pinned here on purpose: a seam renamed in an activity
        // AND in this map would still leave every other test green while the
        // provider bound the old string, and a scheduled dump would then refuse
        // on a seam that reads as wired in the source.
        self::assertSame([
            'teleclient.app.service',
            'teleclient.notify.watchdog',
            'teleclient.store.backup.service',
            'teleclient.store.backup.target',
            'teleclient.tstore.service',
        ], LaravelWorkerBindings::seamNames(), 'the five seams, sorted, byte-identical to the wire-visible names');

        self::assertCount(5, LaravelWorkerBindings::seamNames(),
            'FIVE, not six: prune and sync declare the same seam string, so the map has five entries');

        // And each of those five IS a constant somebody actually declares.
        foreach ($this->declaredSeams() as $seam) {
            self::assertContains($seam, LaravelWorkerBindings::seamNames(),
                $seam.' is declared by an activity the worker serves, so the worker must be able to bind it');
        }

        // …and the set closes in the other direction: every seam the adapter
        // advertises is a seam a served activity declares. An extra entry would
        // be a name no activity will ever ask for — a binding nothing reaches,
        // which is the same false-completeness as a missing one.
        self::assertSame(
            $this->sorted(array_unique($this->declaredSeams())),
            LaravelWorkerBindings::seamNames(),
            'the map and the activities declare ONE set of seams, not two overlapping ones',
        );

        // THE REASON IT IS FIVE. Prune and sync are separate activity types on
        // separate cadences, and both reach the same store service, so the seam
        // is shared on purpose rather than duplicated per activity.
        self::assertSame(StorePruneActivity::SERVICE_SEAM, StoreSyncActivity::SERVICE_SEAM,
            'prune and sync share one seam — this is why the count is five and not six');
        self::assertSame('teleclient.tstore.service', StorePruneActivity::SERVICE_SEAM);
    }

    // ── THE TRAP: the target seam's source is NOT its own name ───────────

    #[Test]
    public function the_target_seam_resolves_from_the_class_keyed_database_target_and_not_from_its_own_name(): void
    {
        // THE MOST VALUABLE TEST IN THIS FILE, and the one a tidy-up would
        // delete. `teleclient.store.backup.target` is bound by NO provider in
        // this repository: it is `StoreBackupDumpActivity`'s own name for "the
        // target this dump runs against", and `DatabaseBackupService::backup()`
        // takes that target as an explicit argument, so the name has to exist
        // for the activity to resolve.
        //
        // The key that IS bound is `DatabaseTarget::class`:
        // StoreBackupServiceProvider.php:558 binds it from the
        // `teleframe.store.backup.database.*` config, and TstoreCommand.php:488
        // resolves the manual dump's target with exactly
        // `$this->getLaravel()->make(DatabaseTarget::class)`. So resolving the
        // seam FROM that class is not a guess about what a target should be — it
        // is the same resolution `php artisan teleframe:tstore backup` performs,
        // and it is the only derivation that keeps a SCHEDULED dump and a
        // MANUAL dump pointed at the same database.
        //
        // The failure mode it guards is the worst kind: a map "simplified" to
        // self-mapping would still bind five seams and every reachability test
        // would stay green, because that test's container binds the seam names
        // itself. In a real host the target seam would then be UNBOUND and every
        // nightly dump would die — or, worse, a host that bound the name by hand
        // to something else would quietly back up a DIFFERENT database than the
        // CLI, which is how a green schedule destroys the wrong thing. Hence
        // BOTH directions, asserted separately. One direction proves nothing.
        self::assertSame(
            DatabaseTarget::class,
            LaravelWorkerBindings::SEAM_SOURCES[StoreBackupDumpActivity::TARGET_SEAM] ?? null,
            'the target seam resolves FROM the class-keyed DatabaseTarget',
        );
        self::assertNotSame(
            StoreBackupDumpActivity::TARGET_SEAM,
            LaravelWorkerBindings::SEAM_SOURCES[StoreBackupDumpActivity::TARGET_SEAM] ?? null,
            'and emphatically NOT from its own name — no provider binds that string',
        );

        // (b) The real host shape: the class key is bound, the seam name is not.
        $target = new DatabaseTarget(driver: 'pgsql', database: 'teleframe-app');
        $realHost = new Container;
        $realHost->bind(DatabaseTarget::class, static fn (): DatabaseTarget => $target);

        self::assertFalse($realHost->bound(StoreBackupDumpActivity::TARGET_SEAM),
            'precondition: a real host binds the class key and NOT the seam name');
        self::assertContains(
            StoreBackupDumpActivity::TARGET_SEAM,
            (new LaravelWorkerBindings($realHost))->boundSeams(),
            '…so the target seam is still BOUND, because the adapter reads the key that exists',
        );
        self::assertSame(
            $target,
            (new LaravelWorkerBindings($realHost))->resolver()->resolve(StoreBackupDumpActivity::TARGET_SEAM),
            'and it resolves to the very object the manual dump gets from make(DatabaseTarget::class) — not to a target invented here',
        );

        // (c) The converse, and the reason (b) is not enough on its own. A host
        // that binds the SEAM NAME and not the class key — the shape a "fix"
        // would produce — must leave the target seam unbound. If this half ever
        // fails, a scheduled dump has been re-pointed away from the
        // config-derived target.
        $mirrored = new Container;
        $mirrored->bind(StoreBackupDumpActivity::TARGET_SEAM, static fn (): object => new \stdClass);

        $bindings = new LaravelWorkerBindings($mirrored);

        self::assertNotContains(
            StoreBackupDumpActivity::TARGET_SEAM,
            $bindings->boundSeams(),
            'a binding on the seam NAME must not make the target seam bound — the map reads DatabaseTarget::class',
        );
        self::assertFalse($bindings->resolver()->has(StoreBackupDumpActivity::TARGET_SEAM),
            'so the worker keeps its own named refusal rather than resolving something a provider never bound');

        // The four self-mapping seams are untouched by all of that: a seam this
        // package binds verbatim is bound off its own name, which is the sanity
        // for the converse above.
        $verbatim = new Container;
        $verbatim->bind(CheckNoShowActivity::WATCHDOG_SEAM, static fn (): object => new \stdClass);
        self::assertNotEmpty(
            (new LaravelWorkerBindings($verbatim))->boundSeams(),
            'a seam this package binds verbatim is bound off its own name — so the target seam\'s absence above is the derivation, not a broken map',
        );

        // AND THE TRAP IS DEMONSTRATED, not merely asserted directionally. The
        // constructor's `$seamSources` override is how a self-mapped map is
        // expressed without editing the constant, so the "harmless tidy-up" can
        // be run against a real host here and watched lose the target seam. If
        // this assertion is ever deleted alongside the map change, the two
        // failures are the same event and the guard is gone.
        $selfMapped = new LaravelWorkerBindings($realHost, [
            StoreBackupDumpActivity::TARGET_SEAM => StoreBackupDumpActivity::TARGET_SEAM,
        ]);

        self::assertNotContains(
            StoreBackupDumpActivity::TARGET_SEAM,
            $selfMapped->boundSeams(),
            'PROVEN HERE: a self-mapped target seam loses the config-derived target in a real host, because no '
            .'provider binds that name — the nightly dump would refuse while every reachability test stayed green',
        );
    }

    // ── THE HONESTY HINGE: an unbound seam keeps the WORKER's refusal ────

    #[Test]
    public function an_unbound_seam_is_left_out_of_the_resolver_so_the_workers_own_refusal_fires(): void
    {
        // A container binding THREE of the five: a realistic host — a store
        // install with the app module absent and no notify subscribers. The
        // tempting shortcut is a factory that calls `make()` unconditionally for
        // every seam, which "works" until a tick hits the missing one and the
        // operator is handed a `BindingResolutionException` about a class name
        // they never wrote. Leaving the seam OUT costs nothing and keeps the
        // worker's own message, which is the actionable one.
        $container = new Container;
        $container->bind(StorePruneActivity::SERVICE_SEAM, static fn (): object => new MemoryHotCache(
            ttlSeconds: 600,
            maxBytes: 1024 * 1024,
        ));
        $container->bind(StoreBackupDumpActivity::SERVICE_SEAM, static fn (): object => new MemoryBackupRunIndex);
        $container->bind(DatabaseTarget::class, static fn (): DatabaseTarget => new DatabaseTarget(
            driver: 'pgsql',
            database: 'teleframe-app',
        ));

        $bindings = new LaravelWorkerBindings($container);
        $bound = $bindings->boundSeams();
        $unbound = $bindings->unboundSeams();
        $resolver = $bindings->resolver();

        // The two lists PARTITION the five. Not "mostly": a seam in both lists is
        // a seam whose answer depends on when you asked, and a seam in neither is
        // a seam the worker cannot serve and the operator cannot see.
        self::assertCount(3, $bound);
        self::assertCount(2, $unbound);
        self::assertSame(
            LaravelWorkerBindings::seamNames(),
            $this->sorted([...$bound, ...$unbound]),
            'bound + unbound is exactly the five seams, nothing lost and nothing claimed twice',
        );
        self::assertSame([], array_intersect($bound, $unbound), 'a seam is bound or it is not — never both');

        self::assertSame([
            'teleclient.store.backup.service',
            'teleclient.store.backup.target',
            'teleclient.tstore.service',
        ], $bound, 'the three the container can answer for, the target seam via the class key');
        self::assertSame([
            RunAppCallActivity::RUNTIME_SEAM,
            CheckNoShowActivity::WATCHDOG_SEAM,
        ], $unbound, 'the two it cannot, so they keep the refusing default');

        foreach ($bound as $seam) {
            self::assertTrue($resolver->has($seam), $seam.' is bound, so the resolver must carry a factory for it');
        }

        foreach ($unbound as $seam) {
            self::assertFalse($resolver->has($seam),
                $seam.' must be left OUT of the resolver — an unconditional factory here would launder a '
                .'BindingResolutionException into a tick failure');
        }

        // The message, then. The whole point of leaving the seam out is the WORD
        // the operator reads, so it is asserted rather than assumed.
        foreach ($unbound as $seam) {
            $refused = $this->refusal(static fn () => $resolver->resolve($seam));

            self::assertNotNull($refused, $seam.': an unbound seam must refuse, not return something');
            self::assertStringContainsString($seam, $refused, $seam.': the refusal must name the seam');
            self::assertStringContainsString('nothing is bound to it', $refused,
                $seam.': …and say nothing is bound, not merely fail');
            self::assertStringNotContainsStringIgnoringCase('BindingResolutionException', $refused,
                $seam.': a host container exception would name a binding the operator never wrote');
        }
    }

    // ── laziness: a worker is a long-running process ─────────────────────

    #[Test]
    public function it_resolves_nothing_until_an_activity_asks(): void
    {
        // A boot-time container hit per seam is work done in a process that may
        // serve a task queue nothing ever ticks — and, on a host whose bindings
        // are not ready that early, an exception on a `php artisan list`. So the
        // count is asserted at three points: after construction, after
        // `resolver()`, and after the first `resolve()`.
        $made = 0;
        $target = new DatabaseTarget(driver: 'pgsql', database: 'teleframe-app');

        $container = new Container;
        $container->bind(DatabaseTarget::class, function () use (&$made, $target): DatabaseTarget {
            $made++;

            return $target;
        });

        $bindings = new LaravelWorkerBindings($container);

        self::assertSame(0, $made, 'the constructor stores the container and resolves nothing');

        $resolver = $bindings->resolver();

        self::assertSame(0, $made, 'resolver() may ask bound() — a cheap isset over the bindings array — and nothing else');

        self::assertSame(
            $target,
            $resolver->resolve(StoreBackupDumpActivity::TARGET_SEAM),
            'the first resolve() is what reaches the host',
        );
        self::assertSame(1, $made);

        self::assertSame(
            $target,
            $resolver->resolve(StoreBackupDumpActivity::TARGET_SEAM),
            'and the resolver memoises, so the same activity instance never re-hits the container',
        );
        self::assertSame(1, $made, 'a second resolve() must NOT re-invoke the host factory');
    }

    #[Test]
    public function a_factory_returning_nothing_is_refused_rather_than_treated_as_a_service(): void
    {
        // THIS IS REAL, not hypothetical. `teleclient.notify.watchdog` is
        // registered as an ALIAS (StoreBackupServiceProvider.php:238) whose
        // factory catches Throwable and returns null when there is no durable
        // heartbeat state to watch. So in a real host the seam reads as BOUND
        // while the object is not there, and `bound()` — correctly — answers true.
        // The adapter does not pre-check that, does not add a fallback, and does
        // not invent a substitute watchdog; the null travels out untyped and
        // `ContainerResolver::resolve()` refuses it by name.
        //
        // The alternative is the failure this project has spent a cycle
        // removing: hand a watchdog activity a `null`, let it report "0 alerts",
        // and a notify surface that watches nothing reads as a healthy job for as
        // long as nobody looks at the underlying state.
        $container = new Container;
        $container->bind(CheckNoShowActivity::WATCHDOG_SEAM, static fn (): ?object => null);

        $bindings = new LaravelWorkerBindings($container);
        $resolver = $bindings->resolver();

        self::assertContains(
            CheckNoShowActivity::WATCHDOG_SEAM,
            $bindings->boundSeams(),
            'precondition, and the trap itself: the host has the alias registered, so this seam is advertised as '
            .'BOUND — whether the object behind it exists is the tick\'s business',
        );
        self::assertTrue($resolver->has(CheckNoShowActivity::WATCHDOG_SEAM), 'so a factory is carried for it');

        $refused = $this->refusal(static fn () => $resolver->resolve(CheckNoShowActivity::WATCHDOG_SEAM));

        self::assertNotNull($refused, 'a factory that returns nothing must be REFUSED, not resolved');
        self::assertStringContainsString(CheckNoShowActivity::WATCHDOG_SEAM, $refused, 'by name');
        self::assertStringContainsString('not an object', $refused, '…saying what came back instead');
        self::assertStringContainsString('null', $refused, '…and naming the null, so the operator knows the host binding returned nothing');
        self::assertStringNotContainsStringIgnoringCase('BindingResolutionException', $refused,
            'a null is a refusal, not a container misconfiguration');
    }

    // ── the adapter must be reachable from a plain process ───────────────

    #[Test]
    public function it_constructs_from_a_bare_container_with_no_laravel_application_booted(): void
    {
        // `temporal/sdk` is a require-DEV here, and `bin/teleframe-worker` is a
        // long-running entry point that has to decide things BEFORE anything is
        // dispatched. So this file must be usable with no application, and the
        // seam list must be readable with no container at all — otherwise
        // `--describe` could not print what it is refusing to serve.
        // The demonstration that it needs no instance is the CALL ITSELF: there
        // is no container, no application and no $bindings in scope here, so
        // this line is the property. What it must return is the activities' own
        // seam set, and not an empty list a static method could get away with.
        self::assertSame(
            $this->sorted(array_unique($this->declaredSeams())),
            LaravelWorkerBindings::seamNames(),
            'the seam list is static and needs no instance — an operator can be told what is missing before anything boots',
        );
        self::assertCount(5, LaravelWorkerBindings::seamNames(),
            '…and it is the full list, not an empty one a static method could trivially return');

        $bindings = new LaravelWorkerBindings(new Container);

        self::assertSame([], $bindings->boundSeams());
        self::assertSame(LaravelWorkerBindings::seamNames(), $bindings->unboundSeams());
        self::assertInstanceOf(
            ContainerResolver::class,
            $bindings->resolver(),
            'a bare container yields a resolver — an empty one, which refuses by name, exactly like the unadapted worker',
        );

        // ASKING IS NOT BUILDING, proved with a factory that would explode if it
        // ran. `boundSeams()` and `unboundSeams()` are the questions a boot path
        // asks before there is anything to serve; if answering them constructed a
        // service, a host with an unready binding would fail on `--describe`.
        $invocations = 0;
        $container = new Container;
        $container->bind(StorePruneActivity::SERVICE_SEAM, function () use (&$invocations): object {
            $invocations++;

            throw new RuntimeException('the host factory must not run while the adapter is only being asked about');
        });

        $asked = new LaravelWorkerBindings($container);

        self::assertSame([StorePruneActivity::SERVICE_SEAM], $asked->boundSeams(),
            'the seam is still reported bound — Container::bound() is an isset(), not a build');
        self::assertCount(4, $asked->unboundSeams());

        $resolver = $asked->resolver();

        self::assertSame(0, $invocations, 'construction, boundSeams(), unboundSeams() and resolver() build NOTHING');

        // …and the binding really is live, reached only from inside resolve().
        $refused = $this->refusal(static fn () => $resolver->resolve(StorePruneActivity::SERVICE_SEAM));

        self::assertSame(1, $invocations, 'the factory runs exactly once, on the first resolve()');
        self::assertNotNull($refused, 'a host factory that throws surfaces as the host\'s own failure — the adapter does not swallow it');
        self::assertStringContainsString('must not run while the adapter is only being asked about', $refused);
    }

    // ── what the operator is told ────────────────────────────────────────

    #[Test]
    public function describe_says_what_is_bound_and_what_a_tick_will_refuse_without_ever_claiming_a_tick_will_run(): void
    {
        // `bin/teleframe-worker --describe` is the ONE place an operator learns
        // what this worker will and will not do, so its text is a contract. The
        // properties that matter: it names the count, it names the seams, it
        // says a tick for an unbound seam REFUSES BY NAME, and it does not
        // anywhere claim a tick can run or a message was sent — because this
        // repo has no live Temporal server and no live Telegram run, and the
        // two prior slices in this area were written specifically to stop the
        // disclosure from over-claiming what the code proves.
        $container = new Container;
        $container->bind(StorePruneActivity::SERVICE_SEAM, static fn (): object => new MemoryHotCache(
            ttlSeconds: 600,
            maxBytes: 1024 * 1024,
        ));
        $container->bind(StoreBackupDumpActivity::SERVICE_SEAM, static fn (): object => new MemoryBackupRunIndex);
        $container->bind(DatabaseTarget::class, static fn (): DatabaseTarget => new DatabaseTarget(
            driver: 'pgsql',
            database: 'teleframe-app',
        ));

        $bindings = new LaravelWorkerBindings($container);
        $text = $bindings->describe();
        $bound = $bindings->boundSeams();
        $unbound = $bindings->unboundSeams();

        self::assertCount(3, $bound);
        self::assertCount(2, $unbound);

        // The count, cross-checked against the container rather than hardcoded,
        // so the prose cannot drift from the seams the adapter found.
        self::assertStringContainsString(
            sprintf('%d of %d worker seams bound', count($bound), count(LaravelWorkerBindings::seamNames())),
            $text,
            'it states how many of the five are bound',
        );

        foreach ($bound as $seam) {
            self::assertStringContainsString($seam, $text, 'the bound seams are named: '.$seam);
        }

        self::assertStringContainsString(
            sprintf('NOT bound here (%d of %d)', count($unbound), count(LaravelWorkerBindings::seamNames())),
            $text,
            'and how many are not, so a count that does not add up is visible',
        );
        self::assertStringContainsString('refuses BY NAME', $text,
            'an unbound seam must be described as refusing BY NAME — the outcome, not just the absence');

        foreach ($unbound as $seam) {
            self::assertStringContainsString($seam, $text, 'the not-bound seams are named: '.$seam);
        }

        self::assertStringContainsString(TemporalWorker::ENTRY_POINT, $text,
            'and it names the entry point, because "pass a container" is advice without one');

        // The honest limit, verbatim from the class\'s own wording.
        self::assertStringContainsString('does NOT prove a Temporal server dispatches the tick', $text);
        self::assertStringContainsString('does NOT prove a real message was sent', $text);
        self::assertStringContainsString('live dispatch', $text, 'the sentence names the thing that is untested');

        // …and the absence of any un-negated success claim. Every one of these is
        // a phrase the disclosure must never contain, in either branch. Note that
        // "a message was sent" is deliberately NOT in this list: the honest-limit
        // sentence contains the negated "does NOT prove a real message was sent",
        // and a blunt substring check would flag the very sentence that is doing
        // the work.
        foreach ([
            'a tick can run',
            'a tick will run',
            'the tick will run',
            'a message is sent',
            'a message was sent',
            'messages are sent',
            'the tick succeeds',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $text,
                'the disclosure must never claim "'.$forbidden.'" — binding a seam proves reachability, nothing more');
        }
    }

    #[Test]
    public function an_empty_container_reports_every_activity_type_failing_on_every_tick(): void
    {
        // The zero case, and the one an operator will actually meet first: the
        // container handed to the worker binds none of the five. The previous
        // state of this repo is exactly this — five registered activities, none
        // executable — so the text saying so unmistakably is the whole job.
        $bindings = new LaravelWorkerBindings(new Container);

        self::assertSame([], $bindings->boundSeams());
        self::assertSame(LaravelWorkerBindings::seamNames(), $bindings->unboundSeams(),
            'every seam the adapter knows is unbound in an empty container');

        $text = $bindings->describe();

        self::assertStringContainsString('0 of 5 worker seams bound', $text);
        self::assertStringContainsString('binds NONE of the seams', $text, 'said unmistakably, not implied by a count of zero');
        self::assertStringContainsString('every single tick of every one of them fails', $text,
            'and what that MEANS for a tick, which is the thing the operator has to act on');
        self::assertStringContainsString('nothing is bound to it', $text, 'in the refusal the ticks will hit');

        // THE PROSE MUST NOT DRIFT FROM THE REGISTRY. The count in the sentence
        // is read back out of the text (zero-regex, by explode) and compared with
        // `TemporalWorker::servedActivities()` — so a sixth activity added to the
        // worker without this disclosure noticing fails HERE, not in production.
        self::assertSame(5, count(TemporalWorker::servedActivities()),
            'the worker serves five activity types, which is the number the disclosure is cross-checked against');
        self::assertSame(
            count(TemporalWorker::servedActivities()),
            $this->activityCountIn($text),
            'the activity count the disclosure states must be the registry\'s own count, not a number typed into a string',
        );
        self::assertStringContainsString(
            sprintf('the %d registered activity types need', count(TemporalWorker::servedActivities())),
            $text,
        );

        foreach (LaravelWorkerBindings::seamNames() as $seam) {
            self::assertStringContainsString($seam, $text, 'every refusing seam is named: '.$seam);
        }
    }

    // ── the inputs this file asserts against ────────────────────────────

    /**
     * Every seam constant declared by the five activities the worker serves.
     *
     * Deliberately spelled out class by class rather than reflected: the whole
     * point is that the adapter's map and the activities' own declarations are
     * two independent statements that have to agree, and a reflection walk would
     * make them agree by construction.
     *
     * @return list<string>
     */
    private function declaredSeams(): array
    {
        return [
            StoreBackupDumpActivity::TARGET_SEAM,
            StoreBackupDumpActivity::SERVICE_SEAM,
            CheckNoShowActivity::WATCHDOG_SEAM,
            RunAppCallActivity::RUNTIME_SEAM,
            StorePruneActivity::SERVICE_SEAM,
            StoreSyncActivity::SERVICE_SEAM,
        ];
    }

    /**
     * The activity count `describe()` states, read back OUT of its own text so
     * the assertion is about the disclosure and not about a number retyped here.
     *
     * Zero regex by construction: the sentence ends `… the <n> registered
     * activity types need …`, so the token before that phrase is the number.
     */
    private function activityCountIn(string $text): int
    {
        $before = explode('registered activity types need', $text)[0];
        $tokens = explode(' ', trim($before));

        return (int) end($tokens);
    }

    /**
     * Run a tick-shaped callable and hand back the message it refused with, or
     * null if it did not refuse at all.
     */
    private function refusal(Closure $tick): ?string
    {
        try {
            $tick();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * @param  list<string>  $seams
     * @return list<string>
     */
    private function sorted(array $seams): array
    {
        sort($seams);

        return $seams;
    }
}
