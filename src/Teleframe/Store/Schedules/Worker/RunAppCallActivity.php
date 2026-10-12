<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Schedules\Worker;

use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;

/**
 * RunAppCallActivity — activity type `teleframe.app.run-call`, the work behind
 * every `telegram.app.run-call {app} {call}` schedule AppCallSpec installs.
 *
 * The real work is `AppRuntime::runCall($app, $call)` — the same call the
 * package's own app trigger path makes (AppRuntime::installCalls()/triggers),
 * so a scheduled call and a triggered call resolve the SAME transport, honour
 * the SAME mode check, and log under the SAME `telegram.app.run-call.failed`
 * event.
 *
 * The app/call names arrive as arguments (the workflow reads them off the
 * payload's `app` / `call` keys), NOT parsed back out of the workflow type
 * string — the payload is already keyed for exactly this.
 *
 * Plain-PHP constructible, no container, zero regex.
 */
#[ActivityInterface]
final class RunAppCallActivity
{
    /** The activity type registered on the worker and stubbed by the workflow. */
    public const TYPE = 'teleframe.app.run-call';

    /** Container seam: the AppRuntime the app module binds. */
    public const RUNTIME_SEAM = 'teleclient.app.service';

    public function __construct(
        private readonly ContainerResolver $resolver = new ContainerResolver,
    ) {}

    public static function options(): ActivityOptions
    {
        return ActivityOptions::new()
            // An app call is a Telegram RPC: bounded by the server's own
            // patience, and retried because a transient flood-wait should not
            // cost the whole cadence.
            ->withStartToCloseTimeout(300)
            ->withRetryOptions(
                RetryOptions::new()
                    ->withMaximumAttempts(3),
            );
    }

    /**
     * Run one declared app call.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    #[ActivityMethod(name: self::TYPE)]
    public function runCall(string $app, string $call, array $payload = []): array
    {
        // Refuse an unnamed call by name, here, rather than handing '' to
        // AppRuntime and letting the failure read as "unknown app ''".
        if ($app === '' || $call === '') {
            throw new AppException(sprintf(
                'A %s schedule ticked without naming the call to run (app=%s call=%s). The schedule payload '
                .'must carry the `app` and `call` keys AppCallSpec::installSpec() emits; the workflow type '
                .'string alone is not parsed for routing.',
                'telegram.app.run-call',
                $app === '' ? '(empty)' : $app,
                $call === '' ? '(empty)' : $call,
            ));
        }

        $runtime = $this->resolver->resolve(self::RUNTIME_SEAM);

        $runtime instanceof AppRuntime or throw new \RuntimeException(sprintf(
            'The "%s" seam resolved to %s, which is not an %s. There is no second way to run an app call '
            .'from this package.',
            self::RUNTIME_SEAM,
            $runtime::class,
            AppRuntime::class,
        ));

        $result = $runtime->runCall($app, $call);

        return [
            'app' => $app,
            'call' => $call,
            // The result is a catalog payload (array/scalar) per AppRuntime's
            // contract; anything else is stringified rather than dropped, so a
            // tick's outcome is never silently empty.
            'result' => is_array($result) || is_scalar($result) || $result === null
                ? $result
                : get_debug_type($result),
        ];
    }
}
