<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\AttachmentFile;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\Topic;
use MeRezaRezaei\Teleframe\Notify\UserScopeTransport;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayLogger;
use PHPUnit\Framework\TestCase;

/**
 * Rate-limit enforcement THROUGH the shipped send process — the layer a
 * caller actually touches, on both transports.
 *
 * The three things a "rate limit is enforced" claim has to mean, asserted
 * here end to end: the server-supplied wait is the wait that was taken (an
 * injected sleeper records the seconds; nothing sleeps for real), the retry
 * REALLY re-dials the transport (a counting double, not a mock expectation
 * on the policy), and the cost is visible afterwards — on the receipt for a
 * send that went out, on `lastRateLimit()` plus the PSR-3 record for one
 * that did not.
 */
final class RateLimitedSendTest extends TestCase
{
    private string $tmp;

    /** @var list<int> the seconds the injected sleeper was asked to sleep */
    private array $slept = [];

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-notify-ratelimit-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
        $this->slept = [];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->tmp);
    }

    public function test_a_flood_wait_on_the_user_scope_path_is_honoured_and_the_send_retried(): void
    {
        $scope = new FakeUserScope;
        $scope->failWith(new FloodWaitException(45, 'FLOOD_WAIT_45', 420));
        $scope->replyWith(['_' => 'updates']);

        $sender = $this->userSender($scope);

        $receipt = $sender->sendWithReceipt('[ALERT] disk full', ['chat' => InputPeer::chat(-1001, 'h'), 'thread_id' => 7]);

        self::assertSame([45], $this->slept, 'exactly the seconds Telegram demanded');
        self::assertSame(['messages.sendMessage', 'messages.sendMessage'], $scope->methods(), 'the retry really re-dialed MTProto');
        self::assertTrue($receipt->rateLimited());
        self::assertSame(45, $receipt->waitedSeconds());
        self::assertFalse($receipt->rateLimit?->deferred);
        self::assertSame(2, $receipt->rateLimit?->attempts);
    }

    public function test_a_bot_api_429_on_the_bot_path_is_honoured_and_the_send_retried(): void
    {
        // The shape BotClient::call() really raises for a Bot API flood: a
        // plain TelegramException with a 429 and a "retry after N"
        // description — which the old ResilientCall wrapper could not see,
        // because it only caught the MTProto-typed FloodWaitException.
        $bot = new class extends BotClient
        {
            public int $attempts = 0;

            public function __construct()
            {
                parent::__construct('test-token', null, 1, 0);
            }

            public function call(string $method, array $params = []): array
            {
                $this->attempts++;

                if ($this->attempts === 1) {
                    throw new TelegramException('Telegram Bot API [429]: Too Many Requests: retry after 8', 429);
                }

                return ['ok' => true, 'result' => ['message_id' => 1]];
            }
        };

        $sender = new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            $bot,
            sleep: $this->sleeper(),
        );

        $receipt = $sender->sendWithReceipt('[ERROR] backup missed', ['chat' => 42]);

        self::assertSame([8], $this->slept);
        self::assertSame(2, $bot->attempts, 'the retry really re-dialed the Bot API');
        self::assertSame(8, $receipt->waitedSeconds());
        self::assertSame('sendMessage', $receipt->method);
        self::assertSame('bot-api', $receipt->transport);
    }

    public function test_send_still_returns_the_raw_reply_after_an_honoured_wait(): void
    {
        $scope = new FakeUserScope;
        $scope->failWith(new FloodWaitException(45, 'FLOOD_WAIT_45', 420));
        $scope->replyWith(['ok' => true]);

        $response = $this->userSender($scope)->send('x', ['chat' => InputPeer::chat(-1001, 'h')]);

        self::assertSame(['ok' => true], $response, 'the existing send() contract is unchanged');
    }

    public function test_a_wait_over_the_cap_throws_and_still_reports_the_demanded_seconds(): void
    {
        $scope = new FakeUserScope;
        $scope->failWith(new FloodWaitException(7200, 'FLOOD_WAIT_7200', 420), 3);

        $sender = $this->userSender($scope);

        try {
            $sender->send('x', ['chat' => InputPeer::chat(-1001, 'h')]);
            self::fail('a 2h demand must be deferred, not slept');
        } catch (NotifyException $e) {
            self::assertStringContainsString('7200s', $e->getMessage());
        }

        self::assertSame([], $this->slept, 'nothing was slept in-line');

        $outcome = $sender->lastRateLimit();
        self::assertNotNull($outcome, 'a send that threw still carries its rate-limit record');
        self::assertTrue($outcome->deferred);
        self::assertSame(0, $outcome->waitedSeconds());
        self::assertSame(1, $outcome->attempts);
    }

    public function test_the_service_logs_an_honoured_rate_limit_as_a_cost_not_an_error(): void
    {
        $logger = new ArrayLogger;
        $scope = new FakeUserScope;
        $scope->failWith(new FloodWaitException(20, 'FLOOD_WAIT_20', 420));
        $scope->replyWith(['_' => 'updates']);

        $this->service($this->userSender($scope, $logger), $logger)->log(Level::Alert, 'backup missed');

        $notices = array_values(array_filter(
            $logger->records,
            static fn (array $r): bool => $r['message'] === 'notify send honoured a Telegram rate limit',
        ));
        self::assertCount(1, $notices);
        self::assertSame('notice', $notices[0]['level']);
        self::assertSame('app.alert', $notices[0]['context']['topic']);
        self::assertSame(20, $notices[0]['context']['rate_limit']['waited_seconds']);
        self::assertSame('mtproto:user-scope', $notices[0]['context']['transport']);

        foreach ($logger->records as $record) {
            self::assertNotSame('error', $record['level'], 'an honoured wait is not a failure');
        }
    }

    public function test_the_service_logs_the_demanded_seconds_when_the_send_is_deferred(): void
    {
        $logger = new ArrayLogger;
        $scope = new FakeUserScope;
        $scope->failWith(new FloodWaitException(7200, 'FLOOD_WAIT_7200', 420), 3);

        try {
            $this->service($this->userSender($scope, $logger), $logger)->log(Level::Alert, 'backup missed');
            self::fail('the deferred send must propagate so the queue can own the wait');
        } catch (NotifyException) {
            // expected — the queue's retry/dead-letter owns recovery.
        }

        $errors = array_values(array_filter(
            $logger->records,
            static fn (array $r): bool => $r['message'] === 'notify send failed' && $r['level'] === 'error',
        ));
        self::assertCount(1, $errors);
        self::assertTrue($errors[0]['context']['rate_limit']['deferred'], 'the demanded seconds ride the error record');
        self::assertSame(0, $errors[0]['context']['rate_limit']['waited_seconds']);
        self::assertSame('app.alert', $errors[0]['context']['topic']);
    }

    public function test_an_attachment_send_on_the_user_path_honours_the_wait_too(): void
    {
        $scope = new FakeUserScope;
        $scope->failWith(new FloodWaitException(11, 'FLOOD_WAIT_11', 420));
        $scope->replyWith(['_' => 'updates']);

        $file = $this->tmp.'/report.html';
        file_put_contents($file, '<h1>report</h1>');

        $sender = $this->userSender($scope, null, static fn (string $path, string $mime, string $name): array => [
            '_' => 'inputMediaUploadedDocument',
            'mime_type' => $mime,
            'file_name' => $name,
        ]);

        $receipt = $sender->sendWithReceipt(
            '[NOTICE] report',
            ['chat' => InputPeer::chat(-1001, 'h')],
            (new AttachmentFile('report.html', $file, 'text/html', (int) filesize($file)))->toArray(),
        );

        self::assertSame([11], $this->slept);
        self::assertSame(['messages.sendMedia', 'messages.sendMedia'], $scope->methods());
        self::assertSame('sendDocument', $receipt->method);
    }

    private function userSender(FakeUserScope $scope, ?ArrayLogger $logger = null, ?callable $uploader = null): NotificationSender
    {
        return new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            null,
            $logger,
            120,
            3,
            $this->sleeper(),
            new UserScopeTransport($scope->resolver(), peerResolver: static fn (): array => InputPeer::chat(-1001, 'h'), uploader: $uploader),
        );
    }

    /**
     * The production wiring: the sync queue driver runs the job through the
     * service's own handle() the moment it is enqueued (the closure captures
     * the service by reference, exactly as the provider does), so log()
     * below is a real end-to-end send, not a queue that drops the job.
     */
    private function service(NotificationSender $sender, ?ArrayLogger $logger = null): NotifyService
    {
        $registry = Topic::defaults();
        foreach (Topic::predefined() as $row) {
            $registry = $registry->define(
                $row->name(),
                $row->level(),
                $row->description(),
                -1001,
                $row->threadId(),
            );
        }

        $service = null;
        $queue = new SyncNotificationQueue(function (NotificationJob $job) use (&$service): void {
            $service?->handle($job);
        });

        $service = new NotifyService(
            registry: $registry,
            checker: new CapabilityCheck(new CapabilityTable),
            segmenter: new MessageSegmenter(new HtmlAttachment, $this->tmp),
            queue: $queue,
            sender: $sender,
            renderer: new HtmlAttachment,
            inlineBudget: 800,
            attachmentDir: $this->tmp,
            logger: $logger,
        );

        return $service;
    }

    /** @return callable(int): void records the sleep, never performs one */
    private function sleeper(): callable
    {
        return function (int $seconds): void {
            $this->slept[] = $seconds;
        };
    }
}
