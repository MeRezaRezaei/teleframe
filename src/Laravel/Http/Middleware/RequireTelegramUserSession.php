<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The deny-by-default session guard on the embedded Telegram client routes.
 *
 * tg.readonly (ScopeTelegramReadOnly) is NOT a guard: it only OPENS the
 * read-only scope frame when a user session is ALREADY present, so it does
 * nothing at all for a session-less request. That left the embedded client
 * route reachable anonymously, and — worse — a session-less request also
 * arrives at the relay with the read-only frame CLOSED, i.e. a silent allow
 * straight into full write access. Verbatim :11249 ("laravel session is not
 * logied in and that page in this situation should have a auth middleware")
 * and verbatim :6803 ("this user of laravel should only have read only
 * acces to this telgram account or that bot account") are the requirement
 * this class closes.
 *
 * Posture, in order:
 *
 *  1. no authenticated Laravel user        → 401, the controller never runs;
 *  2. the auth resolver itself throws      → 401 (fail closed; when in
 *     doubt, DENY — a broken guard backend must never become an allow);
 *  3. an authenticated Laravel user        → passed through untouched, so
 *     the read-only posture the user IS entitled to (ScopeTelegramReadOnly
 *     opening the frame) still applies downstream.
 *
 * The refusal body is a fixed string: it names no account, no session, no
 * vault row, and it never echoes the resolver's failure, so an anonymous
 * caller learns only that it is unauthenticated. Plain PHP + an injected
 * callable — never a concrete user model, never a session string.
 */
final class RequireTelegramUserSession
{
    /** The one and only refusal body. */
    private const REFUSAL = ['message' => 'Unauthenticated.'];

    /** @var (Closure(): bool)|null */
    private readonly ?Closure $authResolver;

    public function __construct(?callable $authResolver = null)
    {
        $this->authResolver = $authResolver === null ? null : $authResolver(...);
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->hasUserSession()) {
            return new JsonResponse(self::REFUSAL, Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }

    /**
     * Whether an authenticated Laravel user backs this request.
     *
     * A resolver that throws is a DENY, never an allow: the guard failing
     * open is the vulnerability this class exists to remove.
     */
    private function hasUserSession(): bool
    {
        try {
            return $this->authResolver !== null
                ? (bool) ($this->authResolver)()
                : Auth::check();
        } catch (Throwable) {
            return false;
        }
    }
}
