<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use Closure;

/**
 * ContainerResolver — the one lazy seam every worker activity resolves its
 * Laravel collaborator through.
 *
 * WHY IT EXISTS. The three activities' real work already exists in the package
 * (`AppRuntime::runCall`, `DatabaseBackupService::backup`, `NoShowWatchdog::check`)
 * and each needs a bound object from the Laravel container: a client, a scope,
 * a config-derived target. A worker is a LONG-RUNNING process, so resolving
 * those in an activity's constructor would either resolve at worker-boot (a
 * boot-time container hit for every seam, in a process that may serve a task
 * queue nothing ever ticks) or refuse to construct without a container — which
 * would break plain-PHP construction.
 *
 * So: an activity holds a CLOSURE, not a service. The closure runs on the
 * first `resolve()` and is memoised from then on, so a container binding is
 * touched at most once per activity instance and NEVER at construction. With
 * no resolver bound, `resolve()` throws a named RuntimeException rather than
 * returning null — a worker whose activity cannot reach its service must say
 * which seam is missing, not silently no-op on every tick.
 *
 * An untyped `Closure` on purpose: no SDK class, no container class, and no
 * service class appears in any signature, so this file loads with the SDK
 * absent and constructs with nothing at all.
 *
 * Plain-PHP, no container, zero regex.
 */
final class ContainerResolver
{
    /**
     * Seam name => zero-arg factory. Deliberately UNPARAMETERISED: a host
     * factory is arbitrary code, and resolve() checks what it returned rather
     * than trusting the signature. A `Closure(): object` annotation here would
     * make that runtime check dead code by declaration.
     *
     * @var array<string, Closure>
     */
    private array $factories;

    /** @var array<string, object> */
    private array $resolved = [];

    /**
     * @param  array<string, Closure>  $factories  seam name => zero-arg factory
     */
    public function __construct(array $factories = [])
    {
        $this->factories = $factories;
    }

    /**
     * Build a resolver that is guaranteed to refuse. This is the default
     * posture for a container-free worker: a tick then fails naming the seam,
     * rather than running an activity that does nothing.
     */
    public static function refusing(): self
    {
        return new self;
    }

    public function bind(string $seam, Closure $factory): self
    {
        $this->factories[$seam] = $factory;
        unset($this->resolved[$seam]);

        return $this;
    }

    /**
     * The bound object for a seam, resolved on first use and memoised.
     *
     * @throws \RuntimeException when the seam is unbound, or its factory
     *                           returns something that is not an object
     */
    public function resolve(string $seam): object
    {
        if (isset($this->resolved[$seam])) {
            return $this->resolved[$seam];
        }

        if (! isset($this->factories[$seam])) {
            throw new \RuntimeException(sprintf(
                'The Temporal worker activity for this tick needs the "%s" seam and nothing is bound to it. '
                .'A Temporal worker runs OUTSIDE a web request but INSIDE the host application, so the seam is '
                .'bound at worker boot — pass a ContainerResolver with a factory for "%s" to '
                .'%s::run() (e.g. new ContainerResolver(["%s" => fn() => $container->make(…)]). '
                .'Refusing here is deliberate: returning null would make every tick succeed while doing nothing.',
                $seam,
                $seam,
                TemporalWorker::class,
                $seam,
            ));
        }

        $value = ($this->factories[$seam])();

        if (! is_object($value)) {
            throw new \RuntimeException(sprintf(
                'The factory bound to the Temporal worker seam "%s" returned %s, not an object. '
                .'A worker activity with no service to call cannot run, and reporting success would be a lie.',
                $seam,
                get_debug_type($value),
            ));
        }

        return $this->resolved[$seam] = $value;
    }

    /**
     * The seams this resolver can serve, sorted. A worker reports this so an
     * operator can see which activity surfaces are actually wired.
     *
     * @return list<string>
     */
    public function seams(): array
    {
        $seams = array_keys($this->factories);
        sort($seams);

        return $seams;
    }

    public function has(string $seam): bool
    {
        return isset($this->factories[$seam]);
    }
}
