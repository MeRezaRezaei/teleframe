<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use MeRezaRezaei\Teleframe\Laravel\Facades\Passport;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Passport\NonceRegistry;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The Passport facade (design 2026-09-24 §3) — THE code face of the
 * Telegram Passport login surface: "let Laravel users login using it when
 * one configures them". The accessor is the fixed teleclient.passport.service
 * seam; the facade, `Teleframe::passport()` and the `teleframe:passport` CLI
 * share ONE service instance. Unconfigured (enabled=false): resolves and is
 * callable — login() throws loud on first real use, never silent-drop.
 */
final class PassportFacadeTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [PassportServiceProvider::class];
    }

    public function test_facade_accessor_is_the_fixed_service_key(): void
    {
        self::assertSame(PassportSupport::SERVICE_KEY, PassportAccessorProbe::accessor());
        self::assertSame('teleclient.passport.service', PassportSupport::SERVICE_KEY);
    }

    public function test_facade_boots_against_the_real_provider_and_resolves_the_service(): void
    {
        $service = Passport::getFacadeRoot();

        self::assertInstanceOf(PassportLoginService::class, $service);
        self::assertSame($service, $this->app->make(PassportSupport::SERVICE_KEY));
    }

    public function test_facade_service_is_the_same_instance_the_command_seams_resolve(): void
    {
        $service = Passport::getFacadeRoot();

        self::assertSame($service, $this->app->make(PassportSupport::SERVICE_KEY));
        self::assertSame($service, $service->service());
        // the nonce registry seam the CLI probe rides is the same registry
        // the facade's issueNonce() writes through
        self::assertInstanceOf(NonceRegistry::class, $this->app->make(PassportSupport::NONCES_KEY));
    }

    public function test_unconfigured_facade_login_throws_loud_never_silent_drop(): void
    {
        $service = Passport::getFacadeRoot();

        try {
            $service->login(['update_id' => 1], 42);
            self::fail('an unconfigured module must refuse loud on login');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('passport login not enabled', $e->getMessage());
        }
    }

    public function test_unconfigured_facade_handle_update_is_silent_only_without_credentials(): void
    {
        $service = Passport::getFacadeRoot();

        self::assertNull($service->handleUpdate(['update_id' => 1, 'message' => ['text' => 'plain message']]));
    }

    public function test_unconfigured_facade_handle_update_with_credentials_stays_loud(): void
    {
        $service = Passport::getFacadeRoot();

        $update = [
            'message' => ['passport_data' => ['credentials' => ['data' => 'x', 'hash' => 'y', 'secret' => 'z']]],
        ];

        try {
            $service->handleUpdate($update, 42);
            self::fail('an update carrying credentials routes to login() and stays loud');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('passport login not enabled', $e->getMessage());
        }

        // With no session to present it, the update is refused BEFORE the
        // chain — a credential with an unknown presenter is never "just
        // logged in" (2026-09-26 account takeover).
        try {
            $service->handleUpdate($update);
            self::fail('an unbound update carrying credentials must be refused');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('no session is bound', $e->getMessage());
        }
    }

    public function test_facade_issue_nonce_rides_the_shared_registry(): void
    {
        $service = Passport::getFacadeRoot();

        $nonce = $service->issueNonce(7);

        self::assertSame(32, strlen($nonce));
    }
}

/**
 * Accessor probe: Facade::getFacadeAccessor() is protected, so a subclass
 * exposes the configured accessor for the seam assertion.
 */
final class PassportAccessorProbe extends Passport
{
    public static function accessor(): string
    {
        return self::getFacadeAccessor();
    }
}
