<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\PremiumProbe;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * PremiumProbe was dead code in the shipped tree: the class had ZERO
 * references in src/ — not bound, not called — while the owner had asked
 * for an "enhanced check on … if they have premium" and only the static
 * config multiplier was ever honoured.
 *
 * This proves the probe is (a) not wired when the host has no transport that
 * could ever answer users.getFullUser (a bot token cannot), and (b) really
 * consulted — per send, through the notification core — when the host binds
 * the one-line transport seam.
 */
final class NotifyPremiumProbeWiringTest extends TestbenchTestCase
{
    private string $tmp;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/tframe-premium-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);

        config(['teleframe.notify.default_chat' => '@ops']);

        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        $this->app->singleton(BotClient::class, fn (): FakeBotClient => new FakeBotClient);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);

        parent::tearDown();
    }

    public function test_the_probe_is_not_consulted_without_a_transport_seam(): void
    {
        config(['teleframe.notify.premium_probe.user_id' => 555, 'teleframe.notify.premium' => false]);

        // Sync queue without a transport: the job is captured, not sent, so
        // the test asserts the FLAG the core resolved.
        $queue = new SyncNotificationQueue(static function (): void {});
        $this->app->instance(FacadeSupport::QUEUE_KEY, $queue);

        $service = $this->app->make(FacadeSupport::SERVICE_KEY);
        $service->log(Level::Info, 'no transport bound');

        $reflection = new \ReflectionClass($service);
        self::assertNull(
            $reflection->getProperty('premiumProbe')->getValue($service),
            'a probe must not be built from a transport that cannot answer users.getFullUser',
        );
        self::assertSame(555, $reflection->getProperty('premiumUserId')->getValue($service));
        self::assertFalse($this->app->bound(NotifyServiceProvider::PREMIUM_TRANSPORT_KEY));
    }

    public function test_the_bound_transport_makes_the_probe_answer_on_every_send(): void
    {
        config(['teleframe.notify.premium_probe.user_id' => 555, 'teleframe.notify.premium' => false, 'teleframe.notify.premium_probe.ttl_seconds' => 0]);

        // A counter OBJECT, not a captured reference: an arrow-function
        // factory captures `$calls` BY VALUE, which would silently reset
        // the count and make this test pass for the wrong reason.
        $calls = new \stdClass;
        $calls->n = 0;
        $this->app->bind(NotifyServiceProvider::PREMIUM_TRANSPORT_KEY, static function () use ($calls): callable {
            return static function (string $method, array $params) use ($calls): array {
                $calls->n++;

                return ['users' => [['id' => $params['id'], 'premium' => true]]];
            };
        });

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);

        $this->app->make(FacadeSupport::SERVICE_KEY)->log(Level::Info, 'the probe says premium');

        self::assertSame(1, $calls->n, 'the probe transport was never dialed');
        self::assertNotSame([], $bot->calls, 'the send never happened');
    }

    public function test_the_probe_is_resolvable_from_the_container_for_hosts_that_inject_it(): void
    {
        $this->app->bind(NotifyServiceProvider::PREMIUM_TRANSPORT_KEY, static fn (): callable => static fn (string $method, array $params): array => ['users' => [['id' => $params['id'], 'premium' => false]]]);

        self::assertInstanceOf(PremiumProbe::class, $this->app->make(PremiumProbe::class));
    }

    public function test_the_static_flag_still_governs_when_the_probe_answers_nothing(): void
    {
        config([
            'teleframe.notify.premium_probe.user_id' => 555,
            'teleframe.notify.premium_probe.ttl_seconds' => 0,
            'teleframe.notify.premium' => true,
        ]);

        $this->app->bind(NotifyServiceProvider::PREMIUM_TRANSPORT_KEY, static fn (): callable => static function (string $method, array $params): array {
            throw new \RuntimeException('dc unreachable');
        });

        $this->app->make(FacadeSupport::SERVICE_KEY)->log(Level::Info, 'probe down');

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertNotSame([], $bot->calls, 'a failing probe must never break the send');
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
