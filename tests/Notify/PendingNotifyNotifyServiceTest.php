<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use LogicException;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\AttachmentFile;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyContext;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\PendingNotify;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\Topic;
use PHPUnit\Framework\TestCase;

/**
 * Orchestrator + fluent builder (design §2/§3, G9): the immutable
 * PendingNotify chain, the job's wire shape, the send()/queue() loud gates,
 * and NotifyService log()/report()/end-to-end — facade → topic=level → sync
 * queue → capability-checked send through the ONE shared sender into a faked
 * BotClient terminal. No live Telegram, no credentials.
 */
final class PendingNotifyNotifyServiceTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-notify-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);
    }

    public function test_fluent_chain_is_immutable(): void
    {
        $base = new PendingNotify('app.info', 'brief', new NotifyContext, new RecordingQueue);
        $baseWire = $base->to('@ops')->toWireArray();

        self::assertSame('app.error', $base->topic('app.error')->to('@ops')->toWireArray()['topic']);
        self::assertSame('critical', $base->level(Level::Critical)->to('@ops')->toWireArray()['level']);
        self::assertSame(['chat' => '@ops', 'thread_id' => 7], $base->to('@ops', 7)->toWireArray()['target']);
        self::assertSame(['a' => 1], $base->with(['a' => 1])->to('@ops')->toWireArray()['context']);
        self::assertTrue($base->premium(true)->to('@ops')->toWireArray()['premium']);
        self::assertSame('x.html', $base->attach(new AttachmentFile('x.html', '/tmp/x.html', 'text/html', 42))->to('@ops')->toWireArray()['attachment']['name']);

        // The original instance is untouched by every fluent call.
        self::assertSame('app.info', $baseWire['topic']);
        self::assertSame(false, $baseWire['premium']);
        self::assertSame([], $baseWire['context']);
    }

    public function test_to_wire_array_has_the_design_wire_shape(): void
    {
        $wire = (new PendingNotify('app.error', 'boom', new NotifyContext, new RecordingQueue))
            ->level(Level::Critical)
            ->premium(true)
            ->attach(new AttachmentFile('x.html', '/tmp/x.html', 'text/html', 42))
            ->to('@ops', 5)
            ->toWireArray();

        self::assertSame([
            'job_id',
            'topic',
            'level',
            'brief',
            'context',
            'attachment',
            'target',
            'premium',
            'attempts',
            'created_at',
        ], array_keys($wire));
        self::assertSame('app.error', $wire['topic']);
        self::assertSame('critical', $wire['level']);
        self::assertSame('boom', $wire['brief']);
        self::assertSame(0, $wire['attempts']);
        self::assertTrue($wire['premium']);
        self::assertSame(['name' => 'x.html', 'path' => '/tmp/x.html', 'mime' => 'text/html', 'bytes' => 42], $wire['attachment']);
        self::assertSame(['chat' => '@ops', 'thread_id' => 5], $wire['target']);
        self::assertIsInt($wire['created_at']);
    }

    public function test_send_with_a_fake_queue_returns_the_wire_array_and_enqueues(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service(queue: $queue);

        $wire = $service->to('app.error', 'boom', ['account_id' => 7])
            ->level(Level::Error)
            ->to('@ops', 5)
            ->send();

        self::assertCount(1, $queue->jobs);
        self::assertSame($queue->jobs[0]->jobId, $wire['job_id']);
        self::assertSame('app.error', $wire['topic']);
        self::assertSame('error', $wire['level']);
        self::assertSame(['chat' => '@ops', 'thread_id' => 5], $wire['target']);
        self::assertSame(['account_id' => 7], $wire['context']);
    }

    public function test_queue_returns_only_the_job_id(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service(queue: $queue);

        $id = $service->to('app.info', 'x')->to('@ops')->queue();

        self::assertSame($queue->jobs[0]->jobId, $id);
    }

    public function test_send_without_a_queue_throws_notify_exception(): void
    {
        $pending = new PendingNotify('app.info', 'x', (new NotifyContext)->withTarget('@ops'));

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no notify queue wired');

        $pending->send();
    }

    public function test_send_without_a_target_chat_throws_notify_exception(): void
    {
        $pending = new PendingNotify('app.info', 'x', new NotifyContext, new RecordingQueue);

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no target chat');

        $pending->send();
    }

    public function test_log_returns_the_job_id_and_routes_to_the_level_default_topic(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service(queue: $queue);

        $id = $service->log(Level::Warning, 'ingest stalled', ['account_id' => 7]);

        self::assertNotSame('', $id);
        self::assertSame($queue->jobs[0]->jobId, $id);
        self::assertSame('app.warning', $queue->jobs[0]->topic);
        self::assertSame('warning', $queue->jobs[0]->level);
        self::assertSame(['account_id' => 7], $queue->jobs[0]->context);
        self::assertSame(['chat' => '@ops', 'thread_id' => 4], $queue->jobs[0]->target);
        self::assertFalse($queue->jobs[0]->premium);
    }

    public function test_unknown_topic_throws_notify_exception_at_send_time(): void
    {
        $service = $this->service();

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('unknown topic "no.such.topic"');

        $service->log(Level::Info, 'x', [], 'no.such.topic');
    }

    public function test_report_composes_a_brief_and_an_html_attachment(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service(queue: $queue);

        $service->report('Deploy summary', ['tasks' => ['one', 'two']], ['rev' => 'abc123']);

        $job = $queue->jobs[0];
        self::assertSame('report', $job->topic);
        self::assertSame('notice', $job->level);
        self::assertSame('Deploy summary', $job->brief);
        self::assertNotNull($job->attachment);
        self::assertSame('text/html', $job->attachment['mime']);
        self::assertFileExists($job->attachment['path']);
    }

    public function test_premium_flag_reaches_the_job_payload(): void
    {
        $queue = new RecordingQueue;
        $service = $this->service(queue: $queue, premium: true);

        $service->log(Level::Info, 'x', []);
        self::assertTrue($queue->jobs[0]->premium);

        // Per-send override down: the builder can drop premium for one send.
        $service->to('app.info', 'y')->premium(false)->to('@ops')->queue();
        self::assertFalse($queue->jobs[1]->premium);
    }

    public function test_log_runs_end_to_end_through_the_sync_queue_into_the_bot_transport(): void
    {
        [$bot, $service] = $this->endToEnd();

        $id = $service->log(Level::Info, 'backup completed', ['bytes' => 48127]);

        self::assertNotSame('', $id);
        self::assertCount(1, $bot->calls);
        self::assertSame('sendMessage', $bot->calls[0]['method']);
        $params = $bot->calls[0]['params'];
        self::assertSame('@ops', $params['chat_id']);
        self::assertSame(2, $params['message_thread_id']);
        self::assertStringContainsString('[INFO] app.info', (string) $params['text']);
        self::assertStringContainsString('backup completed', (string) $params['text']);
        self::assertStringContainsString('bytes=48127', (string) $params['text']);
    }

    public function test_report_runs_end_to_end_as_send_document_with_caption(): void
    {
        [$bot, $service] = $this->endToEnd();

        $id = $service->report('Deploy summary', ['tasks' => ['one', 'two']], ['rev' => 'abc123']);

        self::assertNotSame('', $id);
        self::assertCount(1, $bot->calls);
        self::assertSame('sendDocument', $bot->calls[0]['method']);
        $params = $bot->calls[0]['params'];
        self::assertSame('@ops', $params['chat_id']);
        self::assertArrayHasKey('document', $params);
        self::assertStringContainsString('Deploy summary', (string) $params['caption']);
        self::assertLessThanOrEqual(1024, mb_strlen((string) $params['caption'])); // caption cap row

        // The rendered HTML reached the terminal with the full long section...
        self::assertCount(1, $bot->documents);
        $html = $bot->documents[0];
        self::assertNotNull($html);
        self::assertStringContainsString('<!DOCTYPE html>', (string) $html);

        // ...and the temp attachment the worker owns is cleaned up after the send.
        self::assertFileDoesNotExist($params['document']);
    }

    /** Leader path: log() with an over-cap brief truncates and keeps the full text in the file. */
    public function test_log_runs_end_to_end_with_an_attachment_when_the_brief_is_truncated(): void
    {
        [$bot, $service] = $this->endToEnd();

        $service->log(Level::Info, str_repeat('verbose detail ', 700), ['account_id' => 7]);

        self::assertCount(1, $bot->calls);
        self::assertSame('sendDocument', $bot->calls[0]['method']);
        $params = $bot->calls[0]['params'];
        self::assertStringContainsString('… [truncated]', (string) $params['caption']);
        self::assertLessThanOrEqual(1024, mb_strlen((string) $params['caption']));
        self::assertCount(1, $bot->documents);
        $html = $bot->documents[0];
        self::assertNotNull($html);
        self::assertStringContainsString('verbose detail', (string) $html); // full text survived in the file
    }

    /**
     * @return array{FakeBotClient, NotifyService}
     */
    private function endToEnd(): array
    {
        $bot = new FakeBotClient;
        $sender = new NotificationSender(new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'), $bot);
        $wiring = new class
        {
            public ?NotifyService $service = null;
        };
        $queue = new SyncNotificationQueue(static function (NotificationJob $job) use ($wiring): void {
            if ($wiring->service === null) {
                throw new LogicException('notify end-to-end fixture: service not wired before the first enqueue');
            }
            $wiring->service->handle($job);
        });

        $registry = Topic::defaults();
        foreach (Topic::predefined() as $row) {
            $registry->define($row->name(), $row->level(), $row->description(), '@ops', $row->threadId());
        }

        $wiring->service = new NotifyService(
            registry: $registry,
            checker: new CapabilityCheck(new CapabilityTable),
            segmenter: new MessageSegmenter(new HtmlAttachment, $this->tmp),
            queue: $queue,
            sender: $sender,
            renderer: new HtmlAttachment,
            inlineBudget: 800,
            attachmentDir: $this->tmp,
        );

        return [$bot, $wiring->service];
    }

    private function service(
        ?NotificationQueueContract $queue = null,
        bool $premium = false,
    ): NotifyService {
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
            premium: $premium,
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
