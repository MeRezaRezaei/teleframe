<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;


use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\Topic;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationQueueContract;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotificationTransportContract;
use MeRezaRezaei\Teleframe\Notify\SendReceipt;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * AN OFFLINE SEND MUST NOT CLAIM DELIVERY, AND MUST NOT DESTROY ITS ARTEFACT.
 *
 * With `live_mode` off — the default outside a fully wired host — the MTProto
 * `Client::call()` returns a self-describing offline marker (`_offline => true`)
 * instead of an `rpc_result`. Nothing in the notify chain inspected it, so two
 * failures stacked:
 *
 *  1. the send was reported delivered, and
 *  2. `NotifyService` unlinked the temp attachment, because a non-throwing send
 *     is by contract a send whose artefact the worker owns and may drop.
 *
 * The message was never sent, and the second failure destroyed the only copy
 * left to retry it. The marker is the contract's own failure signal; honouring
 * it is the whole fix. `Client` still returns it unchanged — that is pinned as
 * a contract in ClientOfflineContractTest — and only the notify path's reading
 * of it changed.
 *
 * AND OFFLINE THROWS (D6). The first version of that fix LOGGED at error and
 * then RETURNED, which on every async driver is an ACK: the envelope was
 * deleted, never retried, never dead-lettered, and the temp attachment — the
 * only copy of the report — was orphaned on disk forever. Returning is not a
 * neutral choice on a queue consumer; it is a delivery confirmation for a send
 * that did not happen. An offline send must THROW, so the retry / dead-letter
 * machinery owns recovery and the artefact stays on disk for the redelivery.
 */
final class OfflineNotifyDoesNotDestroyArtefactTest extends TestCase
{
    public function test_an_offline_send_keeps_the_attachment(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tg-offline-');
        self::assertIsString($path);
        file_put_contents($path, 'the only copy of this attachment');

        try {
            $service = $this->service($this->offlineReceipt());

            // D6: an offline send must NOT return. On an async driver a
            // non-throwing consumer is an ACK — the job is gone and the
            // artefact is stranded with no redelivery ever coming.
            try {
                $service->handle($this->job($path));
                self::fail('an offline send returned normally — the queue will ACK a notification that was never delivered');
            } catch (NotifyException $e) {
                self::assertStringContainsString('did not happen', $e->getMessage());
                self::assertStringContainsString('attachment was kept', $e->getMessage());
            }

            self::assertFileExists(
                $path,
                'an offline send unlinked the attachment — the only copy left to retry with is gone',
            );
            self::assertSame('the only copy of this attachment', file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    /** D6: the retry machinery is only reachable if the consumer refuses the job. */
    public function test_an_offline_send_throws_so_the_queue_can_retry_or_dead_letter_it(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tg-offline-throw-');
        self::assertIsString($path);
        file_put_contents($path, 'retry me');

        try {
            $this->expectException(NotifyException::class);
            $this->service($this->offlineReceipt())->handle($this->job($path));
        } finally {
            self::assertFileExists($path, 'the artefact must survive the throw — it is what the retry re-sends');
            @unlink($path);
        }
    }

    public function test_an_offline_send_is_reported_as_not_happening(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tg-offline-');
        self::assertIsString($path);
        $log = new SpyLogger;

        try {
            try {
                $this->service($this->offlineReceipt(), $log)->handle($this->job($path));
            } catch (NotifyException) {
                // expected (D6) — the operator is told below either way.
            }

            self::assertStringContainsString(
                'did not happen',
                $log->everything(),
                'a send that never left the machine must not be silent',
            );
            self::assertStringContainsString(
                'attachment_kept',
                $log->everything(),
                'the operator needs to know the artefact survived, so it can be retried',
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_a_real_send_still_drops_the_attachment(): void
    {
        // The other half of the contract: a genuine send is one the worker owns
        // the artefact for, and NOT cleaning it up is its own leak.
        $path = tempnam(sys_get_temp_dir(), 'tg-online-');
        self::assertIsString($path);
        file_put_contents($path, 'delivered');

        try {
            $this->service($this->realReceipt())->handle($this->job($path));

            self::assertFileDoesNotExist(
                $path,
                'a real send must still release the temp attachment the worker owns',
            );
        } finally {
            @unlink($path);
        }
    }

    public function test_a_real_send_is_not_logged_as_a_failure(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tg-online-');
        self::assertIsString($path);
        $log = new SpyLogger;

        try {
            $this->service($this->realReceipt(), $log)->handle($this->job($path));

            self::assertStringNotContainsString('did not happen', $log->everything());
        } finally {
            @unlink($path);
        }
    }

    // ---- doubles ---------------------------------------------------------

    private function offlineReceipt(): SendReceipt
    {
        // Exactly what Client::call() returns with live_mode off.
        return new SendReceipt(
            'mtproto:user-scope',
            'messages.sendMedia',
            ['_' => 'rpc_result', '_offline' => true],
        );
    }

    private function realReceipt(): SendReceipt
    {
        return new SendReceipt(
            'mtproto:user-scope',
            'messages.sendMedia',
            ['_' => 'updates', 'update_id' => 7],
        );
    }

    /**
     * A REAL NotifyService over the real transport seam — no reflection, no
     * faked collaborators. Two earlier drafts of this file assigned an
     * anonymous class to the typed `$sender` property and then poked
     * `$registry` and `$logger` in by hand; both were wrong, and the second
     * would have tested a service that cannot be constructed the way a host
     * constructs it.
     */
    private function service(SendReceipt $receipt, ?LoggerInterface $logger = null): NotifyService
    {
        $tmp = sys_get_temp_dir().'/tg-offline-'.bin2hex(random_bytes(4));
        mkdir($tmp, 0o777, true);

        $sender = new NotificationSender(
            new MessageCompiler($tmp.'/tpl', $tmp.'/cache'),
            null,
            $logger,
            120,
            3,
            static function (): void {},
            new ReceiptTransport($receipt),
        );

        return new NotifyService(
            registry: Topic::defaults()->define('app.info', Level::Info, '', '@ops', 2),
            checker: new CapabilityCheck(new CapabilityTable),
            segmenter: new MessageSegmenter(new HtmlAttachment, $tmp),
            queue: new NullQueue,
            sender: $sender,
            enforceBudgets: false,
            logger: $logger,
        );
    }

    private function job(string $attachmentPath): NotificationJob
    {
        return new NotificationJob(
            jobId: 'job-1',
            topic: 'app.info',
            level: 'info',
            brief: 'a notification',
            context: ['_' => 'updateNewMessage', 'message' => ['_' => 'message', 'id' => 1, 'message_id' => 1, 'date' => time()]],
            attachment: ['name' => 'file.bin', 'path' => $attachmentPath, 'mime' => 'application/octet-stream'],
        );
    }
}

/** The queue is not what is under test; handle() is called directly. */
final class NullQueue implements NotificationQueueContract
{
    public function enqueue(NotificationJob $job): string
    {
        return $job->jobId;
    }

    public function consume(callable $handler, int $max = 1): int
    {
        return 0;
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function deadLetterName(): string
    {
        return 'null';
    }
}

/** Answers every method with the receipt under test — the real seam, faked once. */
final class ReceiptTransport implements NotificationTransportContract
{
    public function __construct(private readonly SendReceipt $receipt) {}

    public function call(string $method, array $params = []): array
    {
        return $this->receipt->response;
    }

    public function describe(): string
    {
        return 'receipt-transport';
    }
}

/** The repo's existing PSR-3 capture convention. */
final class SpyLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $lines = [];

    /**
     * The CONTEXT keys are recorded too, not just the message: the point of
     * this test is that the operator is told the artefact SURVIVED, and that
     * claim lives in the context array rather than in the sentence.
     *
     * @param array<string, mixed> $context
     */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->lines[] = (string) $level.' '.(string) $message.' '.implode(' ', array_keys($context));
    }

    public function everything(): string
    {
        return implode("\n", $this->lines);
    }
}
