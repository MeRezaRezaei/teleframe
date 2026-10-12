<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MeRezaRezaei\Teleframe\Control\RelayContext;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayActiveAccount;

/**
 * Control-layer account switch ("sit in the middle").
 *
 * POST accounts/{id}/switch — render ONE vault account ACTIVE for the
 * relay: persist the opaque active context (no session string ever leaves
 * the server) and hand back a fresh lazily bound RelayTerminal that the
 * shared relay re-dials through. Multi-account switching = swap the scope
 * resolver behind the ticket: the terminal is NOT built or dialed here —
 * switchTo() stays fully lazy (first outbound dispatch resolves the scope).
 *
 * Tenant-scoped exactly like the ticket / presence seams: another user's
 * account 404s, unauthenticated requests 401, and sessionless accounts are
 * rejected (422) since there is nothing to render ACTIVE.
 *
 * W-09: the ACTIVE context is namespaced by the requesting tenant (the
 * authenticated owner), so one tenant's switch writes
 * ``relay:active:account:<owner>`` and never re-points another tenant's
 * sessions — cross-tenant consumers watch their own generation key and do
 * not re-seat.
 */
final class RelaySwitchController extends Controller
{
    use ConcernsScopesVault;

    public function store(Request $request, int $id, RelayActiveAccount $switcher): JsonResponse
    {
        $account = $this->scopedAccounts($request)->whereKey($id)->first();

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        if ($account->session === null || $account->session === '') {
            return response()->json(['message' => 'Account has no session to render active.'], 422);
        }

        $switcher->switchFor(
            $this->scopeId($request),
            new RelayContext(
                accountId: (int) $account->getKey(),
                dcId: (int) $account->dc_id,
                label: (string) $account->label,
                liveEndpoint: $this->liveEndpoint(),
            ),
        );

        return response()->json([
            'ok' => true,
            'account_id' => (int) $account->getKey(),
            'live_endpoint' => $this->liveEndpoint(),
        ]);
    }

    private function liveEndpoint(): ?string
    {
        $endpoint = config('teleframe.telegram_web.live_endpoint');

        return is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }

    /**
     * The ownership scope that namespaces the ACTIVE context (W-09): the
     * same authenticated ``owner_type:owner_id`` pair ConcernsScopesVault
     * scopes the queried accounts by, so switching in tenant A writes
     * ``relay:active:account:<A>`` and tenant B's context is untouched.
     */
    private function scopeId(Request $request): string
    {
        $user = $request->user();

        return $user !== null ? $user::class.'#'.$user->getKey() : 'anonymous';
    }
}
