<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Auth;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\PhoneCodeException;
use MeRezaRezaei\Teleframe\Laravel\Exceptions\ResendLimitExceededException;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayCache;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The resend budget must follow the LOGIN, not the process.
 *
 * TeleframeAuthService is a container singleton, but a web caller (controller,
 * Livewire action) gets a fresh instance on every request. With the counter
 * living in the instance, request 2 got a fresh budget of 3 — unbounded
 * PHONE_NUMBER_FLOOD, exactly what the cap was added to prevent. Two tests
 * here are the regression guards: a fresh "request" (a fresh service over the
 * SAME PSR-16 store) does not get a fresh budget, and a send that dies after
 * reaching Telegram (transport failure) does not grant a free retry.
 */
final class ResendBudgetTest extends TestCase
{
    private const PHONE = '+989123456789';

    private const HASH = 'hash_from_send_code';

    private function service(ArrayCache $store): TeleframeAuthService
    {
        return new TeleframeAuthService(live: false, resendBudget: $store);
    }

    private function sentCode(): AuthScopeDouble
    {
        return new AuthScopeDouble([
            'auth.resendCode' => [['_' => 'auth.sentCode', 'phone_code_hash' => 'hash_2']],
        ]);
    }

    public function test_a_fresh_request_does_not_get_a_fresh_budget_of_three(): void
    {
        $store = new ArrayCache;
        $cap = TeleframeAuthService::MAX_RESENDS_PER_LOGIN;

        // "Request 1" — a service instance that spends the whole budget.
        $first = $this->service($store);
        for ($i = 0; $i < $cap; $i++) {
            $first->resendCode($this->sentCode(), self::PHONE, self::HASH);
        }
        self::assertSame(0, $first->resendsRemaining(self::HASH));

        // "Request 2" — a BRAND NEW service instance over the same store: the
        // budget must still be spent. Before the fix this was 3 again.
        $second = $this->service($store);
        self::assertSame($cap, $second->resendsUsed(self::HASH), 'the cap must survive the process boundary');
        self::assertSame(0, $second->resendsRemaining(self::HASH));

        $this->expectException(ResendLimitExceededException::class);
        $second->resendCode($this->sentCode(), self::PHONE, self::HASH);
    }

    public function test_a_failed_send_does_not_grant_a_free_retry(): void
    {
        $store = new ArrayCache;
        $auth = $this->service($store);

        // The call reached Telegram and the response was lost: a transport
        // failure says nothing about whether a code is now on its way, so the
        // attempt is spent. A retry must not be free.
        $broken = new AuthScopeDouble([
            'auth.resendCode' => [new RuntimeException('socket closed mid-response')],
        ]);

        try {
            $auth->resendCode($broken, self::PHONE, self::HASH);
            self::fail('the transport failure must propagate');
        } catch (RuntimeException $e) {
            self::assertSame('socket closed mid-response', $e->getMessage());
        }

        self::assertSame(1, $auth->resendsUsed(self::HASH), 'a lost response is not a refund');
        self::assertSame(
            TeleframeAuthService::MAX_RESENDS_PER_LOGIN - 1,
            $auth->resendsRemaining(self::HASH),
        );

        // And the spend survives a fresh instance on the same store.
        self::assertSame(1, $this->service($store)->resendsUsed(self::HASH));
    }

    public function test_a_telegram_refusal_of_the_hash_is_still_refunded(): void
    {
        $store = new ArrayCache;
        $auth = $this->service($store);

        // PHONE_CODE_HASH_EMPTY proves no code was sent, so the pre-spent
        // resend goes back and a later honest attempt still has budget.
        $refused = new AuthScopeDouble([
            'auth.resendCode' => [
                new PhoneCodeException('PHONE_CODE_HASH_EMPTY', 400, 'auth.resendCode'),
            ],
        ]);

        $res = $auth->resendCode($refused, self::PHONE, self::HASH);

        self::assertSame('restart_required', $res['status']);
        self::assertSame(0, $auth->resendsUsed(self::HASH));
        self::assertSame(0, $this->service($store)->resendsUsed(self::HASH));
    }

    public function test_the_budget_key_never_contains_the_phone_code_hash(): void
    {
        $store = new ArrayCache;
        $this->service($store)->resendCode($this->sentCode(), self::PHONE, self::HASH);

        $keys = [];
        foreach ($store->all() as $key => $_value) {
            $keys[] = $key;
        }

        self::assertNotEmpty($keys);
        foreach ($keys as $key) {
            self::assertStringStartsWith(TeleframeAuthService::RESEND_KEY_PREFIX, $key);
            self::assertStringNotContainsString(self::HASH, $key, 'a live login credential must not be a cache key');
        }
    }

    public function test_a_fresh_login_starts_a_fresh_budget(): void
    {
        $store = new ArrayCache;
        $cap = TeleframeAuthService::MAX_RESENDS_PER_LOGIN;
        $auth = $this->service($store);
        for ($i = 0; $i < $cap; $i++) {
            $auth->resendCode($this->sentCode(), self::PHONE, self::HASH);
        }

        // A brand new auth.sendCode = a brand new phone_code_hash = the honest
        // restart the cap message recommends.
        $res = $auth->resendCode($this->sentCode(), self::PHONE, 'a_different_hash');

        self::assertSame('sent', $res['status']);
        self::assertSame(1, $auth->resendsUsed('a_different_hash'));
    }

    public function test_without_a_psr16_store_the_budget_is_still_enforced_in_process(): void
    {
        $auth = new TeleframeAuthService(live: false); // plain PHP / standalone smoke
        $cap = TeleframeAuthService::MAX_RESENDS_PER_LOGIN;

        for ($i = 0; $i < $cap; $i++) {
            $auth->resendCode($this->sentCode(), self::PHONE, self::HASH);
        }

        self::assertSame($cap, $auth->resendsUsed());
        self::assertSame(0, $auth->resendsRemaining());

        $this->expectException(ResendLimitExceededException::class);
        $auth->resendCode($this->sentCode(), self::PHONE, self::HASH);
    }
}
