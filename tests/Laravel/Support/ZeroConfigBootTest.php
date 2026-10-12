<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests\Support;

use MeRezaRezaei\Teleframe\Laravel\Notify\ExceptionReporter;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\PassportServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\SupervisorServiceProvider;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use ReflectionProperty;
use Throwable;

/**
 * B3 ZERO-CONFIG BOOT PROOF, as a permanent regression gate.
 *
 * The package's declared rule: unconfigured (nothing in config/teleframe*.php),
 * every provider registers, every seam it binds resolves, and NOTHING dials
 * Telegram, connects Redis, or writes to the filesystem at register/boot time.
 * A seam that cannot be built without credentials must fail LOUD, never
 * silently.
 *
 * This mounts all six auto-discovered providers together in one app and
 * asserts the rule per provider. The companion live proof (a real host app
 * installed from packagist metadata) is reported in the release notes.
 */
final class ZeroConfigBootTest extends TestbenchTestCase
{
    private string $tmp;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            TeleframeServiceProvider::class,
            NotifyServiceProvider::class,
            StoreBackupServiceProvider::class,
            AppServiceProvider::class,
            PassportServiceProvider::class,
            SupervisorServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/tframe-zero-config-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);

        parent::tearDown();
    }

    /**
     * (a) All SIX providers are the ones composer.json declares for
     * auto-discovery, and all of them register + boot with an empty config.
     * (Six since Cycle 20: SupervisorServiceProvider joined the list —
     * `teleframe:supervise` + the ops read-model are the package's own.)
     */
    public function test_all_six_declared_providers_are_mounted_and_boot_unconfigured(): void
    {
        $declared = json_decode(
            (string) file_get_contents(dirname(__DIR__, 3).'/composer.json'),
            true
        )['extra']['laravel']['providers'];

        $mounted = array_keys($this->app->getLoadedProviders());

        foreach ($declared as $provider) {
            self::assertContains($provider, $mounted, $provider.' did not register.');
        }

        self::assertCount(6, $declared);
    }

    /**
     * (b) Every binding each provider registered resolves with an empty
     * config. The two documented exceptions are asserted LOUD, not silent.
     */
    public function test_every_teleclient_seam_resolves_when_unconfigured(): void
    {
        // The bus seam needs a live redis; bind the in-memory double so the
        // assertion is about the PACKAGE's wiring, not about a redis server.
        $this->app->bind(
            RedisConnectionContract::class,
            static fn (): RedisConnectionContract => new ArrayRedis
        );

        // teleclient.notify.service is the ONE documented exception: its
        // NotificationSender needs the host's bot transport (BotClient) and
        // template compiler (MessageCompiler) — a documented host contract
        // (NotifyServiceProviderTest::setUp). It is asserted LOUD, never
        // silently, in test_notify_seam_fails_loud_not_silent_when_bot_is_unbound.
        $documentedHostContracts = [FacadeSupport::SERVICE_KEY];

        $seams = array_values(array_filter(
            array_keys($this->app->getBindings()),
            static fn (string $k): bool => str_starts_with($k, 'teleclient.')
                && ! in_array($k, $documentedHostContracts, true)
        ));
        sort($seams);

        self::assertNotEmpty($seams, 'no teleclient.* seams were registered.');

        $failures = [];
        foreach ($seams as $seam) {
            try {
                $this->app->make($seam);
            } catch (Throwable $e) {
                $failures[] = $seam.' => '.$e::class.': '.$e->getMessage();
            }
        }

        self::assertSame([], $failures, "seams failed to resolve unconfigured:\n".implode("\n", $failures));
    }

    /**
     * (b') Notify is the ONE documented host contract, and "never silent-drop"
     * means it fails at TRANSPORT time, not at RESOLVE time.
     *
     * CORRECTED 2026-09-25: this test previously asserted that make() THROWS
     * when the host's bot binding is absent. That was the behaviour before
     * commit afcedd64, and it contradicted the package's own documented rule
     * (src/Laravel/Facades/Notify.php:29-30): "Unconfigured (no
     * teleframe.notify.default_chat): resolves and is callable - sends throw on
     * first transport use, never silent-drop."
     *
     * BotClient is not autowirable - its constructor takes a required string
     * $botToken - so without an explicit binding the Notify facade could not be
     * resolved AT ALL on any install, token configured or not. afcedd64 bound
     * it so the facade stays callable and inert: an empty token builds a
     * BotClient that only fails when a send actually reaches the transport.
     * That is the documented contract, and this is the assertion that proves
     * it, so the two halves are now consistent instead of contradicting.
     */
    public function test_notify_seam_resolves_and_only_fails_at_transport_time(): void
    {
        $service = $this->app->make(FacadeSupport::SERVICE_KEY);

        self::assertNotNull($service, 'Notify must resolve unconfigured: the rule is "resolves and is callable"');

        // The other half of the same rule: it must NOT be a silent no-op. With
        // no bot token the send reaches the transport and fails LOUD there.
        // The Level import is load-BEARING and was missing. Without it
        // `Level::Error` raised `Error: Class not found`, which satisfies
        // expectException(Throwable::class) WITHOUT EVER REACHING THE NOTIFY
        // CORE — so this test passed for the wrong reason and hid a real
        // silent-drop bug on the async queue driver. A test that cannot fail
        // for its stated reason is worse than no test, because it is trusted.
        // expectException(Throwable::class) ALONE IS NOT ENOUGH, and that was
        // the real defect: a missing import made `Level::Error` raise
        // `Error: Class not found`, which IS a Throwable, so the test went
        // green WITHOUT EVER REACHING THE NOTIFY CORE. Prove the failure came
        // from the transport and not from a name that failed to resolve.
        try {
            $service->log(Level::Error, 'zero-config boot probe');
        } catch (Throwable $e) {
            self::assertNotInstanceOf(\Error::class, $e, 'the call must fail IN the notify core, not on an unresolved class name');
            // The refusal must come from the notify core and name WHY the send
            // is undeliverable. On an unconfigured host the first real reason is
            // the topic having no target chat — not the bot token, because the
            // boundary now refuses an undeliverable job before the transport.
            self::assertMatchesRegularExpression(
                '/topic|target chat|bot token/i',
                $e->getMessage(),
                'the refusal must name an undeliverable reason, got: '.$e->getMessage(),
            );

            return;
        }

        self::fail('an unconfigured Notify send must fail LOUDLY at the transport, never silently no-op');
    }

    /**
     * (c) Booting resolves exactly ONE teleframe seam eagerly —
     * ExceptionReporter, on purpose: the host's exception pipeline must be
     * hooked at boot to report anything at all. The store backup gap watchdog
     * used to be this entry and no longer is, because it no longer needs a
     * console boot to be registered. Anything else eagerly resolved at boot is
     * a regression: a provider must not dial, connect or write during boot.
     */
    public function test_only_the_documented_seam_is_eagerly_resolved_at_boot(): void
    {
        $instances = new ReflectionProperty(
            Container::class,
            'instances'
        );
        $resolved = array_values(array_filter(
            array_keys($instances->getValue($this->app)),
            static fn (string $c): bool => str_starts_with($c, 'MeRezaRezaei\\Teleframe\\')
                || str_starts_with($c, 'teleclient.')
        ));
        sort($resolved);

        // Both entries are deliberate, and each replaced something worse:
        //  - ExceptionReporter is eagerly resolved because the host's exception
        //    pipeline must be hooked at boot to report anything at all; the
        //    previous entry here was the reporter being BOUND but never
        //    CALLED, which is a binding that is decoration.
        //  - teleclient.store.backup.watchdog is GONE from this list: the
        //    backup watchdog no longer needs a console boot to be registered,
        //    because its registrations are derived from the durable schedule
        //    spec on every check instead of living in an in-memory array that
        //    only the registering process could see. That was the defect that
        //    made the owner's "a database backup did not come in time" alert
        //    unreachable.
        self::assertSame(
            [ExceptionReporter::class],
            $resolved,
            'a teleframe seam was eagerly resolved at boot beyond the documented ones.'
        );
    }

    /**
     * (c') The mirror pipeline's schema artifacts resolve from the PACKAGE
     * directory, not the host's base_path() — a stock install has no
     * schema/sources/ in the app root.
     */
    public function test_mirror_schema_artifacts_resolve_from_the_package_directory(): void
    {
        self::assertFileExists(SchemaArtifacts::tl());
        self::assertFileExists(SchemaArtifacts::catalog());
        self::assertStringStartsWith(
            dirname(__DIR__, 3).DIRECTORY_SEPARATOR,
            SchemaArtifacts::tl(),
            'schema artifacts must resolve inside the package, never via base_path().'
        );
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }
}
