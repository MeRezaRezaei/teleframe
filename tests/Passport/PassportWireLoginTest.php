<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Closure;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Passport\AuthorizationImporter;
use MeRezaRezaei\Teleframe\Passport\LoginResult;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportWireContract;
use MeRezaRezaei\Teleframe\Passport\PassportWireLogin;
use MeRezaRezaei\Teleframe\Passport\PasswordConfirmerContract;
use MeRezaRezaei\Teleframe\Passport\PasswordRequiredException;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * PassportWireLogin — the orchestrator that makes the user's ask real: a
 * Telegram `auth.Authorization` becomes a BOUND LOGIN.
 *
 * The three pins this cycle exists to nail:
 *  (a) the import really dispatches `auth.importAuthorization#a57a7dad`
 *      (the official id is pinned in AuthorizationImporterTest);
 *  (b) a `PASSWORD_REQUIRED` refusal is ROUTED to the existing 2FA confirmer —
 *      the chain runs, the import is retried once, and the login completes; a
 *      refusal that cannot be routed becomes the NAMED
 *      PasswordRequiredException, never a swallowed error;
 *  (c) an UNCONFIGURED module dispatches NOTHING at all — not even one method
 *      reaches the wire seam.
 */
final class PassportWireLoginTest extends TestCase
{
    public function test_a_configured_module_turns_an_authorization_into_a_bound_login(): void
    {
        $wire = $this->wireWith([[
            '_' => 'auth.authorization',
            'flags' => 0,
            'user' => ['_' => 'user', 'id' => 4242, 'first_name' => 'Ada'],
        ]]);
        $writer = new MemoryBindingWriter;
        $ridden = [];

        $result = $this->login($wire, $writer, onLogin: static function ($user) use (&$ridden): void {
            $ridden[] = $user;
        })->login(77, 'blob-bytes', new FakePassportUser);

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame(4242, $result->tgUserId);
        self::assertSame(7, $result->accountId);
        self::assertSame([], $result->fieldsVerified, 'an authorization import proves WHO, not which passport fields');
        self::assertSame(4242, $writer->last()['tg_id'] ?? null);
        self::assertCount(1, $ridden, 'the host auth hook rides exactly once');
        self::assertSame(['auth.importAuthorization'], $wire->methodsCalled());
    }

    public function test_the_dispatch_carries_the_official_method_and_two_params(): void
    {
        $wire = $this->wireWith([[
            '_' => 'auth.authorization',
            'user' => ['id' => 11],
        ]]);

        $this->login($wire)->login(31, 'the-bytes', new FakePassportUser);

        self::assertSame('a57a7dad', PassportWireContract::OFFICIAL_IDS['auth.importAuthorization']);
        $dispatch = $wire->firstDispatchOf('auth.importAuthorization');
        self::assertNotNull($dispatch);
        self::assertSame('auth.importAuthorization', $dispatch['method']);
        self::assertSame(['id' => 31, 'bytes' => 'the-bytes'], $dispatch['params']);
    }

    /**
     * (b) THE 2FA ROUTE: Telegram answers PASSWORD_REQUIRED, the shipped 2FA
     * confirmer runs, the import is retried ONCE, the login completes.
     */
    public function test_password_required_is_routed_to_the_confirmer_and_retried_once(): void
    {
        $wire = $this->wireWith([
            new RpcErrorException('PASSWORD_REQUIRED', 400, 'A 2FA password must be configured to use Telegram Passport.'),
            ['_' => 'auth.authorization', 'user' => ['id' => 555]],
        ]);
        $writer = new MemoryBindingWriter;
        $confirmer = new RecordingConfirmer;
        $logger = new ArrayLogger;

        $result = $this->login($wire, $writer, $confirmer, $logger)
            ->login(9, 'blob', new FakePassportUser, 'the-2fa-password');

        self::assertSame(555, $result->tgUserId);
        self::assertSame(['the-2fa-password'], $confirmer->seen(), 'the 2FA confirmer was never reached');
        self::assertSame(2, $wire->dispatchCount(), 'the import was retried exactly once');
        self::assertSame(555, $writer->last()['tg_id'] ?? null);

        // The 2FA password must never reach the log.
        self::assertStringNotContainsString('the-2fa-password', $this->dump($logger));
    }

    public function test_password_required_without_a_password_is_named_never_swallowed(): void
    {
        $wire = $this->wireWith([new RpcErrorException('PASSWORD_REQUIRED', 400)]);
        $logger = new ArrayLogger;

        try {
            $this->login($wire, confirmer: new RecordingConfirmer, logger: $logger)
                ->login(9, 'blob', new FakePassportUser);
            self::fail('a PASSWORD_REQUIRED with no password must be named, not dropped');
        } catch (PasswordRequiredException $e) {
            self::assertStringContainsString('PASSWORD_REQUIRED', $e->getMessage());
            self::assertStringContainsString('2FA cloud password is required', $e->getMessage());
            self::assertInstanceOf(PassportLoginException::class, $e, 'the host still catches ONE base type');
        }

        self::assertSame(1, $wire->dispatchCount(), 'no retry without a password');
        self::assertNotSame([], $logger->records, 'the refusal is PSR-3 logged');
    }

    public function test_password_required_without_a_confirmer_seam_is_named(): void
    {
        $wire = $this->wireWith([new RpcErrorException('PASSWORD_REQUIRED', 400)]);

        $this->expectException(PasswordRequiredException::class);
        $this->expectExceptionMessageMatches('/no .*PasswordConfirmer.* seam is bound/');
        $this->login($wire)->login(9, 'blob', new FakePassportUser, 'pw');
    }

    public function test_a_still_refused_import_after_2fa_is_named(): void
    {
        $refusal = new RpcErrorException('PASSWORD_REQUIRED', 400);
        $wire = $this->wireWith([$refusal, $refusal]);

        $this->expectException(PasswordRequiredException::class);
        $this->expectExceptionMessageMatches('/still refuses/');
        $this->login($wire, confirmer: new RecordingConfirmer)
            ->login(9, 'blob', new FakePassportUser, 'pw');
    }

    /**
     * (c) THE OPT-IN GATE: unconfigured ⇒ NO dispatch at all. Not "the call
     * fails" — nothing ever reaches the wire seam.
     */
    public function test_an_unconfigured_module_dispatches_nothing(): void
    {
        $wire = $this->wireWith([['_' => 'auth.authorization', 'user' => ['id' => 1]]]);
        $writer = new MemoryBindingWriter;
        $ridden = false;

        $login = new PassportWireLogin(
            config: new PassportConfig(['enabled' => false, 'account_id' => 7]),
            importer: new AuthorizationImporter($wire),
            writer: $writer,
            confirmer: null,
            logger: null,
            onLogin: static function () use (&$ridden): void {
                $ridden = true;
            },
        );

        try {
            $login->login(1, 'blob', new FakePassportUser);
            self::fail('an unconfigured module must refuse loud');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('passport login not enabled', $e->getMessage());
        }

        self::assertSame(0, $wire->dispatchCount(), 'an unconfigured module must make NO telegram call');
        self::assertSame([], $wire->methodsCalled());
        self::assertNull($writer->last(), 'nothing may be bound when the module is off');
        self::assertFalse($ridden, 'the auth hook must not ride when the module is off');
    }

    /**
     * A refusal that is NOT a 2FA demand is rethrown, not retried, not
     * swallowed. It reaches the host as a PassportLoginException (the module's
     * ONE base type, as the webhook path does) whose `previous` is the original
     * RpcErrorException — so the typed refusal is still reachable — and whose
     * message carries Telegram's own wording. The PSR-3 line carries the bare
     * token, never the authorization bytes.
     */
    public function test_a_non_2fa_refusal_is_rethrown_untouched_and_logged(): void
    {
        $wire = $this->wireWith([
            new RpcErrorException('AUTH_BYTES_INVALID', 400, 'The provided authorization is invalid.'),
        ]);
        $writer = new MemoryBindingWriter;
        $logger = new ArrayLogger;

        try {
            $this->login($wire, $writer, logger: $logger)->login(9, 'blob', new FakePassportUser);
            self::fail('a non-2fa refusal must not be swallowed');
        } catch (PassportLoginException $e) {
            self::assertInstanceOf(RpcErrorException::class, $e->getPrevious(), 'the typed refusal stays reachable');
            self::assertSame('AUTH_BYTES_INVALID', $e->getPrevious()->rpcErrorMessage);
            self::assertStringContainsString('The provided authorization is invalid.', $e->getMessage());
        }

        self::assertSame(1, $wire->dispatchCount(), 'a non-2fa refusal is not retried');
        self::assertNull($writer->last());
        $dumped = $this->dump($logger);
        self::assertStringContainsString('PassportWireLogin::login failed', $dumped);
        self::assertStringContainsString('AUTH_BYTES_INVALID', $dumped);
        self::assertStringNotContainsString('blob', $dumped, 'the authorization bytes are never logged');
    }

    public function test_the_inert_default_config_builds_a_working_orchestrator(): void
    {
        $wire = $this->wireWith([['_' => 'auth.authorization', 'user' => ['id' => 3]]]);
        $login = $this->login($wire, config: new PassportConfig([]));

        self::assertInstanceOf(PassportWireLogin::class, $login);
        self::assertSame(0, $wire->dispatchCount(), 'construction alone dispatches nothing');
    }

    /**
     * @param  list<array<string, mixed>|\Throwable>  $steps
     */
    private function wireWith(array $steps): MemoryPassportWire
    {
        return new MemoryPassportWire([
            PassportWireContract::METHOD_IMPORT_AUTHORIZATION => $steps,
        ]);
    }

    private function login(
        MemoryPassportWire $wire,
        ?MemoryBindingWriter $writer = null,
        ?PasswordConfirmerContract $confirmer = null,
        ?LoggerInterface $logger = null,
        ?Closure $onLogin = null,
        ?PassportConfig $config = null,
    ): PassportWireLogin {
        return new PassportWireLogin(
            config: $config ?? new PassportConfig(['enabled' => true, 'account_id' => 7]),
            importer: new AuthorizationImporter($wire, $logger),
            writer: $writer ?? new MemoryBindingWriter,
            confirmer: $confirmer,
            logger: $logger,
            onLogin: $onLogin,
        );
    }

    /** The whole log as one string, for a "must not contain" assertion. */
    private function dump(ArrayLogger $logger): string
    {
        $parts = [];
        foreach ($logger->records as $record) {
            $parts[] = $record['level'].' '.$record['message'].' '.(string) json_encode($record['context']);
        }

        return implode("\n", $parts);
    }
}

/**
 * The 2FA seam double: records the passwords it was handed so a test can prove
 * the route fired, without running the real SRP chain (which needs a live
 * scope — that stays PasswordConfirmer's own contract to prove).
 */
final class RecordingConfirmer implements PasswordConfirmerContract
{
    /** @var list<string> */
    private array $seen = [];

    public function confirm(string $password): array
    {
        $this->seen[] = $password;

        return ['_' => 'auth.authorization', 'user' => ['id' => 1]];
    }

    /**
     * @return list<string>
     */
    public function seen(): array
    {
        return $this->seen;
    }
}
