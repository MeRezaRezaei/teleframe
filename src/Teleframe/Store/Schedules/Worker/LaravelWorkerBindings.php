<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Closure;
use Illuminate\Contracts\Container\Container;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;

/**
 * LaravelWorkerBindings — the missing adapter between this package's Laravel
 * providers and `bin/teleframe-worker`.
 *
 * THE DEFECT THIS CLASS EXISTS TO KILL. `bin/teleframe-worker` builds
 * `new TemporalWorker($taskQueue, $pairs)`, and that constructor's third
 * parameter defaults to a fresh `ContainerResolver` — which is
 * `ContainerResolver::refusing()` in everything but name
 * (ContainerResolver.php:63). So a worker booted the documented way got FIVE
 * registered activities and could execute NONE of them: every single tick died
 * with "needs the `<seam>` seam and nothing is bound to it". Four of the five
 * seams ARE bound, by this package's own providers, in any host that installed
 * it — but nothing anywhere in the tree ever HANDED those bindings to the
 * worker. The work was reachable and the wire was not.
 *
 * So the seam NAME is the thing that goes missing, not the service. An activity
 * asks for `teleclient.tstore.service`; the provider binds
 * `teleclient.tstore.service`; and only the object graph that owns the resolver
 * can join those two facts. This class is that join, and nothing else.
 *
 * FIVE SEAMS, NOT SIX. `StorePruneActivity` and `StoreSyncActivity` declare the
 * SAME seam string (both `teleclient.tstore.service`, both
 * `TstoreCommand::SERVICE_KEY` at TstoreCommand.php:58), so the map below has
 * five entries. The map is built from the activity classes' own constants
 * rather than repeated string literals, precisely so a seam renamed in an
 * activity cannot silently drift away from what the worker binds here — a
 * duplicated literal would leave both sides agreeing on a name no provider
 * binds, and the tick would fail on a seam that LOOKS wired.
 *
 * THE ONE DERIVATION, and why it is not an invention. Four of the five seams
 * resolve FROM themselves, because this package binds those names verbatim.
 * The fifth, `StoreBackupDumpActivity::TARGET_SEAM`
 * (`teleclient.store.backup.target`), is bound by NO provider: it is the
 * activity's own name for "the target the dump runs against", and
 * `DatabaseBackupService::backup()` takes that target as an explicit argument,
 * so the seam has to exist as a name the activity can resolve. Its SOURCE is
 * therefore a different key that IS bound:
 * `StoreBackupServiceProvider.php:558` binds `DatabaseTarget::class` from the
 * `teleframe.store.backup.database.*` config, and `TstoreCommand.php:488`
 * resolves the backup target with exactly `$container->make(DatabaseTarget::class)`
 * — the same call `php artisan teleframe:tstore backup` makes. Resolving the
 * target seam FROM `DatabaseTarget::class` is therefore not a guess about what
 * a target should be; it is the same resolution the manual dump performs, so a
 * SCHEDULED dump and a MANUAL dump reach the SAME DatabaseTarget object built
 * from the SAME config. Both facts are cited rather than assumed, because the
 * alternative — handing the activity a target invented here — would let the
 * worker back up a different database than the CLI, which is exactly the class
 * of bug where a green schedule destroys the wrong thing.
 *
 * THE HONESTY HINGE — the one behaviour this class must not get wrong. A
 * factory is bound for a seam ONLY when `$this->container->bound($source)` is
 * true at `resolver()` time. A seam the host has NOT bound is left UNBOUND in
 * the returned resolver, so the tick falls through to
 * `ContainerResolver::resolve()`'s OWN named refusal — byte-for-byte the same
 * actionable message a container-free worker produces today. The tempting
 * shortcut, binding a factory that calls `make()` unconditionally, is worse
 * than doing nothing: it converts the worker's own honest, specific refusal
 * ("nothing is bound to teleclient.app.service, pass a resolver with a factory
 * for it") into a raw host `BindingResolutionException` about some derived
 * class name, and sends an operator to debug a container binding that may not
 * exist at all. Preserving the refusal is the whole point of this slice.
 *
 * A `null` from the host is the same story and gets the same treatment. The
 * watchdog binding at `StoreBackupServiceProvider.php:238` is an alias, and the
 * factory behind it catches Throwable and returns null when there is no
 * durable heartbeat state to watch. `bound()` is therefore true for
 * `teleclient.notify.watchdog` in a real host while the object may not exist.
 * This class does NOT pre-check that, does NOT add a fallback, and does NOT
 * invent a substitute watchdog: the null travels out of the factory untyped
 * and `ContainerResolver::resolve()` refuses it by name, with the right
 * message, for the right reason. Same refusal, better forensics.
 *
 * WHY THE FACTORIES ARE UNTYPED CLOSURES. Each factory carries NO return type
 * declaration, so a `null` (or anything else a host hands back) reaches
 * `ContainerResolver`'s own `is_object()` check instead of dying as a TypeError
 * against a signature this class invented. `ContainerResolver` checks what a
 * factory RETURNED rather than trusting its type — @see its
 * `$factories` docblock — and a return type here would quietly convert that
 * runtime check into a lie.
 *
 * NOTHING DIALES, RESOLVES, OR CONSTRUCTS A SERVICE. The constructor stores
 * the container; it resolves nothing. `resolver()` may call `bound()` — a cheap
 * `isset()` over the bindings array, no service instantiation — and builds
 * closures. `make()` runs on first `resolve()`, inside the activity, on the
 * tick. A worker is a long-running process, so a boot-time container hit per
 * seam would be work done in a process that may serve a task queue nothing ever
 * ticks (@see TemporalWorker's own construction-is-inert note).
 *
 * THE SDK IS ABSENT FROM THIS FILE ENTIRELY. No `Temporal\` class appears in an
 * import, a signature, or a property; the only mention of the word is in the
 * honest-limit paragraph of `describe()`. `temporal/sdk` is require-DEV in this
 * package, so this file loads, constructs, and serves its static methods with the
 * SDK not installed. `Illuminate\Contracts\Container\Container` is safe to type
 * against for the same reason `laravel/framework` is safe: it is a HARD require,
 * and PHP only resolves a parameter type when the call happens — so
 * `new LaravelWorkerBindings(new \Illuminate\Container\Container)` works in a
 * bare process with no booted application, and `seamNames()` works with no
 * container at all.
 *
 * Plain-PHP constructible, no container, zero regular expressions.
 */
final class LaravelWorkerBindings
{
    /**
     * Seam name => the container key that seam resolves FROM. Every key on the
     * right is either the seam itself (this package binds those names verbatim)
     * or a key a provider in this package demonstrably binds.
     *
     * @var array<string, string>
     */
    public const SEAM_SOURCES = [
        StoreBackupDumpActivity::TARGET_SEAM => DatabaseTarget::class,
        StoreBackupDumpActivity::SERVICE_SEAM => StoreBackupDumpActivity::SERVICE_SEAM,
        CheckNoShowActivity::WATCHDOG_SEAM => CheckNoShowActivity::WATCHDOG_SEAM,
        RunAppCallActivity::RUNTIME_SEAM => RunAppCallActivity::RUNTIME_SEAM,
        StorePruneActivity::SERVICE_SEAM => StorePruneActivity::SERVICE_SEAM,
    ];

    /**
     * @param  Container  $container  the host's container; NOTHING is resolved in this constructor
     * @param  null|array<string, string>  $seamSources  seam => container key override (tests);
     *                                                   null = self::SEAM_SOURCES
     */
    public function __construct(
        private readonly Container $container,
        private readonly ?array $seamSources = null,
    ) {}

    /**
     * Every seam this class knows how to bind, sorted.
     *
     * Five, not six: prune and sync share `teleclient.tstore.service`. Uses the
     * class's own constants, so a seam that vanishes from SEAM_SOURCES cannot
     * still be advertised here.
     *
     * @return list<string>
     */
    public static function seamNames(): array
    {
        $seams = array_keys(self::SEAM_SOURCES);
        sort($seams);

        return $seams;
    }

    /**
     * The seams this host's container HAS a binding for, sorted.
     *
     * `Container::bound()` answers true for an alias as well as a concrete
     * binding, which is what makes `teleclient.notify.watchdog` count: it is
     * registered as an alias of the backup-seam watchdog
     * (StoreBackupServiceProvider.php:238), never as a factory under its own
     * name. The seam being bound says the host MAY be able to serve it; whether
     * the object behind it is a real service is the tick's business, and
     * `describe()` is careful to say so.
     *
     * @return list<string>
     */
    public function boundSeams(): array
    {
        return $this->seamsWhere(
            fn (string $source): bool => $this->container->bound($source),
        );
    }

    /**
     * The seams it does NOT, so those keep the refusing default. Sorted.
     *
     * @return list<string>
     */
    public function unboundSeams(): array
    {
        return $this->seamsWhere(
            fn (string $source): bool => ! $this->container->bound($source),
        );
    }

    /**
     * A resolver binding ONE LAZY FACTORY PER BOUND SEAM ONLY. Never resolves
     * anything itself — no `make()` runs here, and none runs until an activity
     * calls `resolve()` on a tick.
     *
     * An unbound seam gets NO factory, on purpose: @see the class docblock's
     * honesty hinge. Leaving it out means `ContainerResolver::resolve()` raises
     * its own named refusal, the same one an unadapted worker raises, instead of
     * this class laundering a host `BindingResolutionException` into a tick
     * failure whose message names a binding the operator never wrote.
     */
    public function resolver(): ContainerResolver
    {
        $resolver = new ContainerResolver;

        foreach ($this->seamSources() as $seam => $source) {
            if (! $this->container->bound($source)) {
                continue;
            }

            $resolver->bind($seam, fn () => $this->container->make($source));
        }

        return $resolver;
    }

    /**
     * One honest paragraph for `bin/teleframe-worker --describe`.
     *
     * What it states, in this order: how many of the five seams this host's
     * container binds, which ones, what happens to a tick for every seam it does
     * NOT bind (it refuses by name, and the operator's job is to hand the worker
     * a container that binds it — via the entry point), and the limit of what
     * binding anything proves.
     *
     * What it will never say: that a tick can run. No live Temporal server
     * exists in this repo, so "the seams are bound" means a tick REACHES a real
     * service method — nothing more. It is not evidence that a server dispatches
     * the tick, and not evidence that any message was sent.
     */
    public function describe(): string
    {
        $bound = $this->boundSeams();
        $unbound = $this->unboundSeams();
        $total = count($this->seamSources());
        $activities = count(TemporalWorker::servedActivities());

        $lines = [];

        if ($bound === []) {
            $lines[] = sprintf(
                'Container bindings: 0 of %d worker seams bound. This host\'s container binds NONE of the seams the '
                .'%d registered activity types need, so every single tick of every one of them fails with "needs '
                .'the <seam> seam and nothing is bound to it" — the worker is wired to five activities it cannot '
                .'execute. Pass a host container that binds them to %s; the entry point is `%s`.',
                $total,
                $activities,
                self::class,
                TemporalWorker::ENTRY_POINT,
            );
        } else {
            $lines[] = sprintf(
                'Container bindings: %d of %d worker seams bound by this host\'s container (%s).',
                count($bound),
                $total,
                implode(', ', $bound),
            );
        }

        $lines[] = 'The honest limit: a bound seam only means a tick for it REACHES a real service method through '
            .'ContainerResolver. It does NOT prove a Temporal server dispatches the tick, and it does NOT prove a '
            .'real message was sent — this repo has no live Temporal server, so live dispatch is untested.';

        if ($unbound !== []) {
            $lines[] = sprintf(
                'NOT bound here (%d of %d), so a tick for each of these refuses BY NAME before any service is '
                .'touched: %s. To serve one, hand the worker a container that binds it — `%s` is the entry point. '
                .'This class does not fabricate a binding that does not exist, because an invented service would '
                .'make a tick look served while calling something the host never agreed to.',
                count($unbound),
                $total,
                implode(', ', $unbound),
                TemporalWorker::ENTRY_POINT,
            );
        }

        return implode("\n", $lines);
    }

    /**
     * The seam => source map in force: the constructor's override, or the
     * class constant.
     *
     * @return array<string, string>
     */
    private function seamSources(): array
    {
        return $this->seamSources ?? self::SEAM_SOURCES;
    }

    /**
     * The seam names whose source key satisfies the given test, sorted. The
     * test is handed the SOURCE so the caller never has to repeat the lookup.
     *
     * @param  Closure(string): bool  $matches
     * @return list<string>
     */
    private function seamsWhere(Closure $matches): array
    {
        $seams = [];

        foreach ($this->seamSources() as $seam => $source) {
            if ($matches($source)) {
                $seams[] = $seam;
            }
        }

        sort($seams);

        return $seams;
    }
}
