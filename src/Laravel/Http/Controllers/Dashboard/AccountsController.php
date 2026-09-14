<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;

/** Linked Telegram accounts (encrypted session / bot token). */
final class AccountsController extends Controller
{
    use ConcernsScopesVault;

    public function index(Request $request): JsonResponse
    {
        $accounts = $this->scopedAccounts($request)
            ->with('app')
            ->latest('id')
            ->get()
            ->map(fn (TelegramAccount $account): array => self::payload($account))
            ->values();

        return response()->json(['accounts' => $accounts]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $account = $this->scopedAccounts($request)->whereKey($id)->first();

        if ($account === null) {
            return response()->json(['message' => 'Account not found.'], 404);
        }

        $account->delete();

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    public static function payload(TelegramAccount $account): array
    {
        $app = $account->app;

        return [
            'id' => (int) $account->getKey(),
            'label' => (string) $account->label,
            'type' => (string) $account->type,
            'user_id' => $account->user_id !== null ? (int) $account->user_id : null,
            'dc_id' => (int) $account->dc_id,
            'app_id' => $account->app_id !== null ? (int) $account->app_id : null,
            'app_label' => $app?->label,
            'has_session' => $account->session !== null && $account->session !== '',
            'created_at' => $account->created_at?->toIso8601String(),
        ];
    }
}
