<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\PremiumProbe;
use MeRezaRezaei\Teleframe\Notify\Topic;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

/**
 * The three computed-but-never-consulted capabilities of the readiness
 * audit, proven to be CONSULTED:
 *
 *   - PremiumProbe      — the live per-user premium flag. The owner asked
 *                         for an "enhanced check on … if they have premium";
 *                         until now only the static config flag was honoured
 *                         (CapabilityTable's premium multiplier), and the probe
 *                         class had zero references in src/.
 *   - checkRate()       — the send_rate / per_chat_rate / topic_budget rows.
 *   - Topic::throttlePerHour() — the per-topic hourly budget row.
 *
 * A capability that is computed and never consulted is decoration, so each
 * test here asserts a BEHAVIOURAL consequence, not a return value.
 */
final class NotifyCapabilityGuardrailTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmp = sys_get_temp_dir().'/tframe-guardrail-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);

        parent::tearDown();
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

    private function service(
        RecordingQueue $queue,
        ?PremiumProbe $probe = null,
        ?int $premiumUserId = null,
        ?CacheInterface $budgets = null,
        ?CapabilityTable $table = null,
        ?Topic $registry = null,
        bool $staticPremium = false,
        bool $enforce = true,
    ): NotifyService {
        $registry ??= Topic::defaults()->define('app.info', Level::Info, '', '@ops', 2);

        return new NotifyService(
            registry: $registry,
            checker: new CapabilityCheck($table ?? new CapabilityTable),
            segmenter: new MessageSegmenter(new HtmlAttachment, $this->tmp),
            queue: $queue,
            sender: new NotificationSender(
                new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
                new FakeBotClient,
            ),
            premium: $staticPremium,
            premiumProbe: $probe,
            premiumUserId: $premiumUserId,
            budgets: $budgets,
            enforceBudgets: $enforce,
        );
    }

    public function test_the_live_premium_probe_is_consulted_when_the_static_flag_says_no(): void
    {
        $calls = 0;
        $probe = new PremiumProbe(
            call: static function (string $method, array $params) use (&$calls): array {
                $calls++;
                self::assertSame('users.getFullUser', $method);

                return ['users' => [['id' => $params['id'], 'premium' => true]]];
            },
            ttlSeconds: 0,
        );

        $queue = new RecordingQueue;
        // The static flag is FALSE — only the live probe can turn premium on.
        $service = $this->service($queue, $probe, premiumUserId: 555);

        $service->log(Level::Info, 'probe says premium');

        self::assertGreaterThan(0, $calls, 'the probe was never called');
        self::assertTrue($queue->jobs[0]->premium, 'the probe result never reached the job');
    }

    public function test_a_failing_probe_never_breaks_the_send_and_falls_back_to_the_static_flag(): void
    {
        $probe = new PremiumProbe(
            call: static function (): array {
                throw new \RuntimeException('transport is down');
            },
            ttlSeconds: 0,
        );

        $queue = new RecordingQueue;
        $service = $this->service($queue, $probe, premiumUserId: 555, staticPremium: true);

        $service->log(Level::Info, 'probe down, static flag still rules');

        self::assertTrue($queue->jobs[0]->premium, 'unknown must fall back to the static flag, not to false');
    }

    public function test_no_probe_and_no_user_id_keeps_the_static_flag_posture(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service($queue, staticPremium: true);

        $service->log(Level::Info, 'static only');

        self::assertTrue($queue->jobs[0]->premium);
    }

    public function test_the_send_rate_row_is_enforced_not_reported(): void
    {
        // A host row that makes the cap observable: 1 msg/s.
        $table = (new CapabilityTable)->merge(['send_rate' => ['base' => 1, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0]]);

        $queue = new RecordingQueue;
        $service = $this->service($queue, budgets: new InMemoryCache, table: $table, enforce: true);

        $service->log(Level::Info, 'first');

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessageMatches('/rate/');
        $service->log(Level::Info, 'second in the same second');
    }

    public function test_an_advisory_posture_logs_instead_of_refusing(): void
    {
        $table = (new CapabilityTable)->merge([
            'send_rate' => ['base' => 1, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0],
            'per_chat_rate' => ['base' => 10, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0],
        ]);

        $queue = new RecordingQueue;
        $service = $this->service($queue, budgets: new InMemoryCache, table: $table, enforce: false);

        $service->log(Level::Info, 'first');
        $service->log(Level::Info, 'second');

        self::assertCount(2, $queue->jobs, 'an advisory budget must not swallow a notification');
    }

    public function test_the_per_chat_rate_row_is_enforced(): void
    {
        $table = (new CapabilityTable)->merge([
            'send_rate' => ['base' => 10, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0],
            'per_chat_rate' => ['base' => 1, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0],
        ]);

        $queue = new RecordingQueue;
        $service = $this->service($queue, budgets: new InMemoryCache, table: $table, enforce: true);

        $service->log(Level::Info, 'first to the chat');

        $this->expectException(NotifyException::class);
        $service->log(Level::Info, 'second to the same chat');
    }

    public function test_the_topic_hourly_budget_is_enforced(): void
    {
        // per_chat_rate raised so THIS test is about the hourly row: the
        // shipped 1 msg/s chat cap would refuse the second send first.
        $table = (new CapabilityTable)->merge(['per_chat_rate' => ['base' => 10, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0]]);
        $registry = Topic::defaults()->define('app.info', Level::Info, '', '@ops', 2, throttlePerHour: 2);

        $queue = new RecordingQueue;
        $service = $this->service($queue, budgets: new InMemoryCache, registry: $registry, table: $table, enforce: true);

        $service->log(Level::Info, 'one');
        $service->log(Level::Info, 'two');
        $this->expectException(NotifyException::class);
        $this->expectExceptionMessageMatches('/topic_budget/');
        $service->log(Level::Info, 'three — over the hourly budget');
    }

    public function test_the_budget_counter_is_scoped_per_topic_and_per_chat(): void
    {
        $table = (new CapabilityTable)->merge(['per_chat_rate' => ['base' => 10, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0]]);
        $registry = Topic::defaults()
            ->define('app.info', Level::Info, '', '@ops', 2, throttlePerHour: 1)
            ->define('app.error', Level::Error, '', '@ops2', 5, throttlePerHour: 1);

        $queue = new RecordingQueue;
        $service = $this->service($queue, budgets: new InMemoryCache, registry: $registry, table: $table, enforce: true);

        $service->log(Level::Info, 'info one');
        $service->log(Level::Error, 'error one — a different topic, its own budget');

        self::assertCount(2, $queue->jobs);
    }

    public function test_a_premium_send_gets_the_raised_rate_cap(): void
    {
        $table = (new CapabilityTable)->merge([
            'send_rate' => ['base' => 1, 'unit' => 'msg/s', 'premiumMultiplier' => 4.0],
            'per_chat_rate' => ['base' => 10, 'unit' => 'msg/s', 'premiumMultiplier' => 1.0],
        ]);

        $queue = new RecordingQueue;
        // 4/s with premium: four sends pass, the fifth does not.
        $service = $this->service($queue, budgets: new InMemoryCache, table: $table, staticPremium: true, enforce: true);

        for ($i = 0; $i < 4; $i++) {
            $service->log(Level::Info, 'send '.$i);
        }

        self::assertCount(4, $queue->jobs);

        $this->expectException(NotifyException::class);
        $service->log(Level::Info, 'send 5');
    }
}
