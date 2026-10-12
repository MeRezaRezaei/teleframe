<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MeRezaRezaei\Teleframe\Control\RelaySessionRegistry;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Realtime\PresenceFeed;

/**
 * Control-layer presence surface ("sit in the middle").
 *
 * GET  accounts/{id}/presence           — is THIS account's relay session
 *                                        alive right now? Reports online,
 *                                        and advertises the live endpoint
 *                                        only when one is provisioned. Also
 *                                        fans the current status out to
 *                                        `account:{id}:updates` (Wave E).
 * POST accounts/{id}/presence/touch     — heartbeat an active session or
 *                                        open a `manual` one (browser
 *                                        watching without a server-side
 *                                        relay yet). Also announces the
 *                                        updated presence (Wave E).
 *
 * Both endpoints are tenant-scoped exactly like the ticket seam: a request
 * for another user's account 404s, unauthenticated requests 401.
 *
 * PresenceFeed is resolved ONLY when the realtime bridge is bound (i.e.
 * teleframe.realtime.url is configured); hosts without one stay silent
 * about realtime. It is NOT a method dependency — Laravel treats a
 * class-typed parameter with a default value as "never resolve" — so the
 * optional feed is pulled from the container here instead.
 */
final class RelayPresenceController extends Controller
{
    use ConcernsScopesVault;

    public function show(Request $request, int $id, RelaySessionRegistry $registry): JsonResponse
    {
        $account = $this->scopedAccounts($request)->whereKey($id)->first();

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $accountId = (int) $account->getKey();
        $this->presenceFeed()?->announce($accountId);

        $payload = [
            'account_id' => $accountId,
            'online' => $registry->status($accountId) !== null,
        ];

        $endpoint = config('teleframe.telegram_web.live_endpoint');
        if (is_string($endpoint) && $endpoint !== '') {
            $payload['live_endpoint'] = $endpoint;
        }

        return response()->json($payload);
    }

    public function touch(Request $request, int $id, RelaySessionRegistry $registry): JsonResponse
    {
        $account = $this->scopedAccounts($request)->whereKey($id)->first();

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $accountId = (int) $account->getKey();
        if ($registry->status($accountId) !== null) {
            $registry->touch($accountId);
        } else {
            $registry->start($accountId, 'manual');
        }

        $this->presenceFeed()?->announce($accountId);

        return response()->json(['ok' => true]);
    }

    private function presenceFeed(): ?PresenceFeed
    {
        try {
            return app(PresenceFeed::class);
        } catch (BindingResolutionException) {
            return null;
        }
    }
}
