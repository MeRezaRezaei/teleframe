<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\MTProto\Client as MTProtoClient;
use MeRezaRezaei\Teleframe\Core\MTProto\SessionData;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportRefusal;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Passport\UserScopePassportWire;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;

/**
 * UserScopePassportWire — the live half of the wire seam, and MemoryPassportWire
 * — the offline half. Together they are the reason the whole passport wire path
 * is testable without a socket, a session string, or a network.
 *
 * Two things must hold, and both are load-bearing:
 *  1. a wire call really reaches `UserAccountScope::call()` with the documented
 *     method and params (so the seed addition and the encoder are on the real
 *     path, not a mock of it);
 *  2. a refusal is re-thrown AS ITSELF — `PASSWORD_REQUIRED` must still be
 *     classifiable after crossing the seam, or the 2FA route is dead code.
 */
final class UserScopePassportWireTest extends TestCase
{
    public function test_a_call_reaches_the_live_scope_with_the_documented_params(): void
    {
        $scope = $this->recordingScope();
        $wire = new UserScopePassportWire($scope);

        $wire->call('auth.importAuthorization', ['id' => 5, 'bytes' => 'b']);

        self::assertCount(1, $scope->calls);
        self::assertSame('auth.importAuthorization', $scope->calls[0]['method']);
        self::assertSame(['id' => 5, 'bytes' => 'b'], $scope->calls[0]['params']);
    }

    public function test_no_scope_means_no_dispatch_and_a_loud_refusal(): void
    {
        $wire = new UserScopePassportWire;

        try {
            $wire->call('auth.importAuthorization', ['id' => 1, 'bytes' => 'b']);
            self::fail('an unbound scope must refuse loud, never no-op');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no scope, no dispatch', $e->getMessage());
            self::assertStringContainsString(PassportWireContract::METHOD_IMPORT_AUTHORIZATION, $e->getMessage());
        }
    }

    public function test_a_refusal_crosses_the_seam_untouched_and_stays_classifiable(): void
    {
        $refusal = new RpcErrorException('PASSWORD_REQUIRED', 400);
        $scope = $this->recordingScope($refusal);
        $logger = new ArrayLogger;

        try {
            (new UserScopePassportWire($scope, $logger))->call('account.acceptAuthorization', []);
            self::fail('the refusal must not be swallowed or flattened');
        } catch (RpcErrorException $e) {
            self::assertSame('PASSWORD_REQUIRED', $e->rpcErrorMessage);
            self::assertTrue(PassportRefusal::passwordRequired($e), 'the 2FA route depends on this surviving');
        }

        self::assertStringContainsString('account.acceptAuthorization', (string) json_encode($logger->records));
    }

    public function test_the_offline_seam_records_dispatches_and_refuses_unscripted_methods(): void
    {
        $wire = new MemoryPassportWire([
            'help.getPassportConfig' => [
                ['_' => 'help.passportConfig', 'hash' => 1, 'countries_langs' => '[["US","en"]]'],
                ['_' => 'help.passportConfigNotModified', 'hash' => 1],
            ],
        ]);

        $wire->call('help.getPassportConfig', ['hash' => 0]);
        $wire->call('help.getPassportConfig', ['hash' => 1]);

        self::assertSame(['help.getPassportConfig', 'help.getPassportConfig'], $wire->methodsCalled());
        self::assertSame(2, $wire->dispatchCount());

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/no scripted answer/');
        $wire->call('auth.exportAuthorization', ['dc_id' => 0]);
    }

    public function test_the_offline_seam_refuses_a_thrown_scripted_step(): void
    {
        $refusal = new RpcErrorException('PASSWORD_REQUIRED', 400);
        $wire = new MemoryPassportWire(['auth.importAuthorization' => [$refusal]]);

        $this->expectException(RpcErrorException::class);
        $wire->call('auth.importAuthorization', ['id' => 1, 'bytes' => 'b']);
    }

    public function test_the_offline_seam_refuses_an_exhausted_script(): void
    {
        $wire = new MemoryPassportWire(['auth.importAuthorization' => []]);

        $this->expectException(PassportLoginException::class);
        $this->expectExceptionMessageMatches('/exhausted/');
        $wire->call('auth.importAuthorization', ['id' => 1, 'bytes' => 'b']);
    }

    /**
     * A scope that records its calls and answers from a script, without ever
     * touching a transport (the FakeUserScope pattern).
     *
     * @param  array<string, mixed>|\Throwable|null  $answer
     */
    private function recordingScope(array|\Throwable|null $answer = null): UserAccountScope
    {
        $scope = new class($answer) extends UserAccountScope
        {
            /** @var list<array{method: string, params: array<string, mixed>}> */
            public array $calls = [];

            public function __construct(private readonly array|\Throwable|null $answer)
            {
                parent::__construct(
                    new MTProtoClient(apiId: 1, apiHash: 'x', session: new SessionData(dcId: 2, authKey: 'k', userId: 42)),
                    new SessionData(dcId: 2, authKey: 'k', userId: 42),
                );
            }

            public function call(string $method, array $params = []): array
            {
                $this->calls[] = ['method' => $method, 'params' => $params];

                if ($this->answer instanceof \Throwable) {
                    throw $this->answer;
                }

                return is_array($this->answer) ? $this->answer : ['_' => 'ok', 'method' => $method];
            }
        };

        return $scope;
    }
}
