<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\Psr3NotifyBridge;
use MeRezaRezaei\Teleframe\Notify\Topic;
use PHPUnit\Framework\TestCase;
use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;

/**
 * Psr3NotifyBridge (design §2/§7, G2 — wave 2): the PSR-3 LoggerInterface
 * adapter that routes log records INTO the notify core. Covers the 8-level
 * routing (message/context pass-through, topic = level's default topic),
 * the unknown-level → Psr\Log\InvalidArgumentException contract, the
 * never-swallow failure propagation, and the NotifyService-as-sink
 * construction.
 */
final class Psr3BridgeTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-bridge-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);
    }

    public function test_all_eight_levels_route_to_their_default_topics(): void
    {
        $calls = [];
        $sink = static function (Level $level, string $message, array $context, ?string $topic) use (&$calls): string {
            $calls[] = ['level' => $level, 'message' => $message, 'context' => $context, 'topic' => $topic];

            return 'job-'.$level->value;
        };
        $bridge = new Psr3NotifyBridge($sink);

        $bridge->emergency('e-m', ['k' => 'e']);
        $bridge->alert('a-m', ['k' => 'a']);
        $bridge->critical('c-m');
        $bridge->error('err-m');
        $bridge->warning('w-m');
        $bridge->notice('n-m');
        $bridge->info('i-m');
        $bridge->debug('d-m');

        $words = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG,
        ];
        self::assertCount(8, $calls);
        foreach ($words as $i => $word) {
            self::assertSame(Level::fromPsr3($word), $calls[$i]['level']);
            self::assertSame('app.'.$word, $calls[$i]['topic']);
        }
        self::assertSame('e-m', $calls[0]['message']);
        self::assertSame(['k' => 'e'], $calls[0]['context']);
        self::assertSame('a-m', $calls[1]['message']);
        self::assertSame(['k' => 'a'], $calls[1]['context']);
        self::assertSame('w-m', $calls[4]['message']); // 5th call is warning
        self::assertSame([], $calls[3]['context']); // error called without context
    }

    public function test_log_accepts_case_insensitive_words_and_stringable_messages(): void
    {
        $calls = [];
        $bridge = new Psr3NotifyBridge(static function (Level $level, string $message, array $context, ?string $topic) use (&$calls): string {
            $calls[] = [$level, $message, $context, $topic];

            return 'job';
        });

        $bridge->log('WARNING', new class implements Stringable
        {
            public function __toString(): string
            {
                return 'str-val';
            }
        }, ['x' => 1]);

        self::assertSame([
            [Level::Warning, 'str-val', ['x' => 1], 'app.warning'],
        ], $calls);
    }

    public function test_unknown_level_throws_psr_invalid_argument(): void
    {
        $bridge = new Psr3NotifyBridge(static fn (Level $level, string $message, array $context, ?string $topic): string => 'job');

        try {
            $bridge->log('verbose', 'x');
            self::fail('an unknown PSR-3 level must throw');
        } catch (PsrInvalidArgumentException $e) {
            self::assertStringContainsString('unknown PSR-3 log level "verbose"', $e->getMessage());
        }
    }

    public function test_sink_failure_propagates_never_swallowed(): void
    {
        $bridge = new Psr3NotifyBridge(static function (Level $level, string $message, array $context, ?string $topic): string {
            throw new RuntimeException('send failed');
        });

        try {
            $bridge->info('x');
            self::fail('a failing sink must propagate, never be swallowed');
        } catch (RuntimeException $e) {
            self::assertSame('send failed', $e->getMessage());
        }
    }

    public function test_notify_service_is_accepted_as_the_sink(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service(queue: $queue);
        $bridge = new Psr3NotifyBridge($service);

        $bridge->warning('ingest stalled', ['account_id' => 7]);

        self::assertCount(1, $queue->jobs);
        self::assertSame('app.warning', $queue->jobs[0]->topic);
        self::assertSame('warning', $queue->jobs[0]->level);
        self::assertSame(['account_id' => 7], $queue->jobs[0]->context);
    }

    private function service(?NotificationQueueContract $queue = null): NotifyService
    {
        $registry = Topic::defaults();
        foreach (Topic::predefined() as $row) {
            $registry->define($row->name(), $row->level(), $row->description(), '@ops', $row->threadId());
        }

        return new NotifyService(
            registry: $registry,
            checker: new CapabilityCheck(new CapabilityTable),
            segmenter: new MessageSegmenter(new HtmlAttachment, $this->tmp),
            queue: $queue ?? new RecordingQueue,
            sender: new NotificationSender(new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'), new FakeBotClient),
            renderer: new HtmlAttachment,
            inlineBudget: 800,
            attachmentDir: $this->tmp,
        );
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
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
