<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Laravel\Console\PassportCommand;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Passport\AuthorizationImporter;
use MeRezaRezaei\Teleframe\Passport\MemoryBindingWriter;
use MeRezaRezaei\Teleframe\Passport\MemoryPassportWire;
use MeRezaRezaei\Teleframe\Passport\PassportConfig;
use MeRezaRezaei\Teleframe\Passport\PassportLoginException;
use MeRezaRezaei\Teleframe\Passport\PassportLoginService;
use MeRezaRezaei\Teleframe\Passport\PassportSupport;
use MeRezaRezaei\Teleframe\Passport\PassportWireLogin;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * `teleframe:passport` — the CLI shell over the passport login surface
 * (design 2026-09-24 §7, NotifyCommand ACTIONS/match pattern): status
 * (config + ordered seams table), nonce (issue/consume replay-guard probe
 * through the SAME registry the webhook uses), flush (clear sweep). Unknown
 * action and RuntimeException → error + FAILURE. Every seam resolves through
 * the REAL PassportServiceProvider via the fixed teleclient.passport.* keys.
 * The unconfigured module (enabled=false) RESOLVES and login() stays loud —
 * never a silent drop (the facade/CLI identity probe).
 */
final class PassportCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Kernel::class)->registerCommand(new PassportCommand);
    }

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [PassportServiceProvider::class];
    }

    public function test_unknown_action_returns_failure(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'explode']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('unknown action "explode"', $out);
        self::assertStringContainsString('status|nonce|flush|import', $out);
    }

    public function test_status_reports_the_inert_unconfigured_module(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'status']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::SUCCESS, $exit);
        self::assertStringContainsString('disabled (inert — configure teleframe.passport)', $out);
        self::assertStringContainsString('private key path: (not configured)', $out);
        self::assertStringContainsString('tenant account id: (unset → Identity primary-account rule)', $out);
        self::assertStringContainsString('nonce TTL: 900s (memory registry)', $out);
        self::assertStringContainsString('allowed types: personal_details, email, phone', $out);
        self::assertStringContainsString('2FA confirm: off', $out);
        // the fixed seams resolve — the module is constructible, not broken
        self::assertStringContainsString(PassportSupport::SERVICE_KEY, $out);
        self::assertStringContainsString(PassportLoginService::class, $out);
        // the Cycle-18 WIRE seams resolve too, unconfigured and inert
        self::assertStringContainsString(PassportSupport::WIRE_LOGIN_KEY, $out);
        self::assertStringContainsString(PassportWireLogin::class, $out);
        self::assertStringContainsString(PassportSupport::IMPORTER_KEY, $out);
        self::assertStringContainsString(PassportSupport::EXPORTER_KEY, $out);
        self::assertStringContainsString(PassportSupport::PROFILE_KEY, $out);
        self::assertStringContainsString(PassportSupport::FORM_KEY, $out);
    }

    /**
     * Cycle 18 `import`: the CLI refuses before touching the wire when the
     * operator did not name a Laravel user. The package never resolves a user
     * on its own (Cycle-8b), so an import without --user is a usage error, not
     * a silent login of whoever happens to be session.
     */
    public function test_import_without_a_user_returns_failure(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'import']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('import needs --user=', $out);
    }

    public function test_import_with_an_unresolvable_user_returns_failure(): void
    {
        $this->bindFakeUsersProvider(unresolvable: 424242);

        $exit = Artisan::call('teleframe:passport', ['action' => 'import', '--user' => '424242']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('no Laravel user 424242 resolves', $out);
    }

    /**
     * An unconfigured module must fail the import BEFORE any Telegram call —
     * the gate is the first thing the wire login does, and the CLI inherits it.
     */
    public function test_import_on_an_unconfigured_module_never_reaches_the_wire(): void
    {
        $this->bindFakeUsersProvider();

        $wire = new MemoryPassportWire;
        $this->app->instance(PassportSupport::WIRE_LOGIN_KEY, new PassportWireLogin(
            config: new PassportConfig(['enabled' => false]),
            importer: new AuthorizationImporter($wire),
            writer: new MemoryBindingWriter,
        ));

        $file = $this->authorizationFile();

        try {
            $exit = Artisan::call('teleframe:passport', [
                'action' => 'import',
                '--user' => '42',
                '--file' => $file,
            ]);
            $out = Artisan::output();
        } finally {
            unlink($file);
        }

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('passport login not enabled', $out);
        self::assertSame(0, $wire->dispatchCount(), 'no telegram call may happen while the module is off');
    }

    /**
     * A CONFIGURED import really dispatches `auth.importAuthorization#a57a7dad`
     * with the id/bytes from the file, binds the telegram user to the Laravel
     * user, and prints the outcome — without ever echoing the bytes.
     */
    public function test_a_configured_import_dispatches_the_official_method_and_binds(): void
    {
        $this->bindFakeUsersProvider();

        $wire = new MemoryPassportWire([
            'auth.importAuthorization' => [
                ['_' => 'auth.authorization', 'flags' => 0, 'user' => ['_' => 'user', 'id' => 4242]],
            ],
        ]);
        $writer = new MemoryBindingWriter;
        $this->app->instance(PassportSupport::WIRE_LOGIN_KEY, new PassportWireLogin(
            config: new PassportConfig(['enabled' => true, 'account_id' => 7]),
            importer: new AuthorizationImporter($wire),
            writer: $writer,
        ));

        $bytes = 'not-a-real-authorization';
        $file = tempnam(sys_get_temp_dir(), 'teleframe-passport-import-');
        self::assertNotFalse($file);
        file_put_contents($file, (string) json_encode(['id' => 77, 'bytes' => base64_encode($bytes)]));

        try {
            $exit = Artisan::call('teleframe:passport', [
                'action' => 'import',
                '--user' => '42',
                '--file' => $file,
            ]);
            $out = Artisan::output();
        } finally {
            unlink($file);
        }

        self::assertSame(PassportCommand::SUCCESS, $exit, $out);
        self::assertStringContainsString('passport import ok: telegram user 4242 bound to laravel user 42', $out);
        self::assertStringContainsString('account 7', $out);
        self::assertStringNotContainsString($bytes, $out, 'the authorization bytes are never printed');

        $dispatch = $wire->firstDispatchOf('auth.importAuthorization');
        self::assertNotNull($dispatch);
        self::assertSame(['id' => 77, 'bytes' => $bytes], $dispatch['params']);
        self::assertSame(4242, $writer->last()['tg_id'] ?? null);
    }

    public function test_import_refuses_a_payload_that_is_not_usable_before_dispatching(): void
    {
        $this->bindFakeUsersProvider();

        $wire = new MemoryPassportWire;
        $this->app->instance(PassportSupport::WIRE_LOGIN_KEY, new PassportWireLogin(
            config: new PassportConfig(['enabled' => true]),
            importer: new AuthorizationImporter($wire),
            writer: new MemoryBindingWriter,
        ));

        $file = $this->authorizationFile(['id' => 0, 'bytes' => '']);

        try {
            $exit = Artisan::call('teleframe:passport', [
                'action' => 'import',
                '--user' => '42',
                '--file' => $file,
            ]);
            $out = Artisan::output();
        } finally {
            unlink($file);
        }

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('positive integer "id"', $out);
        self::assertSame(0, $wire->dispatchCount(), 'an unusable payload dispatches nothing');
    }

    /**
     * The host's own auth provider resolves the --user id — the package never
     * owns user lookup (Cycle-8b). A stub provider stands in for a real table.
     */
    private function bindFakeUsersProvider(?int $unresolvable = null): void
    {
        // The AuthManager only consults a custom creator for the CONFIGURED
        // driver name, so the driver is switched to a stub one first.
        config()->set('auth.providers.users.driver', 'teleframe-stub');

        $this->app['auth']->provider('teleframe-stub', static fn (): UserProvider => new class($unresolvable) implements UserProvider
        {
            public function __construct(private readonly ?int $unresolvable) {}

            public function retrieveById($identifier)
            {
                if ($this->unresolvable !== null && (int) $identifier === $this->unresolvable) {
                    return null;
                }

                $user = new PassportCommandUser;
                $user->id = (int) $identifier;

                return $user;
            }

            public function retrieveByToken($identifier, $token)
            {
                return null;
            }

            public function updateRememberToken(Authenticatable $user, $token): void {}

            public function retrieveByCredentials(array $credentials)
            {
                return null;
            }

            public function validateCredentials(Authenticatable $user, array $credentials): bool
            {
                return false;
            }

            public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void {}
        });

        // The guard caches its provider; drop it so the stub is the one used.
        $this->app['auth']->forgetGuards();
    }

    /**
     * A throwaway authorization payload file — a temp path, deleted by the
     * caller. Nothing here is a real credential.
     *
     * @param  array<string, mixed>  $payload
     */
    private function authorizationFile(array $payload = ['id' => 5, 'bytes' => 'YmxvYg==']): string
    {
        $file = tempnam(sys_get_temp_dir(), 'teleframe-passport-import-');
        self::assertNotFalse($file);
        file_put_contents($file, (string) json_encode($payload));

        return $file;
    }

    public function test_status_reports_the_configured_module(): void
    {
        config()->set('teleframe.passport.enabled', true);
        config()->set('teleframe.passport.account_id', 3);
        config()->set('teleframe.passport.nonce_ttl', 300);
        config()->set('teleframe.passport.require_password_confirmation', true);

        $exit = Artisan::call('teleframe:passport', ['action' => 'status']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::SUCCESS, $exit);
        self::assertStringContainsString('Passport login: ENABLED', $out);
        self::assertStringContainsString('tenant account id: 3', $out);
        self::assertStringContainsString('nonce TTL: 300s', $out);
        self::assertStringContainsString('2FA confirm: required', $out);
    }

    public function test_nonce_probe_issues_and_consumes_once(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'nonce', '--user' => '7']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::SUCCESS, $exit);
        self::assertStringContainsString('nonce probe ok', $out);
        self::assertStringContainsString('for user 7', $out);
        self::assertStringContainsString('consumed once, replay refused', $out);
    }

    /**
     * The probe must never print the nonce it issued.
     *
     * It is a one-time 128-bit secret whose leak this module's own threat
     * model calls account takeover — and the probe has already consumed it, so
     * nothing observable depends on seeing it. The report keeps the length,
     * which is what proves a full-width nonce was issued.
     */
    public function test_nonce_probe_never_prints_the_nonce_value(): void
    {
        $issued = null;
        $registry = new class($issued) implements \MeRezaRezaei\Teleframe\Passport\NonceRegistry {
            public ?string $issued = null;

            public function __construct(?string &$issued)
            {
                $this->issued = &$issued;
            }

            public function isAtomic(): bool
            {
                return true;
            }

            public function issue(int $userId): string
            {
                return $this->issued = str_repeat('n', 64);
            }

            public function consume(string $nonce): ?int
            {
                if ($this->issued !== $nonce) {
                    return null;
                }
                $this->issued = null; // one-time

                return 7;
            }

            public function clear(string $nonce): void {}
        };

        $this->app->instance(\MeRezaRezaei\Teleframe\Passport\PassportSupport::NONCES_KEY, $registry);

        $exit = Artisan::call('teleframe:passport', ['action' => 'nonce', '--user' => '7']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::SUCCESS, $exit);
        self::assertStringNotContainsString(str_repeat('n', 64), $out, 'the nonce value must never reach stdout');
        self::assertStringContainsString('64-char nonce', $out);
    }

    public function test_nonce_probe_without_user_returns_failure(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'nonce']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('nonce needs --user=', $out);
    }

    public function test_nonce_probe_with_zero_user_returns_failure(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'nonce', '--user' => '0']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::FAILURE, $exit);
        self::assertStringContainsString('nonce needs --user=', $out);
    }

    public function test_flush_returns_success(): void
    {
        $exit = Artisan::call('teleframe:passport', ['action' => 'flush']);
        $out = Artisan::output();

        self::assertSame(PassportCommand::SUCCESS, $exit);
        self::assertStringContainsString('Nonce registry flushed', $out);
    }

    public function test_unconfigured_service_resolves_and_login_stays_loud(): void
    {
        $service = $this->app->make(PassportSupport::SERVICE_KEY);

        self::assertInstanceOf(PassportLoginService::class, $service);

        try {
            $service->login(['update_id' => 1], 42);
            self::fail('an unconfigured module must refuse loud on login — never silent-drop');
        } catch (PassportLoginException $e) {
            self::assertStringContainsString('passport login not enabled', $e->getMessage());
        }

        // handleUpdate stays silent ONLY for updates that carry no credentials at all.
        self::assertNull($service->handleUpdate(['update_id' => 1, 'message' => ['text' => 'hi']]));
    }

    public function test_config_defaults_match_design_section_7(): void
    {
        $values = new PassportConfig((array) (config('teleframe.passport') ?? []));

        self::assertFalse($values->enabled());
        self::assertSame('', $values->privateKeyPath());
        self::assertNull($values->primaryAccountId());
        self::assertSame(900, $values->nonceTtl());
        self::assertSame('', $values->nonceStore());
        self::assertSame(['personal_details', 'email', 'phone'], $values->allowedTypes());
        self::assertSame('session', $values->userLookup());
        self::assertFalse($values->requirePasswordConfirmation());
    }
}

/**
 * The stub Laravel user `--user` resolves to. A bare Eloquent Model with a
 * writable id is enough: the command only needs a Model instance to hand the
 * wire login, and nothing here touches a table.
 */
final class PassportCommandUser extends Model
{
    public ?int $id = null;
}
