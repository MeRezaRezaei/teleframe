<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Laravel\Services\ReadOnlyScope;
use MeRezaRezaei\Teleframe\Laravel\Services\RelayTicketBroker;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;

/**
 * Control-layer ticket seam ("sit in the middle").
 *
 * POST accounts/{id}/ticket  — mint a short-lived single-use relay ticket
 *                              bound to one vault account (no session string
 *                              ever leaves the server).
 * POST relay/redeem          — consume a ticket; returns the bound account
 *                              id + dc id for the relay, exactly once.
 *
 * The mint endpoint is tenant-scoped like every vault API. The redeem
 * endpoint is deliberately NOT tenant-scoped: the ticket itself is the
 * authorization (a redeemer only learns the account the ticket was minted
 * for), and the Wave-B relay consumes tickets server-side.
 *
 * The mint route is host-guarded; when the acting Laravel user is
 * resolvable, the ticket binds their id (Auth::id()) into the payload so
 * relay policy middlewares can key the per-connection dispatch on the real
 * user. An unauthenticated mint still works and binds no user.
 *
 * C5: the ticket also carries the minting request's READ-ONLY intent, read
 * from the shared ReadOnlyScope the `tg.readonly` middleware arms for a
 * session-backed request. This is the only read-only SOURCE that exists in
 * the `teleframe:relay` console daemon (a per-request HTTP frame never
 * reaches it), so binding it here is what makes the vault terminal's
 * read-only guard enforceable in the process that actually dispatches RPCs.
 * The flag is on the ticket, never on the response: the JSON contract is
 * unchanged.
 */
final class RelayTicketController extends Controller
{
    use ConcernsScopesVault;

    public function store(Request $request, int $id, RelayTicketBroker $broker, ReadOnlyScope $scope): JsonResponse
    {
        $account = $this->scopedAccounts($request)->whereKey($id)->first();

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        if ($account->session === null || $account->session === '') {
            return response()->json(['message' => 'Account has no session to relay.'], 422);
        }

        $ticket = $broker->mint((int) $account->getKey(), $this->mintingUser(), $this->mintingReadOnly($scope));

        return response()->json([
            'ticket' => $ticket['ticket'],
            'account_id' => $ticket['account_id'],
            'user_id' => $ticket['user_id'],
            'expires_at' => $ticket['expires_at'],
            'live_endpoint' => $this->liveEndpoint(),
        ], 201);
    }

    public function redeem(Request $request, RelayTicketBroker $broker): JsonResponse
    {
        $data = $request->validate([
            'ticket' => ['required', 'string', 'min:8'],
        ]);

        $accountId = $broker->redeem((string) $data['ticket']);

        if ($accountId === null) {
            return response()->json(['message' => 'Ticket invalid, expired, or already used.'], 410);
        }

        $account = TelegramAccount::query()->whereKey($accountId)->first();

        return response()->json([
            'ok' => true,
            'account_id' => $accountId,
            'dc_id' => $account?->dc_id !== null ? (int) $account->dc_id : null,
        ]);
    }

    private function liveEndpoint(): ?string
    {
        $endpoint = config('teleframe.telegram_web.live_endpoint');

        return is_string($endpoint) && $endpoint !== '' ? $endpoint : null;
    }

    /**
     * The Laravel user behind this host-guarded mint request, or null when
     * no authenticated user is resolvable (console, tests without an acting
     * user). Never throws — Auth::id() silently reports an unresolved guard.
     */
    private function mintingUser(): int|string|null
    {
        return Auth::id();
    }

    /**
     * The read-only intent bound into the minted ticket: true when the
     * minting request ran inside a read-only scope (a Laravel user session —
     * the `tg.readonly` middleware the dashboard group carries), false when
     * the host has no session-backed restriction in force.
     *
     * Configurable via teleframe.relay.read_only.session_minted_tickets, whose
     * DEFAULT is the enforcing value (true): a ticket minted from a read-only
     * session IS a read-only ticket, so the daemon refuses writes on that
     * connection. A host that genuinely wants a session-backed user to drive
     * the account must opt out explicitly — the flag defaults to safe because
     * a guard that fails open is worse than no guard, because it is trusted.
     */
    private function mintingReadOnly(ReadOnlyScope $scope): bool
    {
        if (! $scope->isReadOnly()) {
            return false;
        }

        return (bool) config('teleframe.relay.read_only.session_minted_tickets', true);
    }
}
