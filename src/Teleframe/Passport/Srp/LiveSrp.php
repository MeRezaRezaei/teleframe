<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport\Srp;

use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmerContract;
use Psr\Log\LoggerInterface;

/**
 * LiveSrp — the live SRP 2FA confirm over Telegram's 227-callable slice:
 *
 *   account.getPassword → UserAccountScope::mtproto->compute2faProof
 *   → auth.checkPassword (inputCheckPasswordSRP)
 *
 * Composes the same chain as `TeleframeAuthService::check2faPassword` and the
 * shipped `PasswordConfirmer`. The scope is constructor-injected (tests fake
 * it); a null scope throws loud on first use rather than silently skipping —
 * the inert posture. Implements `PasswordConfirmerContract` so it can stand in
 * for the shipped confirmer where a live-wire SRP confirm is wanted.
 *
 * Plain-PHP constructible: no container, facade, or config() calls.
 */
final class LiveSrp implements PasswordConfirmerContract
{
    /**
     * @param  UserAccountScope|null  $scope  the live user scope (null ⇒ loud on use)
     * @param  LoggerInterface|null  $logger  PSR-3, silent when unset
     */
    public function __construct(
        private readonly ?UserAccountScope $scope = null,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @return array<string, mixed> the auth.checkPassword result
     *
     * @throws PassportLoginException when no live scope is injected, or when
     *                                any step of the chain fails
     */
    public function confirm(string $password): array
    {
        $scope = $this->scope;
        if ($scope === null) {
            throw new PassportLoginException('live SRP scope required (inject a UserAccountScope)');
        }

        try {
            $passwordInfo = $scope->call('account.getPassword');
            $srpProof = $scope->mtproto->compute2faProof($passwordInfo, $password);

            $res = $scope->call('auth.checkPassword', [
                'password' => array_merge(['_' => 'inputCheckPasswordSRP'], $srpProof),
            ]);

            if (isset($res['user']['id']) && $scope->mtproto->session !== null) {
                $scope->mtproto->session->userId = (int) $res['user']['id'];
                $scope->session->userId = (int) $res['user']['id'];
            }

            return $res;
        } catch (PassportLoginException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($this->logger !== null) {
                $this->logger->error(
                    'LiveSrp: SRP confirm failed (account.getPassword -> compute2faProof -> auth.checkPassword)',
                    ['chain' => ['account.getPassword', 'auth.checkPassword']]
                );
            }
            throw new PassportLoginException('LiveSrp: SRP confirm failed on the live wire', $e);
        }
    }
}
