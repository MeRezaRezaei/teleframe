<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use PHPUnit\Framework\TestCase;

/**
 * PHONE_MIGRATE_x handling: auth.sendCode must be repeated against the DC
 * Telegram reports, with a fresh per-DC auth key, so the rest of the login
 * flow continues on the migrated DC.
 *
 * @see https://core.telegram.org/api/datacenter
 */
final class TeleframeAuthServiceTest extends TestCase
{
    public function test_send_phone_code_retries_once_at_the_migrated_dc(): void
    {
        $client = new MigratingTeleframeClient;
        $auth = new TeleframeAuthService($client, live: false);

        $res = $auth->sendPhoneCode('+1234567890', 12345, 'hash');

        $this->assertSame('migrated_hash', $res['phone_code_hash']);
        $this->assertSame(1, $res['session']->dcId, 'The returned session must be bound to the migrated DC.');
        $this->assertInstanceOf(SessionData::class, $res['session']);
        $this->assertSame(2, $client->attempts, 'One PHONE_MIGRATE hop + one successful repeat.');
    }

    public function test_send_phone_code_keeps_the_requested_dc_without_migration(): void
    {
        $client = new StaticTeleframeClient;
        $auth = new TeleframeAuthService($client, live: false);

        $res = $auth->sendPhoneCode('+1234567890', 12345, 'hash', dcId: 3);

        $this->assertSame('static_hash', $res['phone_code_hash']);
        $this->assertSame(3, $res['session']->dcId);
        $this->assertSame(1, $client->attempts);
    }
}

/** TeleframeClient whose user() never leaves the process. */
final class MigratingTeleframeClient extends TeleframeClient
{
    public int $attempts = 0;

    public function user(
        ?int $accountId = null,
        string|SessionData|null $session = null,
        ?int $dcId = null,
        ?int $apiId = null,
        ?string $apiHash = null,
        ?array $proxyConfig = null
    ): UserAccountScope {
        $this->attempts++;
        $sessionData = $session instanceof SessionData ? $session : new SessionData(dcId: $dcId ?? 2, authKey: '');
        $mtproto = new class($sessionData) extends MTProtoClient
        {
            public int $attempts = 0;

            public function __construct(public SessionData $stubSession)
            {
                parent::__construct(apiId: 12345, apiHash: 'hash', session: $stubSession);
            }

            public function live(): static
            {
                return $this;
            }

            public function call(string $method, array $params = []): array
            {
                $this->attempts++;

                if ($this->stubSession->dcId === 2) {
                    throw new DcMigrationException(dcId: 1);
                }

                return ['phone_code_hash' => 'migrated_hash', '_' => 'auth.sentCode'];
            }
        };

        return new UserAccountScope($mtproto, $sessionData);
    }
}

/** TeleframeClient that answers auth.sendCode on any DC without migrating. */
final class StaticTeleframeClient extends TeleframeClient
{
    public int $attempts = 0;

    public function user(
        ?int $accountId = null,
        string|SessionData|null $session = null,
        ?int $dcId = null,
        ?int $apiId = null,
        ?string $apiHash = null,
        ?array $proxyConfig = null
    ): UserAccountScope {
        $this->attempts++;
        $sessionData = $session instanceof SessionData ? $session : new SessionData(dcId: $dcId ?? 2, authKey: '');
        $mtproto = new class($sessionData) extends MTProtoClient
        {
            public int $attempts = 0;

            public function __construct(public SessionData $stubSession)
            {
                parent::__construct(apiId: 12345, apiHash: 'hash', session: $stubSession);
            }

            public function live(): static
            {
                return $this;
            }

            public function call(string $method, array $params = []): array
            {
                $this->attempts++;

                return ['phone_code_hash' => 'static_hash', '_' => 'auth.sentCode'];
            }
        };

        return new UserAccountScope($mtproto, $sessionData);
    }
}
