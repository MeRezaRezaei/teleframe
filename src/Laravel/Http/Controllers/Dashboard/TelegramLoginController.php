<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\Concerns\ConcernsScopesVault;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Vault\TelegramAccount;

/**
 * Stateful MTProto phone-login flow across HTTP requests.
 *
 * The CLI prompts inline; a browser cannot, so the half-finished login
 * state (phone_code_hash + exported session carrying the generated auth
 * key) is encrypted and parked in the cache between three calls:
 *   start    -> auth.sendCode
 *   verify   -> auth.signIn        (+ 2FA branch)
 *   password -> auth.checkPassword
 *
 * The session is persisted with `SessionData::exportString()` and restored
 * through `TeleframeClient::user(session: <string>)`, so a fresh HTTP
 * request resumes the same MTProto auth key. Ownership (the account's owner
 * morph) is resolved from the authenticated host user at finalize time —
 * no host user model is hardcoded.
 */
final class TelegramLoginController extends Controller
{
    use ConcernsScopesVault;

    private const CACHE_TTL_MINUTES = 15;

    private const CACHE_PREFIX = 'telegram_login:';

    public function start(Request $request, TeleframeAuthService $auth): JsonResponse
    {
        $data = $request->validate([
            'app_id' => ['required', 'integer'],
            'phone' => ['required', 'string', 'min:8', 'max:20'],
            'dc_id' => ['nullable', 'integer', 'min:1', 'max:5'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $app = $this->scopedApps($request)->whereKey((int) $data['app_id'])->first();
        if ($app === null) {
            return response()->json(['message' => 'App not found.'], 404);
        }

        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $dcId = (int) ($data['dc_id'] ?? 2);
        $phone = (string) $data['phone'];

        try {
            $result = $auth->sendPhoneCode(
                $phone,
                (int) $app->api_id,
                (string) $app->api_hash,
                $dcId,
            );
        } catch (TelegramException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not start the login: '.$e->getMessage()], 500);
        }

        $loginId = (string) Str::uuid();
        $state = [
            'user_id' => (int) $user->getKey(),
            'owner_type' => $user::class,
            'app_id' => (int) $app->getKey(),
            'label' => $data['label'] ?? null,
            'phone' => $phone,
            'phone_code_hash' => (string) $result['phone_code_hash'],
            'session' => $result['session']->exportString(),
            'dc_id' => $result['session']->dcId,
            'api_id' => (int) $app->api_id,
            'api_hash' => (string) $app->api_hash,
            'awaiting_2fa' => false,
        ];

        $this->putState($loginId, $state);

        return response()->json([
            'login_id' => $loginId,
            'phone' => $phone,
            'message' => 'Verification code sent via Telegram / SMS.',
        ]);
    }

    public function verify(Request $request, TeleframeAuthService $auth): JsonResponse
    {
        $data = $request->validate([
            'login_id' => ['required', 'string'],
            'code' => ['required', 'string', 'max:12'],
        ]);

        $state = $this->getState((string) $data['login_id']);
        if ($state === null) {
            return response()->json(['message' => 'Login session expired. Start again.'], 410);
        }

        $scope = $this->scope($state);

        try {
            $auth->signInWithCode(
                $scope,
                (string) $state['phone'],
                (string) $state['phone_code_hash'],
                (string) $data['code'],
            );
        } catch (TelegramException $e) {
            if (str_contains($e->getMessage(), 'SESSION_PASSWORD_NEEDED')) {
                $state['awaiting_2fa'] = true;
                $state['session'] = $scope->session->exportString();
                $this->putState((string) $data['login_id'], $state);

                return response()->json([
                    'two_factor_required' => true,
                    'message' => 'Your account has a cloud password. Enter it to finish.',
                ]);
            }

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sign-in failed: '.$e->getMessage()], 500);
        }

        return $this->finalize((string) $data['login_id'], $state, $scope);
    }

    public function password(Request $request, TeleframeAuthService $auth): JsonResponse
    {
        $data = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $state = $this->getState((string) $data['login_id']);
        if ($state === null) {
            return response()->json(['message' => 'Login session expired. Start again.'], 410);
        }

        $scope = $this->scope($state);

        try {
            $auth->check2faPassword($scope, (string) $data['password']);
        } catch (TelegramException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Password check failed: '.$e->getMessage()], 500);
        }

        return $this->finalize((string) $data['login_id'], $state, $scope);
    }

    // ── internals ────────────────────────────────────────────────────

    /** @param array<string, mixed> $state */
    private function finalize(string $loginId, array $state, UserAccountScope $scope): JsonResponse
    {
        $sessionString = $scope->session->exportString();
        $telegramUserId = $scope->session->userId !== null && $scope->session->userId > 0
            ? (int) $scope->session->userId
            : null;

        $account = new TelegramAccount;
        $account->app_id = (int) $state['app_id'];
        $account->type = TelegramAccount::TYPE_USER;
        // owner_type rides in the parked state (start() parks the concrete
        // host user class). States parked by the OLD host-app controller
        // carried no owner_type — fall back to its class name only there.
        $account->owner_type = (string) ($state['owner_type'] ?? 'App\Models\User');
        $account->owner_id = (int) $state['user_id'];
        $account->session = $sessionString;
        $account->dc_id = (int) $state['dc_id'];
        $account->user_id = $telegramUserId;
        $account->label = $this->uniqueLabel(
            (int) $state['app_id'],
            (string) ($state['label'] ?? $state['phone']),
        );
        $account->save();

        Cache::forget(self::CACHE_PREFIX.$loginId);

        return response()->json([
            'ok' => true,
            'account' => AccountsController::payload($account->fresh('app')),
        ], 201);
    }

    /** @param array<string, mixed> $state */
    private function scope(array $state): UserAccountScope
    {
        $client = new TeleframeClient;
        $scope = $client->user(
            session: (string) $state['session'],
            dcId: (int) $state['dc_id'],
            apiId: (int) $state['api_id'],
            apiHash: (string) $state['api_hash'],
        );
        $scope->mtproto->live();

        return $scope;
    }

    /** @param array<string, mixed> $state */
    private function putState(string $loginId, array $state): void
    {
        Cache::put(
            self::CACHE_PREFIX.$loginId,
            Crypt::encryptString(json_encode($state, JSON_THROW_ON_ERROR)),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
        );
    }

    /** @return array<string, mixed>|null */
    private function getState(string $loginId): ?array
    {
        $raw = Cache::get(self::CACHE_PREFIX.$loginId);
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            $json = Crypt::decryptString($raw);
            $state = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($state) ? $state : null;
    }

    private function uniqueLabel(int $appId, string $base): string
    {
        $label = $base;
        $n = 1;
        while (TelegramAccount::query()->where('app_id', $appId)->where('label', $label)->exists()) {
            $n++;
            $label = $base.' #'.$n;
        }

        return $label;
    }
}
