<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Vault\TelegramApp;

/**
 * Telegram application credentials (my.telegram.org): api_id + api_hash.
 *
 * Backed by the Teleframe vault model — `api_hash` is an `encrypted` cast,
 * so it is ciphertext at rest and NEVER returned to the client in full
 * (only a masked preview). Tenant-scoped to the authenticated host user via
 * the nullable owner morph.
 */
final class AppsController extends Controller
{
    use ConcernsScopesVault;

    public function index(Request $request): JsonResponse
    {
        $apps = $this->scopedApps($request)
            ->latest('id')
            ->get()
            ->map(fn (TelegramApp $app): array => $this->payload($app))
            ->values();

        return response()->json(['apps' => $apps]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'api_id' => ['required', 'integer', 'min:1'],
            'api_hash' => ['required', 'string', 'min:16', 'max:255'],
        ]);

        $owner = $this->owner($request);
        if ($owner === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // telegram_apps.label is UNIQUE at the DB layer (vault apps are
        // resolved by label via TF::vault()->app($label)), so the duplicate
        // check must be global, not tenant-scoped.
        if (TelegramApp::query()->where('label', $data['label'])->exists()) {
            return response()->json(['message' => 'An app with that label already exists.'], 422);
        }

        $app = new TelegramApp;
        $app->label = $data['label'];
        $app->api_id = (int) $data['api_id'];
        $app->api_hash = $data['api_hash'];
        $app->owner_type = $owner['owner_type'];
        $app->owner_id = $owner['owner_id'];
        $app->save();

        return response()->json(['app' => $this->payload($app)], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $app = $this->scopedApps($request)->whereKey($id)->first();

        if ($app === null) {
            return response()->json(['message' => 'App not found.'], 404);
        }

        // nullOnDelete on telegram_accounts: linked accounts survive with
        // app_id = null (MTProto unusable until re-linked).
        $app->delete();

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function payload(TelegramApp $app): array
    {
        $hash = (string) $app->api_hash;

        return [
            'id' => (int) $app->getKey(),
            'label' => (string) $app->label,
            'api_id' => (int) $app->api_id,
            'api_hash' => $this->mask($hash),
            'created_at' => $app->created_at?->toIso8601String(),
        ];
    }

    private function mask(string $secret): string
    {
        $len = strlen($secret);
        if ($len <= 6) {
            return str_repeat('•', $len);
        }

        return substr($secret, 0, 4).str_repeat('•', max(4, $len - 8)).substr($secret, -4);
    }
}
