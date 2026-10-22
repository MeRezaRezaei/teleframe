<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use Symfony\Component\HttpFoundation\Response;

/**
 * Host-boundary binding of the embedded tgweb client to the Laravel user
 * session: when a Laravel user session is present (Auth::check() against the
 * guard resolved through the service provider — never a concrete user model)
 * mark the current request scope read-only (ReadOnlyScope::begin()); without
 * a user session the scope is left untouched, so the standalone client keeps
 * its default full behaviour. The flag frame is closed in terminate() when
 * the request finishes.
 *
 * Attach to the /tg page route and the relay switch/presence control routes:
 *
 *     Route::telegramWeb()->middleware(['web', 'auth', 'tg.readonly']);
 *
 * (the dashboard switch/presence routes carry it automatically, see
 * DashboardRoutes). The auth check is an injected callable so hosts/tests can
 * drive it without a real login guard; the default resolves Laravel's
 * Auth::check().
 *
 * PER-REQUEST OWNERSHIP: the frame is opened ONLY when this request found a
 * user session, and closed only when this request opened one. The ownership
 * flag rides on the Request's own attributes rather than on the middleware
 * instance, because a long-lived worker (Octane/Swoole/RoadRunner) may resolve
 * a FRESH middleware instance for terminate() than for handle() — an instance
 * flag would be lost, and an unconditional end() would decrement the shared
 * ReadOnlyScope counter that ANOTHER in-flight request legitimately opened,
 * closing its read-only frame early. A session-less request now touches
 * nothing.
 */
final class ScopeTelegramReadOnly
{
    /** Request attribute marking "this request opened the read-only frame". */
    public const FRAME_ATTRIBUTE = 'teleframe.read_only.frame';

    /** @var (Closure(): bool)|null */
    private readonly ?Closure $authResolver;

    public function __construct(
        private readonly ReadOnlyScope $scope,
        ?callable $authResolver = null,
    ) {
        $this->authResolver = $authResolver === null ? null : $authResolver(...);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->hasUserSession()) {
            $this->scope->begin();
            $request->attributes->set(self::FRAME_ATTRIBUTE, true);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // Close ONLY a frame THIS request opened — never the shared
        // singleton's, which may belong to a concurrent request.
        if ($request->attributes->get(self::FRAME_ATTRIBUTE) !== true) {
            return;
        }

        $request->attributes->remove(self::FRAME_ATTRIBUTE);
        $this->scope->end();
    }

    private function hasUserSession(): bool
    {
        return $this->authResolver !== null
            ? (bool) ($this->authResolver)()
            : Auth::check();
    }
}
