<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Types\InputPeer;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\CapabilityCheck;
use MeRezaRezaei\Teleframe\Notify\CapabilityTable;
use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\SyncNotificationQueue;
use MeRezaRezaei\Teleframe\Notify\Topic;
use MeRezaRezaei\Teleframe\Notify\UserScopeTransport;
use PHPUnit\Framework\TestCase;

/**
 * Two silent, wrong-destination defects, asserted through a REAL dispatch.
 *
 * The previous coverage asserted the PRODUCER side only — that
 * `PendingNotify::to()` writes `target` onto the wire — which is exactly why
 * nothing caught the fact that no consumer ever read it. Every test here
 * runs the whole path: builder → NotificationJob → SyncNotificationQueue →
 * NotifyService::handle() → NotificationSender → RateLimitPolicy →
 * UserScopeTransport → the scope double, and asserts on what the transport
 * was asked to dial. No socket, no session string, no credential, no clock
 * beyond an injected seam, no sleeper that actually sleeps.
 *
 *  - DEFECT 1: `NotificationJob::$target` was write-only. `target.chat` /
 *    `target.thread_id` were serialised, round-tripped by both async drivers,
 *    and then ignored — `handle()` read the topic registry row only. A
 *    per-send `->to($chat, $threadId)` override landed in the registry's
 *    chat/topic with no exception and no log line.
 *  - DEFECT 2: `random_id` (MTProto's dedup key) was regenerated inside
 *    sendMessage()/sendDocument(), so every attempt the rate-limit policy
 *    made re-dialled with a FRESH id. A retry after an ambiguous failure
 *    delivered the message twice.
 */
final class NotifyTargetAndIdempotencyTest extends TestCase
{
    private string $tmp;

    /** @var list<mixed> the chats the peer resolver was asked to resolve, in order */
    private array $resolved = [];

    /** The inline queue the last service() built — so a hand-built job is dispatched through it too. */
    private ?SyncNotificationQueue $queue = null;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-notify-target-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
        $this->resolved = [];
        $this->queue = null;
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

    public function test_the_jobs_own_chat_beats_the_topic_registry_row(): void
    {
        $scope = new FakeUserScope;
        $service = $this->service($scope);

        // The registry says '@ops' in thread 2. The SEND says -1001.
        $service->to('app.info', 'per-send override')->to(-1001)->send();

        self::assertCount(1, $scope->calls, 'the send really went through the transport');
        self::assertSame(
            [-1001],
            $this->resolved,
            'the job\'s own target.chat was ignored and the registry row won — the message landed in the wrong chat, silently',
        );
    }

    public function test_the_jobs_own_thread_id_beats_the_topic_registry_row(): void
    {
        $scope = new FakeUserScope;
        $service = $this->service($scope);

        $service->to('app.info', 'per-send thread override')->to(-1001, 9)->send();

        $params = $scope->onlyParams();
        self::assertSame(
            9,
            $params['reply_to']['reply_to_msg_id'] ?? null,
            'the job\'s own target.thread_id was ignored and the registry row\'s thread won — the message landed in the wrong forum topic, silently',
        );
    }

    public function test_a_job_with_no_target_still_falls_back_to_the_registry_row(): void
    {
        $scope = new FakeUserScope;
        $this->service($scope);
        $queue = $this->queue;

        // A job as a foreign producer / an older payload would carry it: the
        // wire shape's `target` is OPTIONAL on NotificationJob.
        $queue->enqueue(new NotificationJob(
            jobId: 'tf-no-target',
            topic: 'app.info',
            level: 'info',
            brief: 'no target on the wire',
        ));

        self::assertSame(
            ['@ops'],
            $this->resolved,
            'with no target the registry row must still decide the chat — the fallback is not a regression',
        );
        $params = $scope->onlyParams();
        self::assertSame(2, $params['reply_to']['reply_to_msg_id'] ?? null, 'and the registry row\'s thread');
    }

    public function test_a_job_with_an_empty_target_still_falls_back_to_the_registry_row(): void
    {
        $scope = new FakeUserScope;
        $this->service($scope);
        $queue = $this->queue;

        $queue->enqueue(new NotificationJob(
            jobId: 'tf-empty-target',
            topic: 'app.info',
            level: 'info',
            brief: 'empty target',
            target: ['chat' => '', 'thread_id' => 0],
        ));

        self::assertSame(['@ops'], $this->resolved, 'a blank chat on the wire is not a destination');
        self::assertSame(2, $scope->onlyParams()['reply_to']['reply_to_msg_id'] ?? null);
    }

    public function test_a_retry_of_the_same_logical_send_reuses_the_same_random_id(): void
    {
        $scope = new FakeUserScope;
        // The ambiguous failure: Telegram delivered it, the reply never came
        // back, the policy honoured the flood wait and re-dialled.
        $scope->failWith(new FloodWaitException(1, 'FLOOD_WAIT_1', 420));
        $scope->replyWith(['_' => 'updates']);

        $this->service($scope)->log(Level::Info, 'retry me');

        self::assertCount(2, $scope->calls, 'the policy really re-dialed the transport');
        $ids = array_map(static fn (array $call): int => (int) $call['params']['random_id'], $scope->calls);
        self::assertSame(
            $ids[0],
            $ids[1],
            'the retry carried a FRESH random_id, so Telegram had no dedup key to recognise the second attempt and the message was posted twice',
        );
    }

    public function test_two_different_sends_never_share_a_random_id(): void
    {
        $scope = new FakeUserScope;
        $service = $this->service($scope);

        $service->log(Level::Info, 'first notification');
        $service->log(Level::Info, 'a completely different notification');

        self::assertCount(2, $scope->calls);
        $ids = array_map(static fn (array $call): int => (int) $call['params']['random_id'], $scope->calls);
        self::assertNotSame(
            $ids[0],
            $ids[1],
            'two distinct sends collided on one random_id — the second would have been deduped away by Telegram',
        );
    }

    public function test_the_same_text_to_a_different_chat_never_shares_a_random_id(): void
    {
        $scope = new FakeUserScope;
        $service = $this->service($scope);

        $service->to('app.info', 'identical text')->to(-1001)->send();
        $service->to('app.info', 'identical text')->to(-1002)->send();

        $ids = array_map(static fn (array $call): int => (int) $call['params']['random_id'], $scope->calls);
        self::assertNotSame($ids[0], $ids[1], 'the destination is part of the send, not just its text');
    }

    /**
     * A delivered send releases its memoised id, so a later byte-identical
     * send is a NEW message and must not inherit the previous dedup key.
     */
    public function test_a_delivered_send_releases_its_random_id(): void
    {
        $scope = new FakeUserScope;
        $service = $this->service($scope);

        $service->log(Level::Info, 'the very same text');
        $service->log(Level::Info, 'the very same text');

        $ids = array_map(static fn (array $call): int => (int) $call['params']['random_id'], $scope->calls);
        self::assertNotSame(
            $ids[0],
            $ids[1],
            'a successful send kept its random_id, so an identical notification minutes later would be swallowed by the dedup key',
        );
    }

    /**
     * The service wired over the REAL send path: sync queue → handle() →
     * sender → policy → user-scope transport → the recording scope.
     */
    private function service(FakeUserScope $scope): NotifyService
    {
        $sender = new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            null,
            null,
            120,
            3,
            // Nothing sleeps for real; the policy's decision is asserted by
            // the number of dials, not by the wall clock.
            static function (): void {},
            new UserScopeTransport(
                $scope->resolver(),
                peerResolver: function (mixed $chat): array {
                    $this->resolved[] = $chat;

                    return InputPeer::chat(0, 'h');
                },
            ),
        );

        // The sync queue runs the service's own handle() inline — the queue
        // is constructed first because the service holds it by value, and the
        // handler resolves the service through the by-reference binding.
        $service = null;
        $queue = $this->queue = new SyncNotificationQueue(function (NotificationJob $job) use (&$service): void {
            if ($service instanceof NotifyService) {
                $service->handle($job);
            }
        });

        return $service = new NotifyService(
            registry: Topic::defaults()->define('app.info', Level::Info, '', '@ops', 2),
            checker: new CapabilityCheck(new CapabilityTable),
            segmenter: new MessageSegmenter(new HtmlAttachment, $this->tmp),
            queue: $queue,
            sender: $sender,
            // The per-chat rate guard is not what is under test here and a
            // same-process second send would trip it; advisory mode keeps
            // every send going out so the destination is observable.
            enforceBudgets: false,
        );
    }
}
