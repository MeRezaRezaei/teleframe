<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules;

use Closure;
use RuntimeException;
use Temporal\Client\ClientOptions;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\ScheduleClient;

/**
 * TemporalClientResolver — the one place that answers "where does a
 * RecurringScheduleClient come from?".
 *
 * The gap this closes: the seam (TemporalScheduleClient / RecurringScheduleClient /
 * SchedulePublisher) had NO in-tree implementor, so a host that set
 * `TELEFRAME_STORE_BACKUP_SCHEDULES=temporal` or `TELEFRAME_APP_SCHEDULES=temporal`
 * could only ever be told "no client is bound". Both provider wirings therefore had
 * to THROW for driver 'temporal', i.e. the user's stated preference ("temporral for
 * shcduls") was unusable. The user has the SDK installed on the host, so the honest
 * answer is not "bind your own client" but "use the SDK that is already there".
 *
 * RESOLUTION ORDER — deliberately fixed, and never a silent null:
 *   1. A host-bound RecurringScheduleClient WINS. The host knows things this
 *      package does not (its own connection pool, its own auth, its own namespace
 *      wiring); a bound client is never second-guessed or wrapped.
 *   2. Otherwise the SDK-backed client, built from the DSN with temporal/sdk's own
 *      Temporal\Client\ServiceClient + Temporal\Client\ScheduleClient.
 *   3. Otherwise RuntimeException whose message is
 *      SdkTemporalScheduleClient::sdkMissingMessage() — it names temporal/sdk and
 *      the composer require. It NEVER returns null, a no-op, or a null-object
 *      publisher: a null here would resurrect the exact false-success this whole
 *      slice exists to kill.
 *
 * NOTHING DIALS HERE. Temporal's ServiceClient::create() wraps a LAZY connection
 * factory (a closure producing the gRPC stub on first use), so constructing the
 * client opens no socket. This keeps the class usable at config/resolution time —
 * a resolver that dialled would break plain-PHP construction and would open a queue
 * connection during a Laravel boot.
 *
 * The optional second constructor argument is a SEAM, not a feature: it lets a test
 * (or a host with an exotic connection strategy) supply the SDK client, or return
 * null to model "the SDK is unavailable". Omitting it keeps the default
 * SDK-from-DSN behaviour. `new TemporalClientResolver($hostBound)` — the only call
 * shape providers need — is unaffected by it.
 *
 * Plain-PHP, no container, zero regex.
 */
final class TemporalClientResolver
{
    /**
     * The factory seam. Receives ($dsn, $namespace) and returns a
     * RecurringScheduleClient, or null to say "the SDK is not usable here".
     *
     * @var null|Closure(string, string): (?RecurringScheduleClient)
     */
    private readonly ?Closure $sdkFactory;

    /**
     * @param  ?RecurringScheduleClient  $hostBound  the container binding, if any; wins over everything
     * @param  null|Closure(string, string): (?RecurringScheduleClient)  $sdkFactory  override for the SDK path (tests / exotic hosts); null = build from the DSN
     */
    public function __construct(
        private readonly ?RecurringScheduleClient $hostBound = null,
        ?Closure $sdkFactory = null,
    ) {
        $this->sdkFactory = $sdkFactory;
    }

    /**
     * The host-bound client, or null. Exposed so a provider can bind the resolver
     * itself and still introspect what it will hand back.
     */
    public function hostBound(): ?RecurringScheduleClient
    {
        return $this->hostBound;
    }

    /**
     * @throws RuntimeException when there is no host client AND the SDK cannot be used
     */
    public function resolve(string $dsn, string $namespace = 'default'): RecurringScheduleClient
    {
        if ($this->hostBound instanceof RecurringScheduleClient) {
            return $this->hostBound;
        }

        $client = $this->sdkFactory !== null
            ? ($this->sdkFactory)($dsn, $namespace)
            : $this->buildFromSdk($dsn, $namespace);

        if ($client instanceof RecurringScheduleClient) {
            return $client;
        }

        throw new RuntimeException(SdkTemporalScheduleClient::sdkMissingMessage());
    }

    /**
     * Build the SDK-backed client from the DSN, or return null when temporal/sdk
     * (or the gRPC extension it needs) is not installed.
     *
     * A DSN the SDK accepts but cannot reach is a CONFIG error, not an
     * availability error, so it is allowed to propagate loudly rather than being
     * flattened into "SDK missing" — a host must not be told to install a package
     * it already installed.
     */
    private function buildFromSdk(string $dsn, string $namespace): ?RecurringScheduleClient
    {
        if (! self::sdkInstalled()) {
            return null;
        }

        // No SDK symbol in a signature: these are resolved at call time, so this
        // file loads and this method is skipped cleanly when the SDK is absent.
        $service = ServiceClient::create($dsn);
        $schedule = ScheduleClient::create(
            $service,
            (new ClientOptions)->withNamespace($namespace),
        );

        return new SdkTemporalScheduleClient($schedule, $namespace);
    }

    /**
     * Is the optional runtime dependency present AND able to open a gRPC channel?
     *
     * temporal/sdk is require-DEV in this package, so "installed" is a property of
     * the HOST, not of us. The gRPC extension is checked too because the SDK's
     * ServiceClient::create() throws without it, and that would read to a host as
     * a cryptic runtime failure instead of a clear install instruction.
     */
    public static function sdkInstalled(): bool
    {
        return class_exists(ScheduleClient::class)
            && class_exists(ServiceClient::class)
            && extension_loaded('grpc');
    }
}
