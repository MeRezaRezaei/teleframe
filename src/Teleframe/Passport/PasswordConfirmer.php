<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use MeRezaRezaei\Teleframe\Core\Services\UserAccountScope;
use Psr\Log\LoggerInterface;

/**
 * PasswordConfirmer (design §2/§4/§9, spec §3/§5 G5): the SRP 2FA
 * confirm seam composing the shipped chain EXACTLY as
 * TeleframeAuthService::check2faPassword (src/Laravel/Services/
 * TeleframeAuthService.php:151-166) —
 *
 *   account.getPassword → UserAccountScope::mtproto->compute2faProof
 *   → auth.checkPassword (inputCheckPasswordSRP)
 *
 * — all callable on the 227 wire today (design §5). The scope is
 * constructor-injected (tests fake it; the design's "live scope" is a
 * wave-2, live-gated item): a null scope throws loud on the first use —
 * 'live scope required (wave 2; inject in tests)' — the inert-posture
 * gate, never a silent skip.
 *
 * Failure contract (AGENTS.md logger seam, design §4/§11): any Throwable
 * from the chain is logged at error level with method names only (never
 * the password, never payloads) and ALWAYS rethrown wrapped as a
 * PassportLoginException with the original as previous — logging never
 * swallows, the host owns retry/policy.
 *
 * The scope's own methods mirror TeleframeAuthService exactly, including
 * the session userId assignment on success. Zero facades, zero regex.
 *
 * Implements PasswordConfirmerContract (added 2026-09-25, Cycle 18) so the
 * wire login can ROUTE a `PASSWORD_REQUIRED` refusal to this exact chain
 * while remaining testable offline. The role is unchanged: this class is the
 * SRP 2FA confirm and nothing else.
 */
final class PasswordConfirmer implements PasswordConfirmerContract
{
    /**
     * @param  UserAccountScope|null  $scope  the live user scope (null until wave 2)
     * @param  LoggerInterface|null  $logger  PSR-3, silent (NullLogger) when unset
     */
    public function __construct(
        private readonly ?UserAccountScope $scope = null,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Re-confirm the user's 2FA cloud password over the SRP chain.
     *
     * @return array<string, mixed> the auth.checkPassword result
     *
     * @throws PassportLoginException when no live scope is injected, or
     *                                when any step of the chain fails (previous = the cause)
     */
    public function confirm(string $password): array
    {
        $scope = $this->scope;
        if ($scope === null) {
            throw new PassportLoginException('live scope required (wave 2; inject in tests)');
        }

        try {
            // Mirrors TeleframeAuthService::check2faPassword exactly
            // (:153-166) — the shipped chain, untouched.
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
            throw $e; // already a passport-domain failure — never double-wrap
        } catch (\Throwable $e) {
            if ($this->logger !== null) {
                $this->logger->error(
                    'PasswordConfirmer: SRP 2FA confirm failed '
                    .'(account.getPassword -> compute2faProof -> auth.checkPassword)',
                    ['chain' => ['account.getPassword', 'auth.checkPassword']]
                );
            }
            throw new PassportLoginException(
                'PasswordConfirmer: SRP 2FA confirm failed on the live wire',
                $e
            );
        }
    }
}
