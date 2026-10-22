<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Facades;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\Laravel\Facades\Notify;
use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Facades\TStore;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Store\StoreService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Every `@method` a facade advertises must resolve to something real.
 *
 * WHY THIS EXISTS: this project shipped a facade whose own docblock examples
 * raised `Error: Call to undefined method`. A Laravel Facade is NOT Macroable
 * and its `__callStatic` forwards to the resolved root, so an advertised method
 * that exists on NEITHER the facade nor its root is a hard fatal at the call
 * site — and the docblock is the first thing a host reads.
 *
 * It shipped three separate times: `TelegramApp` advertised define, list, calls
 * and service while implementing none of them, and `Notify::service()` /
 * `TStore::service()` appeared in both an `@method` line and a usage example
 * with no implementation anywhere. Reading the docblock is not a test, and
 * in-repo human review did not catch it either.
 *
 * Note what is deliberately NOT asserted: that the facade class itself defines
 * each method. Forwarded methods are the normal case and go through
 * `__callStatic`. The invariant is that the name resolves SOMEWHERE real.
 */
final class FacadeAdvertisedMethodsTest extends TestCase
{
    /**
     * Each facade paired with the class its accessor must resolve to. Asserted
     * against the real accessor so this mapping cannot silently drift.
     *
     * @return array<string, array{class-string, class-string, string}>
     */
    public static function facadeProvider(): array
    {
        return [
            'Notify' => [Notify::class, NotifyService::class, NotifyCommand::SERVICE_KEY],
            'TStore' => [TStore::class, StoreService::class, TstoreCommand::SERVICE_KEY],
            'TelegramApp' => [TelegramApp::class, AppRuntime::class, AppSupport::SERVICE_KEY],
        ];
    }

    /**
     * @param  class-string  $facade
     * @param  class-string  $root
     */
    #[DataProvider('facadeProvider')]
    public function test_the_accessor_still_resolves_to_the_expected_root(string $facade, string $root, string $key): void
    {
        $accessor = (new ReflectionClass($facade))->getMethod('getFacadeAccessor');
        $accessor->setAccessible(true);

        self::assertSame($key, $accessor->invoke(null), $facade.' accessor drifted from the key this test pins');
    }

    /**
     * @param  class-string  $facade
     * @param  class-string  $root
     */
    #[DataProvider('facadeProvider')]
    public function test_every_advertised_method_resolves(string $facade, string $root, string $key): void
    {
        $reflection = new ReflectionClass($facade);
        $doc = $reflection->getDocComment();

        self::assertIsString($doc, $facade.' must carry a docblock advertising its surface');

        preg_match_all('/@method\s+static\s+\S+\s+(\w+)\s*\(/', $doc, $matches);

        $advertised = array_values(array_unique($matches[1]));
        self::assertNotEmpty($advertised, $facade.' advertises no @method lines, so this test would prove nothing');

        $rootReflection = new ReflectionClass($root);

        foreach ($advertised as $method) {
            self::assertTrue(
                $reflection->hasMethod($method) || $rootReflection->hasMethod($method),
                $facade.' advertises @method '.$method.'() but neither '.$reflection->getShortName()
                .' nor its root '.$rootReflection->getShortName().' defines it. A Facade is not Macroable, so '
                .'that is a fatal at the call site — and the docblock is what a host reads first.'
            );
        }
    }

    /**
     * @param  class-string  $facade
     * @param  class-string  $root
     */
    #[DataProvider('facadeProvider')]
    public function test_every_facade_example_call_resolves_to_a_real_method(string $facade, string $root, string $key): void
    {
        $reflection = new ReflectionClass($facade);
        $doc = $reflection->getDocComment();

        self::assertIsString($doc, $facade.' must carry a docblock');

        $short = $reflection->getShortName();
        preg_match_all('/\b'.preg_quote($short, '/').'::(\w+)\s*\(/', $doc, $matches);

        $called = array_values(array_unique($matches[1]));
        self::assertNotEmpty($called, $facade.' shows no example call to check, so this test would prove nothing');

        $rootReflection = new ReflectionClass($root);

        foreach ($called as $method) {
            self::assertTrue(
                $reflection->hasMethod($method) || $rootReflection->hasMethod($method),
                $facade.' shows an example calling '.$short.'::'.$method.'(), which resolves to nothing. An '
                .'example that fatals teaches a host to distrust every other example in the file.'
            );
        }
    }

    /**
     * A wrongly-bound accessor must fail with a NAMED reason, not a fatal on a
     * missing method. This is the contract the two `service()` accessors rely on.
     */
    public function test_service_accessor_rejects_a_wrong_binding_with_a_named_reason(): void
    {
        $app = new Container;
        $app->instance(NotifyCommand::SERVICE_KEY, 'not-a-notify-service');
        Facade::setFacadeApplication($app);

        try {
            Notify::service();
            self::fail('Notify::service() must refuse a wrongly-bound accessor');
        } catch (\LogicException $e) {
            self::assertStringContainsString('expected', $e->getMessage());
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication(null);
        }
    }
}
