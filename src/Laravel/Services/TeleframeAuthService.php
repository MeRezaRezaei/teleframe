<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use MeRezaRezaei\Teleframe\Bot\Services\BotAccountScope;
use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\PhoneCodeException;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Exceptions\ResendLimitExceededException;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;

/**
 * Service for Telegram MTProto 2.0 User & Bot Authentication workflows.
 * Fully decoupled from CLI so it can be invoked from Controllers, Livewire, Queue Jobs, or Artisan commands.
 */
class TeleframeAuthService
{
    /**
     * Resends allowed per login attempt (per service instance).
     *
     * Three is the point where resending is still worth trying: a code that
     * expired while the operator was away, a code delivered to the wrong
     * medium (call instead of SMS), and one misread expiry. Every further
     * resend costs a real account rate budget (PHONE_NUMBER_FLOOD /
     * FLOOD_WAIT_x) and Telegram burns the phone_code_hash after a couple of
     * resends anyway, so the honest move past the cap is a fresh
     * auth.sendCode — a full restart, not a loop.
     */
    public const MAX_RESENDS_PER_LOGIN = 3;

    /**
     * PSR-16 key prefix for the resend budget. The KEY carries a sha256 of the
     * phone_code_hash, never the hash itself: the cache (redis/file) is not a
     * place a live login credential belongs, but the BUDGET is opaque either
     * way, so a key can be a plain digest.
     */
    public const RESEND_KEY_PREFIX = 'teleframe.auth.resends.';

    /** Budget lifetime: a login code is dead long before this, and the key is cheap. */
    public const RESEND_TTL_SECONDS = 1800;

    protected TeleframeClient $client;

    protected bool $live;

    /** Resends spent by resendCode() since this service was constructed. */
    protected int $resendsUsed = 0;

    /**
     * Cross-request resend budget. The service is a container SINGLETON, but a
     * web caller (controller, Livewire action) gets a fresh instance — and a
     * fresh in-memory counter — on every request, so an in-process counter
     * alone resets the cap to MAX_RESENDS_PER_LOGIN per request. The budget
     * therefore lives in PSR-16, keyed on the phone_code_hash the resend
     * rotates: the cap follows the LOGIN, not the process.
     *
     * Null (plain PHP, the standalone smoke, tests) degrades to the
     * process-local map below — one process is one login there, which is the
     * CLI case the counter was born for.
     */
    protected ?CacheInterface $resendBudget;

    /** phone_code_hash => resends spent, the no-PSR-16 fallback store. */
    protected array $resendsByHash = [];

    public function __construct(?TeleframeClient $client = null, bool $live = true, ?CacheInterface $resendBudget = null)
    {
        $this->client = $client ?? new TeleframeClient;
        $this->live = $live;
        $this->resendBudget = $resendBudget;
    }

    /**
     * Enables live wire mode on a freshly created MTProto scope.
     * Authentication only ever makes sense against real servers;
     * tests construct the service with live=false.
     */
    protected function goLive(UserAccountScope|BotAccountScope $scope): void
    {
        if ($this->live) {
            $scope->mtproto->live();
        }
    }

    /**
     * Step 1: Send SMS/Telegram login verification code to a phone number.
     *
     * @param  string  $phone  Phone number in international format (+1234567890)
     * @param  int  $apiId  Telegram API ID
     * @param  string  $apiHash  Telegram API Hash
     * @param  int  $dcId  Primary DC ID (default: 2)
     * @param  SessionData|null  $session  Existing or new session
     * @return array{phone_code_hash: string, session: SessionData, user: UserAccountScope, raw: array<string, mixed>}
     */
    public function sendPhoneCode(
        string $phone,
        int $apiId,
        string $apiHash,
        int $dcId = 2,
        ?SessionData $session = null
    ): array {
        $sessionData = $session ?? new SessionData(dcId: $dcId, authKey: ''); // empty key: Client handshakes lazily
        $dc = $dcId;

        // Telegram answers PHONE_MIGRATE_x (303 SEE_OTHER) when the phone number
        // lives on another data center. Auth keys are per-DC, so the redirected
        // attempt re-handshakes with a FRESH key bound to the target DC and the
        // rest of the login flow (signInWithCode / check2faPassword) continues
        // there — the returned session carries the migrated DC.
        for ($hop = 0; $hop < 3; $hop++) {
            try {
                return $this->sendCodeOnDc($phone, $apiId, $apiHash, $dc, $sessionData);
            } catch (DcMigrationException $e) {
                $dc = $e->getDcId();
                $sessionData = new SessionData(dcId: $dc, authKey: '');
            }
        }

        // More than a couple of successive migrations is not expected for one
        // phone; let the final hop surface the error to the caller.
        return $this->sendCodeOnDc($phone, $apiId, $apiHash, $dc, $sessionData);
    }

    /**
     * Executes a single auth.sendCode attempt against one data center.
     *
     * The scope (MTProto client + session) is rebuilt on every hop because the
     * DC can change between attempts and the auth key is bound to its DC.
     *
     * @param  string  $phone  Phone number in international format (+1234567890)
     * @param  int  $apiId  Telegram API ID
     * @param  string  $apiHash  Telegram API Hash
     * @param  int  $dcId  Data center to contact for this attempt
     * @param  SessionData  $sessionData  Session bound to $dcId
     * @return array{phone_code_hash: string, session: SessionData, user: UserAccountScope, raw: array<string, mixed>}
     */
    private function sendCodeOnDc(
        string $phone,
        int $apiId,
        string $apiHash,
        int $dcId,
        SessionData $sessionData
    ): array {
        $user = $this->client->user(session: $sessionData, dcId: $dcId, apiId: $apiId, apiHash: $apiHash);
        $this->goLive($user);

        $res = $user->call('auth.sendCode', [
            'phone_number' => $phone,
            'api_id' => $apiId,
            'api_hash' => $apiHash,
            'settings' => ['_' => 'codeSettings'],
        ]);

        $phoneCodeHash = (string) ($res['phone_code_hash'] ?? 'mock_code_hash_'.substr(md5($phone), 0, 8));

        return [
            'phone_code_hash' => $phoneCodeHash,
            'session' => $sessionData,
            'user' => $user,
            'raw' => $res,
        ];
    }

    /**
     * Step 2, outcome-shaped: submit the code and report WHAT to do next
     * instead of letting a PHONE_CODE_* / FLOOD_WAIT_x escape raw and strand
     * the operator.
     *
     * Returns, never throws, for the two auth outcomes a human can act on:
     *
     *  - status=signed_in      → authorization + resends_remaining
     *  - status=code_expired   → hash spent; resend_code()/resendCode() is the
     *                            only move (and it is operator-consented)
     *  - status=code_rejected  → PHONE_CODE_INVALID / PHONE_CODE_EMPTY:
     *                            re-ask for the digits, do NOT resend
     *  - status=flood_wait     → seconds; Telegram told us to stop asking
     *
     * Everything else (SESSION_PASSWORD_NEEDED → the 2FA step, DC migration,
     * transport failures) still propagates, so the existing 2FA flow is
     * untouched.
     *
     * @return array{status: string, authorization?: array<string, mixed>, resends_remaining: int, seconds?: int, error?: string}
     */
    public function attemptCodeSignIn(
        UserAccountScope $user,
        string $phone,
        string $phoneCodeHash,
        string $code
    ): array {
        try {
            $res = $this->signInWithCode($user, $phone, $phoneCodeHash, $code);
        } catch (PhoneCodeException $e) {
            if ($e->isExpired()) {
                return [
                    'status' => 'code_expired',
                    'resends_remaining' => $this->resendsRemaining($phoneCodeHash),
                    'error' => $e->getMessage(),
                ];
            }

            return [
                'status' => 'code_rejected',
                'resends_remaining' => $this->resendsRemaining($phoneCodeHash),
                'error' => $e->getMessage(),
            ];
        } catch (FloodWaitException $e) {
            return [
                'status' => 'flood_wait',
                'resends_remaining' => $this->resendsRemaining($phoneCodeHash),
                'seconds' => $e->seconds,
                'error' => $e->getMessage(),
            ];
        }

        return [
            'status' => 'signed_in',
            'authorization' => $res,
            'resends_remaining' => $this->resendsRemaining($phoneCodeHash),
        ];
    }

    /**
     * Step 2: Submit phone verification code.
     *
     * @return array<string, mixed> auth.Authorization
     */
    public function signInWithCode(
        UserAccountScope $user,
        string $phone,
        string $phoneCodeHash,
        string $code
    ): array {
        $res = $user->call('auth.signIn', [
            'phone_number' => $phone,
            'phone_code_hash' => $phoneCodeHash,
            'phone_code' => $code,
        ]);

        if (isset($res['user']['id']) && $user->mtproto->session !== null) {
            $user->mtproto->session->userId = (int) $res['user']['id'];
            $user->session->userId = (int) $res['user']['id'];
        }

        return $res;
    }

    /**
     * Resends already spent in this login attempt.
     *
     * No argument = the process-local total (the CLI flow, which does the
     * whole login in one process and reads the budget before it has a hash in
     * hand). Pass the phone_code_hash to read the cross-request budget for one
     * login — the cap a web caller must not be able to reset per request.
     */
    public function resendsUsed(?string $phoneCodeHash = null): int
    {
        if ($phoneCodeHash === null) {
            return $this->resendsUsed;
        }

        return $this->budgetFor($phoneCodeHash);
    }

    /** Resends still allowed before the cap refuses. */
    public function resendsRemaining(?string $phoneCodeHash = null): int
    {
        return max(0, self::MAX_RESENDS_PER_LOGIN - $this->resendsUsed($phoneCodeHash));
    }

    /**
     * The ONE resend path (auth.resendCode), and the only one.
     *
     * Deliberately never called from inside a retry loop by the package: the
     * caller (CLI prompt, controller, Livewire action) must obtain explicit
     * operator consent first. The cap is enforced HERE, not by the caller, so
     * no caller can turn this into a loop.
     *
     * The budget is spent BEFORE the wire call and refunded only when Telegram
     * itself refused the hash (a PHONE_CODE_* answer proves no second code was
     * ever sent). A transport failure, timeout or DC migration is NOT a refund:
     * the request may well have reached Telegram and a code may be on its way,
     * so the retry a caller is about to make must not be free — that unbounded
     * path is the PHONE_NUMBER_FLOOD the cap exists to stop.
     *
     * @return array{status: 'sent'|'restart_required', phone_code_hash?: string, resends_remaining: int, error?: string}
     *
     * @throws ResendLimitExceededException when the cap is spent
     * @throws FloodWaitException when Telegram flood-protects the resend itself
     */
    public function resendCode(
        UserAccountScope $user,
        string $phone,
        string $phoneCodeHash
    ): array {
        if ($this->resendsRemaining($phoneCodeHash) <= 0) {
            throw new ResendLimitExceededException($this->resendsUsed($phoneCodeHash), self::MAX_RESENDS_PER_LOGIN);
        }

        $this->spend($phoneCodeHash);

        try {
            $res = $user->call('auth.resendCode', [
                'phone_number' => $phone,
                'phone_code_hash' => $phoneCodeHash,
            ]);
        } catch (PhoneCodeException $e) {
            // Telegram itself refused the hash (PHONE_CODE_EXPIRED /
            // PHONE_CODE_HASH_EMPTY — both are documented errors of
            // auth.resendCode): no amount of resending will help, the login
            // restarts from auth.sendCode. Proven: no code was sent, so the
            // pre-spent resend is refunded.
            $this->refund($phoneCodeHash);

            return [
                'status' => 'restart_required',
                'resends_remaining' => $this->resendsRemaining($phoneCodeHash),
                'error' => $e->getMessage(),
            ];
        }

        $newHash = (string) ($res['phone_code_hash'] ?? $phoneCodeHash);

        return [
            'status' => 'sent',
            'phone_code_hash' => $newHash,
            'resends_remaining' => $this->resendsRemaining($phoneCodeHash),
            'raw' => $res,
        ];
    }

    /** Resends spent on one login (the PSR-16 budget, or the in-process fallback). */
    private function budgetFor(string $phoneCodeHash): int
    {
        if ($this->resendBudget !== null) {
            return (int) $this->resendBudget->get($this->budgetKey($phoneCodeHash), 0);
        }

        return $this->resendsByHash[$phoneCodeHash] ?? 0;
    }

    /**
     * Spend one resend on the login's budget. Counting the attempt, not the
     * success, is what makes a lost response unable to mint a free retry.
     */
    private function spend(string $phoneCodeHash): void
    {
        $used = $this->budgetFor($phoneCodeHash) + 1;

        if ($this->resendBudget !== null) {
            $this->resendBudget->set($this->budgetKey($phoneCodeHash), $used, self::RESEND_TTL_SECONDS);
        }

        $this->resendsByHash[$phoneCodeHash] = $used;
        $this->resendsUsed = max($this->resendsUsed, $used);
    }

    /** Give a resend back — only for a provably code-free Telegram refusal. */
    private function refund(string $phoneCodeHash): void
    {
        $used = max(0, $this->budgetFor($phoneCodeHash) - 1);

        if ($this->resendBudget !== null) {
            $this->resendBudget->set($this->budgetKey($phoneCodeHash), $used, self::RESEND_TTL_SECONDS);
        }

        $this->resendsByHash[$phoneCodeHash] = $used;
        $this->resendsUsed = $used;
    }

    /**
     * The PSR-16 key for one login's budget. sha256 of the phone_code_hash:
     * the budget key must not itself be a usable login credential, and the
     * hash is stable for the whole login (a fresh auth.sendCode = a fresh
     * budget, which is exactly the "restart, don't loop" ruling).
     */
    private function budgetKey(string $phoneCodeHash): string
    {
        return self::RESEND_KEY_PREFIX.hash('sha256', $phoneCodeHash);
    }

    /**
     * Step 3: Complete 2FA Cloud Password login when SESSION_PASSWORD_NEEDED is returned.
     *
     * @param  string  $password  User's 2FA Cloud Password
     * @return array<string, mixed> auth.Authorization
     */
    public function check2faPassword(UserAccountScope $user, string $password): array
    {
        $passwordInfo = $user->call('account.getPassword');
        $srpProof = $user->mtproto->compute2faProof($passwordInfo, $password);

        $res = $user->call('auth.checkPassword', [
            'password' => array_merge(['_' => 'inputCheckPasswordSRP'], $srpProof),
        ]);

        if (isset($res['user']['id']) && $user->mtproto->session !== null) {
            $user->mtproto->session->userId = (int) $res['user']['id'];
            $user->session->userId = (int) $res['user']['id'];
        }

        return $res;
    }

    /**
     * Step 1 (QR): Export a new QR code login token.
     *
     * @return array{token: string, url: string, expires: int, session: SessionData, user: UserAccountScope, raw: array<string, mixed>}
     */
    public function exportQrLoginToken(
        int $apiId,
        string $apiHash,
        int $dcId = 2,
        ?SessionData $session = null
    ): array {
        $sessionData = $session ?? new SessionData(dcId: $dcId, authKey: ''); // empty key: Client handshakes lazily
        $user = $this->client->user(session: $sessionData, dcId: $dcId, apiId: $apiId, apiHash: $apiHash);
        $this->goLive($user);

        $res = $user->call('auth.exportLoginToken', [
            'api_id' => $apiId,
            'api_hash' => $apiHash,
            'except_ids' => [],
        ]);

        $rawToken = $res['token'] ?? random_bytes(32);
        $tokenBase64Url = rtrim(strtr(base64_encode($rawToken), '+/', '-_'), '=');
        $loginUrl = 'tg://login?token='.$tokenBase64Url;

        return [
            'token' => $rawToken,
            'url' => $loginUrl,
            'expires' => (int) ($res['expires'] ?? (time() + 300)),
            'session' => $sessionData,
            'user' => $user,
            'raw' => $res,
        ];
    }

    /**
     * Authenticate a Bot token natively over MTProto binary socket via `auth.importBotAuthorization`.
     *
     * @return array{session: SessionData, bot: BotAccountScope, raw: array<string, mixed>}
     */
    public function loginBot(
        string $botToken,
        int $apiId,
        string $apiHash,
        int $dcId = 2,
        ?SessionData $session = null
    ): array {
        $sessionData = $session ?? new SessionData(dcId: $dcId, authKey: ''); // empty key: Client handshakes lazily
        $bot = $this->client->botMtproto(botToken: $botToken, session: $sessionData, dcId: $dcId, apiId: $apiId, apiHash: $apiHash);
        $this->goLive($bot);

        $authRes = $bot->login();

        return [
            'session' => $sessionData,
            'bot' => $bot,
            'raw' => $authRes,
        ];
    }

    /**
     * Polls auth.exportLoginToken until the QR is scanned (loginTokenSuccess),
     * the account lives on another DC (throws DcMigrationException — reconnect
     * there and call importLoginTokenAt with the exception token), or timeout.
     *
     * @param  \Closure(string, int): void|null  $onToken  Called with (loginUrl, expiresInSeconds) whenever the token refreshes
     * @return array<string, mixed> auth.Authorization from loginTokenSuccess
     */
    public function pollQrLoginToken(
        UserAccountScope $user,
        int $apiId,
        string $apiHash,
        ?\Closure $onToken = null,
        int $timeoutSeconds = 300
    ): array {
        $deadline = time() + $timeoutSeconds;
        while (time() < $deadline) {
            $res = $user->call('auth.exportLoginToken', [
                'api_id' => $apiId,
                'api_hash' => $apiHash,
                'except_ids' => [],
            ]);
            $name = (string) ($res['_'] ?? '');
            if ($name === 'auth.loginToken') {
                if ($onToken !== null) {
                    $raw = (string) $res['token'];
                    $b64 = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
                    $onToken('tg://login?token='.$b64, (int) $res['expires']);
                }
                sleep(2);

                continue;
            }
            if ($name === 'auth.loginTokenMigrateTo') {
                throw new DcMigrationException(
                    (int) $res['dc_id'],
                    'QR login requires migration to DC '.(int) $res['dc_id'],
                    303,
                    (string) $res['token']
                );
            }
            if ($name === 'auth.loginTokenSuccess') {
                return (array) $res['authorization'];
            }
            throw new RuntimeException('TeleframeAuthService: unexpected QR login response '.$name);
        }
        throw new RuntimeException('TeleframeAuthService: QR login timed out after '.$timeoutSeconds.'s');
    }

    /**
     * Continues QR login on the migrated DC with the token from DcMigrationException.
     *
     * @return array<string, mixed> auth.Authorization
     */
    public function importLoginTokenAt(int $dcId, string $token, int $apiId, string $apiHash, ?SessionData $session = null): array
    {
        $sessionData = $session ?? new SessionData(dcId: $dcId, authKey: ''); // empty key: Client handshakes lazily
        $user = $this->client->user(session: $sessionData, dcId: $dcId, apiId: $apiId, apiHash: $apiHash);
        $this->goLive($user);

        $res = $user->call('auth.importLoginToken', ['token' => $token]);
        $name = (string) ($res['_'] ?? '');
        if ($name === 'auth.loginTokenSuccess') {
            return (array) $res['authorization'];
        }
        throw new RuntimeException('TeleframeAuthService: unexpected importLoginToken response '.$name);
    }
}
