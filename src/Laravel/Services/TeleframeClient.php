<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Schema\MethodRegistry;
use RuntimeException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Bot\Services\BotAccountScope;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Vault\Vault;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * High-level Laravel Telegram Client Manager.
 * Supports multi-tenant runtime API credentials, user sessions, and bot accounts.
 */
class TeleframeClient
{
    private ?HttpFactory $http = null;

    private readonly LoggerInterface $logger;

    private ?Vault $vault;

    public function __construct(
        public int $defaultApiId = 0,
        public string $defaultApiHash = '',
        public ?string $defaultBotToken = null,
        public ?array $defaultProxyConfig = null,
        public ?string $defaultUserSession = null,
        public ?string $defaultBotSession = null,
        public int $defaultDcId = 2,
        ?HttpFactory $http = null,
        ?LoggerInterface $logger = null,
        ?Vault $vault = null,
    ) {
        $this->http = $http;
        $this->logger = $logger ?? new NullLogger();
        $this->vault = $vault;
    }

    /**
     * Dispatch a generated builder request (['_' => method, ...params]) to the
     * transport the packaged schema catalog assigns to the method: 'mtproto'
     * → user() scope, 'bot-http' → bot() client.
     *
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     *
     * @throws \InvalidArgumentException when the method exists in neither schema artifact
     */
    public function dispatch(array $request): array
    {
        $name = (string) ($request['_'] ?? '');
        $api = MethodRegistry::apiOf($name);
        unset($request['_']);

        try {
            return $api === 'mtproto'
                ? $this->user()->call($name, $request)
                : $this->bot()->call($name, $request);
        } catch (\Throwable $e) {
            // PSR-3 seam: RPC/wire failures are logged, never swallowed —
            // the original exception still propagates unchanged.
            $this->logger->error('teleframe.rpc.failed', [
                'method' => $name,
                'api' => $api,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    /**
     * Batch passthrough: N independent MTProto methods in ONE round-trip on
     * the default user scope's connection (ergonomic use with the .env session).
     *
     * @param array<string, array{method: string, params: array<string, mixed>}> $requests
     * @return array<string, array<string, mixed>> key => decoded result, input order preserved
     */
    public function callMany(array $requests): array
    {
        try {
            return $this->user()->mtproto->callMany($requests);
        } catch (\Throwable $e) {
            $this->logger->error('teleframe.rpc.batch_failed', [
                'methods' => array_map(
                    static fn (array $r): string => (string) $r['method'],
                    $requests,
                ),
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            throw $e;
        }
    }

    /**
     * Create or bind an MTProto user account session.
     * If no session is provided, falls back to the configured default user session from .env.
     *
     * @param int|null $accountId Telegram user ID (optional if session contains it)
     * @param string|SessionData|null $session SessionData object, exported base64 string, or raw AuthKey
     * @param int|null $dcId Primary DC ID (default: configured defaultDcId or 2)
     * @param int|null $apiId Custom runtime API ID (falls back to default if null)
     * @param string|null $apiHash Custom runtime API Hash (falls back to default if null)
     * @param array|null $proxyConfig Custom runtime proxy config (falls back to default if null)
     */
    public function user(
        ?int $accountId = null,
        string|SessionData|null $session = null,
        ?int $dcId = null,
        ?int $apiId = null,
        ?string $apiHash = null,
        ?array $proxyConfig = null
    ): UserAccountScope {
        $finalApiId = $apiId ?? $this->defaultApiId;
        $finalApiHash = $apiHash ?? $this->defaultApiHash;
        $finalProxy = $proxyConfig ?? $this->defaultProxyConfig;
        $finalDcId = $dcId ?? $this->defaultDcId;
        $targetSession = $session ?? $this->defaultUserSession;

        if (empty($finalApiId) || empty($finalApiHash)) {
            throw new RuntimeException("Telegram API ID and API Hash are required. Pass them to user() or configure defaults in config/teleframe.php.");
        }

        if ($targetSession instanceof SessionData) {
            $sessionData = $targetSession;
        } elseif (is_string($targetSession) && str_contains(base64_decode($targetSession, true) ?: '', ':')) {
            $sessionData = SessionData::importString($targetSession);
        } else {
            $sessionData = new SessionData(
                dcId: $finalDcId,
                authKey: is_string($targetSession) ? $targetSession : '',
                userId: $accountId
            );
        }

        $mtproto = new MTProtoClient(
            apiId: $finalApiId,
            apiHash: $finalApiHash,
            session: $sessionData
        );

        if ($finalProxy) {
            $mtproto->setProxy($finalProxy);
        }

        return new UserAccountScope($mtproto, $sessionData);
    }

    /**
     * Backward-compatible alias for user().
     */
    public function forAccount(
        ?int $accountId = null,
        string|SessionData|null $session = null,
        ?int $dcId = null,
        ?int $apiId = null,
        ?string $apiHash = null,
        ?array $proxyConfig = null
    ): UserAccountScope {
        return $this->user($accountId, $session, $dcId, $apiId, $apiHash, $proxyConfig);
    }

    /**
     * Declared Telegram schema layer of the packaged artifacts (spec D5).
     */
    public function schemaLayer(): int
    {
        return \MeRezaRezaei\Teleframe\Core\Schema\SchemaLayer::layer();
    }

    /**
     * Create a client directly from an exported session string.
     */
    public function fromSession(
        string $sessionString,
        ?int $apiId = null,
        ?string $apiHash = null,
        ?array $proxyConfig = null
    ): UserAccountScope {
        return $this->user(
            accountId: null,
            session: $sessionString,
            apiId: $apiId,
            apiHash: $apiHash,
            proxyConfig: $proxyConfig
        );
    }

    /**
     * Create or bind a Bot API client (over HTTP Bot API).
     *
     * @param string|null $botToken Custom runtime Bot Token (falls back to default if null)
     * @param array|null $proxyConfig Custom runtime proxy
     */
    public function bot(?string $botToken = null, ?array $proxyConfig = null): BotClient
    {
        $finalToken = $botToken ?? $this->defaultBotToken;
        if (empty($finalToken)) {
            throw new RuntimeException("Telegram Bot Token is required. Pass it to bot() or configure TELEGRAM_BOT_TOKEN in .env.");
        }

        return new BotClient($finalToken, $proxyConfig ?? $this->defaultProxyConfig, http: $this->http);
    }

    /**
     * Create or bind a Bot account operating directly over high-speed MTProto 2.0 (Binary TCP RPC).
     * Uses MTProto `auth.importBotAuthorization` for bot login without Bot API HTTP latency.
     *
     * @param string|null $botToken Custom runtime Bot Token
     * @param string|SessionData|null $session
     * @param int|null $dcId Primary DC ID (default: 2)
     * @param int|null $apiId
     * @param string|null $apiHash
     * @param array|null $proxyConfig
     */
    public function botMtproto(
        ?string $botToken = null,
        string|SessionData|null $session = null,
        ?int $dcId = null,
        ?int $apiId = null,
        ?string $apiHash = null,
        ?array $proxyConfig = null
    ): BotAccountScope {
        $finalToken = $botToken ?? $this->defaultBotToken;
        if (empty($finalToken)) {
            throw new RuntimeException("Telegram Bot Token is required for MTProto bot authorization.");
        }

        $finalApiId = $apiId ?? $this->defaultApiId;
        $finalApiHash = $apiHash ?? $this->defaultApiHash;
        $finalProxy = $proxyConfig ?? $this->defaultProxyConfig;
        $finalDcId = $dcId ?? $this->defaultDcId;
        $targetSession = $session ?? $this->defaultBotSession;

        if (empty($finalApiId) || empty($finalApiHash)) {
            throw new RuntimeException("Telegram API ID and API Hash are required for MTProto connections.");
        }

        if ($targetSession instanceof SessionData) {
            $sessionData = $targetSession;
        } elseif (is_string($targetSession) && str_contains(base64_decode($targetSession, true) ?: '', ':')) {
            $sessionData = SessionData::importString($targetSession);
        } else {
            $sessionData = new SessionData(
                dcId: $finalDcId,
                authKey: is_string($targetSession) ? $targetSession : ''
            );
        }

        $mtproto = new MTProtoClient(
            apiId: $finalApiId,
            apiHash: $finalApiHash,
            session: $sessionData
        );

        if ($finalProxy) {
            $mtproto->setProxy($finalProxy);
        }

        return new BotAccountScope($mtproto, $sessionData, $finalToken);
    }

    /**
     * Named user scope from the DB vault (Task 4: thin wrapper).
     *
     * @param string|int|null $labelOrUserId Telegram user id (int) or legacy label (string)
     */
    public function userFromVault(string|int|null $labelOrUserId = null, ...$overrides): UserAccountScope
    {
        $account = $this->resolveVaultAccount($labelOrUserId);
        $creds = $account !== null ? $account->credentials() : [];
        $resolvedUserId = $account !== null ? $account->getAttribute('user_id') : null;

        return $this->user(
            accountId: $overrides['accountId'] ?? ($resolvedUserId !== null ? (int) $resolvedUserId : null),
            session: $overrides['session'] ?? $creds['session'] ?? null,
            dcId: $overrides['dcId'] ?? $creds['dc_id'] ?? null,
            apiId: $overrides['apiId'] ?? $creds['api_id'] ?? null,
            apiHash: $overrides['apiHash'] ?? $creds['api_hash'] ?? null,
            proxyConfig: $overrides['proxyConfig'] ?? null,
        );
    }

    /**
     * Named bot client from the DB vault (Task 4: thin wrapper).
     *
     * @param string|int|null $labelOrUserId Telegram user id (int) or legacy label (string)
     * @param string $transport 'http' → bot(), 'mtproto' → botMtproto()
     */
    public function botFromVault(string|int|null $labelOrUserId = null, string $transport = 'http', ...$overrides): BotClient|BotAccountScope
    {
        $account = $this->resolveVaultAccount($labelOrUserId);
        $creds = $account !== null ? $account->credentials() : [];

        if ($transport === 'mtproto') {
            return $this->botMtproto(
                botToken: $overrides['botToken'] ?? $creds['bot_token'] ?? null,
                session: $overrides['session'] ?? $creds['session'] ?? null,
                dcId: $overrides['dcId'] ?? $creds['dc_id'] ?? null,
                apiId: $overrides['apiId'] ?? $creds['api_id'] ?? null,
                apiHash: $overrides['apiHash'] ?? $creds['api_hash'] ?? null,
                proxyConfig: $overrides['proxyConfig'] ?? null,
            );
        }

        return $this->bot(
            botToken: $overrides['botToken'] ?? $creds['bot_token'] ?? null,
            proxyConfig: $overrides['proxyConfig'] ?? null,
        );
    }

    /**
     * Resolve a vault account by Telegram user id (int) or legacy label (string).
     * Null falls back to the env default account.
     */
    private function resolveVaultAccount(string|int|null $labelOrUserId): ?\MeRezaRezaei\Teleframe\Vault\TelegramAccount
    {
        $vault = $this->vault;
        if ($vault === null && function_exists('app')) {
            try {
                $vault = app(Vault::class);
            } catch (\Throwable) {
                $vault = null;
            }
        }

        if ($vault === null) {
            if ($labelOrUserId === null) {
                return null;
            }
            throw new RuntimeException("no vault bound: cannot resolve vault account '{$labelOrUserId}'.");
        }

        if ($labelOrUserId !== null) {
            // Numeric: look up by Telegram user id (canonical identity)
            if (is_int($labelOrUserId) || ctype_digit((string) $labelOrUserId)) {
                $userId = is_int($labelOrUserId) ? $labelOrUserId : (int) $labelOrUserId;
                $account = $vault->accountByUserId($userId);
                if ($account !== null) {
                    return $account;
                }
            }

            // Fallback: treat as label (legacy compat)
            $account = $vault->account((string) $labelOrUserId);
            if ($account === null) {
                throw new RuntimeException("unknown vault account '{$labelOrUserId}'.");
            }

            return $account;
        }

        return $vault->defaultAccount();
    }
}
