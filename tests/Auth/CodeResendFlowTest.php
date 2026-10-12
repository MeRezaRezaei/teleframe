<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Auth;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\PhoneCodeException;
use MeRezaRezaei\Teleframe\Laravel\Exceptions\ResendLimitExceededException;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use PHPUnit\Framework\TestCase;

/**
 * The stale-login-code dead end: a PHONE_CODE_EXPIRED used to escape raw with
 * a hint nobody could act on, and the only cure (auth.resendCode) was never
 * wired. Now it is an outcome, resending is a single explicit consented call,
 * and the resends are capped so no loop can burn a real account's rate budget.
 */
final class CodeResendFlowTest extends TestCase
{
    private const PHONE = '+989123456789';

    private const HASH = 'hash_from_send_code';

    private function service(): TeleframeAuthService
    {
        return new TeleframeAuthService(live: false);
    }

    private function phoneCode(string $error, int $code = 400): PhoneCodeException
    {
        return new PhoneCodeException($error, $code, 'auth.signIn');
    }

    public function test_an_expired_code_produces_the_code_expired_outcome_not_a_raw_exception(): void
    {
        $scope = new AuthScopeDouble([
            'auth.signIn' => [$this->phoneCode('PHONE_CODE_EXPIRED', 400)],
        ]);

        $outcome = $this->service()->attemptCodeSignIn($scope, self::PHONE, self::HASH, '12345');

        self::assertSame('code_expired', $outcome['status']);
        self::assertSame(TeleframeAuthService::MAX_RESENDS_PER_LOGIN, $outcome['resends_remaining']);
        self::assertStringContainsString('resendCode', (string) $outcome['error']);
        self::assertSame(0, $scope->countCalls('auth.resendCode'), 'a stale code must never auto-resend.');
    }

    public function test_a_wrong_code_is_rejected_without_burning_a_resend(): void
    {
        $scope = new AuthScopeDouble([
            'auth.signIn' => [$this->phoneCode('PHONE_CODE_INVALID')],
        ]);
        $auth = $this->service();

        $outcome = $auth->attemptCodeSignIn($scope, self::PHONE, self::HASH, '00000');

        self::assertSame('code_rejected', $outcome['status']);
        self::assertSame(0, $auth->resendsUsed());
        self::assertSame(TeleframeAuthService::MAX_RESENDS_PER_LOGIN, $auth->resendsRemaining());
        self::assertSame(0, $scope->countCalls('auth.resendCode'));
    }

    public function test_a_consented_resend_calls_auth_resend_code_exactly_once_and_returns_the_fresh_hash(): void
    {
        $scope = new AuthScopeDouble([
            'auth.resendCode' => [['_' => 'auth.sentCode', 'phone_code_hash' => 'hash_2']],
        ]);
        $auth = $this->service();

        $res = $auth->resendCode($scope, self::PHONE, self::HASH);

        self::assertSame('sent', $res['status']);
        self::assertSame('hash_2', $res['phone_code_hash']);
        self::assertSame(1, $auth->resendsUsed());
        self::assertSame(TeleframeAuthService::MAX_RESENDS_PER_LOGIN - 1, $auth->resendsRemaining());
        self::assertSame(1, $scope->countCalls('auth.resendCode'), 'one consented resend = exactly one wire call.');
        // Schema parameter names for auth.resendCode (methods-mtproto.json).
        self::assertSame(
            ['phone_number' => self::PHONE, 'phone_code_hash' => self::HASH],
            $scope->paramsOf('auth.resendCode')
        );
    }

    public function test_resends_beyond_the_cap_are_refused_without_touching_the_wire(): void
    {
        $cap = TeleframeAuthService::MAX_RESENDS_PER_LOGIN;
        $scope = new AuthScopeDouble;
        $auth = $this->service();

        for ($i = 0; $i < $cap; $i++) {
            $auth->resendCode($scope, self::PHONE, self::HASH);
        }

        self::assertSame($cap, $auth->resendsUsed());
        self::assertSame(0, $auth->resendsRemaining());

        $this->expectException(ResendLimitExceededException::class);
        try {
            $auth->resendCode($scope, self::PHONE, self::HASH);
        } finally {
            self::assertSame($cap, $scope->countCalls('auth.resendCode'), 'the refused resend must not reach Telegram.');
        }
    }

    public function test_the_cap_refusal_names_the_restart_instead_of_looping(): void
    {
        $cap = TeleframeAuthService::MAX_RESENDS_PER_LOGIN;
        $auth = $this->service();
        $scope = new AuthScopeDouble;
        for ($i = 0; $i < $cap; $i++) {
            $auth->resendCode($scope, self::PHONE, self::HASH);
        }

        $e = new ResendLimitExceededException($auth->resendsUsed(), $cap);
        self::assertStringContainsString('auth.sendCode', $e->getMessage());
        self::assertStringContainsString((string) $cap, $e->getMessage());
    }

    public function test_a_flood_wait_surfaces_the_waited_seconds_instead_of_escaping_raw(): void
    {
        $scope = new AuthScopeDouble([
            'auth.signIn' => [new FloodWaitException(137, 'FLOOD_WAIT_137', 420)],
        ]);

        $outcome = $this->service()->attemptCodeSignIn($scope, self::PHONE, self::HASH, '12345');

        self::assertSame('flood_wait', $outcome['status']);
        self::assertSame(137, $outcome['seconds']);
    }

    public function test_a_flood_wait_on_the_resend_itself_surfaces_to_the_caller(): void
    {
        $scope = new AuthScopeDouble([
            'auth.resendCode' => [new FloodWaitException(60, 'FLOOD_WAIT_60', 420)],
        ]);

        $this->expectException(FloodWaitException::class);
        $this->service()->resendCode($scope, self::PHONE, self::HASH);
    }

    public function test_a_telegram_refused_resend_asks_for_a_restart_and_spends_no_resend(): void
    {
        $scope = new AuthScopeDouble([
            // auth.resendCode's own documented errors include PHONE_CODE_EXPIRED
            // and PHONE_CODE_HASH_EMPTY: the hash is gone, no resend can help.
            'auth.resendCode' => [new PhoneCodeException('PHONE_CODE_HASH_EMPTY', 400, 'auth.resendCode')],
        ]);
        $auth = $this->service();

        $res = $auth->resendCode($scope, self::PHONE, self::HASH);

        self::assertSame('restart_required', $res['status']);
        self::assertSame(0, $auth->resendsUsed(), 'a refused resend must not consume the budget.');
    }

    public function test_a_successful_sign_in_still_works(): void
    {
        $scope = new AuthScopeDouble([
            'auth.signIn' => [['_' => 'auth.authorization', 'user' => ['id' => 777]]],
        ]);
        $auth = $this->service();

        $outcome = $auth->attemptCodeSignIn($scope, self::PHONE, self::HASH, '54321');

        self::assertSame('signed_in', $outcome['status']);
        self::assertSame(777, $outcome['authorization']['user']['id']);
        self::assertSame(777, $scope->session->userId);
        self::assertSame(0, $auth->resendsUsed());
        self::assertSame(0, $scope->countCalls('auth.resendCode'));
    }

    public function test_the_whole_expired_code_recovery_is_two_wire_calls_the_operator_asks_for(): void
    {
        $scope = new AuthScopeDouble([
            'auth.signIn' => [$this->phoneCode('PHONE_CODE_EXPIRED'), ['_' => 'auth.authorization', 'user' => ['id' => 9]]],
            'auth.resendCode' => [['_' => 'auth.sentCode', 'phone_code_hash' => 'hash_2']],
        ]);
        $auth = $this->service();

        $first = $auth->attemptCodeSignIn($scope, self::PHONE, self::HASH, '12345');
        self::assertSame('code_expired', $first['status']);

        // the operator says yes:
        $resend = $auth->resendCode($scope, self::PHONE, self::HASH);
        $second = $auth->attemptCodeSignIn($scope, self::PHONE, (string) $resend['phone_code_hash'], '12345');

        self::assertSame('signed_in', $second['status']);
        self::assertSame(1, $scope->countCalls('auth.resendCode'));
        self::assertSame(1, $auth->resendsUsed());
        self::assertSame(9, $second['authorization']['user']['id']);
    }

    public function test_the_phone_code_exception_knows_which_verbs_recover(): void
    {
        self::assertTrue((new PhoneCodeException('PHONE_CODE_EXPIRED', 400))->isExpired());
        self::assertFalse((new PhoneCodeException('PHONE_CODE_EMPTY', 400))->isExpired());
        self::assertTrue((new PhoneCodeException('PHONE_CODE_EMPTY', 400))->isEmpty());
        self::assertFalse((new PhoneCodeException('PHONE_CODE_INVALID', 400))->isExpired());
    }
}
