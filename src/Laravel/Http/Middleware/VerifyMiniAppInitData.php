<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates Telegram Mini App / Web App cryptographic HMAC-SHA256 signatures.
 * Adheres strictly to Telegram Bot API WebApp authentication guidelines.
 */
class VerifyMiniAppInitData
{
    /**
     * Handle an incoming request from Telegram Mini App.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-Telegram-Init-Data') ?? $request->input('initData');
        if (!$initData || !is_string($initData)) {
            return response()->json(['error' => 'Missing Telegram initData'], 401);
        }

        $botToken = config('teleframe.bot_token')
            ?? config('teleframe.default_bot_token')
            ?? config('telegram.bot_token')
            ?? config('telegram.default_bot_token')
            ?? env('TELEGRAM_BOT_TOKEN');
        if (!$botToken) {
            return response()->json(['error' => 'Bot token not configured on server'], 500);
        }

        $validatedUser = $this->validateInitData($initData, $botToken);
        if (!$validatedUser) {
            return response()->json(['error' => 'Invalid Telegram HMAC signature'], 403);
        }

        $request->attributes->set('telegram_user', $validatedUser);

        return $next($request);
    }

    /**
     * Cryptographically validates Telegram Mini App initData without key mangling.
     */
    public function validateInitData(string $initData, string $botToken): ?array
    {
        $pairs = explode('&', $initData);
        $params = [];
        $hash = null;

        foreach ($pairs as $pair) {
            if ($pair === '') {
                continue;
            }
            $parts = explode('=', $pair, 2);
            $key = urldecode($parts[0]);
            $value = isset($parts[1]) ? urldecode($parts[1]) : '';

            if ($key === 'hash') {
                $hash = $value;
            } else {
                $params[$key] = $value;
            }
        }

        if ($hash === null || empty($params)) {
            return null;
        }

        // Sort parameters alphabetically
        ksort($params);

        $dataCheckArr = [];
        foreach ($params as $k => $v) {
            $dataCheckArr[] = "{$k}={$v}";
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        // secret_key = HMAC_SHA256("WebAppData", bot_token)
        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($calculatedHash, $hash)) {
            return null;
        }

        // Freshness, and it belongs HERE rather than only in the guard.
        //
        // A valid HMAC proves Telegram signed this string; it says nothing
        // about WHEN. Without this gate a captured initData URL is a
        // permanent credential: it keeps verifying forever, so anyone who
        // obtains one — a shared browser history, a proxy log, a referrer
        // header, a screenshot of a URL — authenticates indefinitely.
        //
        // TgWebAppGuard has always had this check, so the capability existed
        // and was tested. It simply was not on the path this middleware takes,
        // and `tg.miniapp` is the alias the package advertises and its own
        // stub Mini App documents. IdentityConfig even states that the two
        // layers "must agree word for word" — they did not, because one
        // checked freshness and this one did not.
        //
        // After hash_equals, never before: auth_date is only trustworthy once
        // Telegram's own signature has covered it.
        if (! self::isFreshEnough($params)) {
            return null;
        }

        if (isset($params['user'])) {
            return json_decode($params['user'], true) ?: null;
        }

        return $params;
    }

    /**
     * The replay window, read from the SAME key the guard reads.
     *
     * `teleframe.identity.miniapp_auth_max_age` (env
     * TELEFRAME_MINIAPP_AUTH_MAX_AGE, default 1800s) is owned by
     * IdentityConfig; it is read through config() when a container is
     * available and the env var otherwise, because this class is also
     * instantiated with `new` by the guard and must keep working with no
     * application at all.
     *
     * @param  array<string, string>  $params  the verified, still-unsorted params
     */
    public static function isFreshEnough(array $params): bool
    {
        $raw = $params['auth_date'] ?? null;

        if ($raw === null || $raw === '' || ! ctype_digit($raw)) {
            // Absent or unparseable auth_date cannot be shown to be fresh.
            // Failing open here is what made the guard's copy necessary, and
            // repeating it would reintroduce the same hole on this path.
            return false;
        }

        // A FUTURE auth_date is not "very fresh", it is a clock skew or a
        // forgery attempt. Allow a small tolerance so a host whose clock runs
        // slightly behind Telegram is not locked out of its own Mini App.
        $age = time() - (int) $raw;

        return $age <= self::maxAuthAge() && $age >= -self::CLOCK_SKEW_TOLERANCE;
    }

    /** Seconds of clock skew tolerated in the operator's favour. */
    private const CLOCK_SKEW_TOLERANCE = 60;

    private static function maxAuthAge(): int
    {
        // Same guard shape as IdentityConfig::configOrEnv, so the two layers
        // read the key identically — which is the whole point of having moved
        // the rule here.
        if (function_exists('config')) {
            try {
                $configured = config('teleframe.identity.miniapp_auth_max_age');
            } catch (\Throwable) {
                // No container bound: this class is also instantiated with
                // `new` by the guard, so the env fallback below has to stand.
                $configured = null;
            }

            if ($configured !== null && $configured !== '' && $configured !== false) {
                return max(0, (int) $configured);
            }
        }

        $env = getenv('TELEFRAME_MINIAPP_AUTH_MAX_AGE');

        if ($env === false || $env === '') {
            return 1800;
        }

        return max(0, (int) $env);
    }
}
