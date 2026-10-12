<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use Psr\Log\LoggerInterface;

/**
 * UserScopePassportWire (Cycle 18 W3) — the LIVE implementation of the wire
 * seam: it forwards the documented passport methods to a
 * `UserAccountScope::call()`, which is the shipped path down to
 * `EncryptedConnection::runCall`.
 *
 * The scope is constructor-injected (PasswordConfirmer precedent), so nothing
 * here resolves a container and nothing dials at construction. A null scope
 * throws LOUD on the first call — "no scope, no dispatch" — never a silent
 * no-op: a passport login that quietly does nothing is the exact failure this
 * whole cycle exists to kill.
 *
 * Refusals are re-thrown AS THEMSELVES, never wrapped. `RpcErrorException`
 * carries `rpcErrorMessage` (`PASSWORD_REQUIRED`, `AUTH_BYTES_INVALID`, …)
 * and `rpcErrorCode`, and PassportRefusal reads them to route a 2FA demand to
 * the PasswordConfirmer. Wrapping here would erase exactly the signal the
 * routing needs. The PSR-3 error line names the METHOD only — never the
 * params, the authorization bytes, or a key.
 *
 * Zero-regex; no container, no facade, no config().
 */
final class UserScopePassportWire implements PassportWireContract
{
    /**
     * @param  UserAccountScope|null  $scope  the live user scope (null until a host binds one)
     * @param  LoggerInterface|null  $logger  PSR-3, silent (NullLogger) when unset
     */
    public function __construct(
        private readonly ?UserAccountScope $scope = null,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws PassportLoginException when no live scope is injected
     */
    public function call(string $method, array $params = []): array
    {
        $scope = $this->scope;
        if ($scope === null) {
            throw new PassportLoginException(
                'passport wire call "'.$method.'" needs a live UserAccountScope'
                .' (bind '.PassportSupport::SCOPE_KEY.') — no scope, no dispatch'
            );
        }

        try {
            return $scope->call($method, $params);
        } catch (PassportLoginException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($this->logger !== null) {
                $this->logger->error(
                    'passport wire call "'.$method.'" refused',
                    [
                        'method' => $method,
                        'exception' => $e::class,
                        'rpc_error' => $e instanceof RpcErrorException ? $e->rpcErrorMessage : '',
                        'rpc_code' => $e instanceof RpcErrorException ? $e->rpcErrorCode : 0,
                    ]
                );
            }

            // Rethrown unwrapped: the typed refusal IS the routing signal.
            throw $e;
        }
    }
}
