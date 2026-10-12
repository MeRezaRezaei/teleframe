<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Notify;

use Illuminate\Bus\Queueable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use MeRezaRezaei\Teleframe\Notify\NotificationJob;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

/**
 * QueuedNotifyJob — the Laravel-queue envelope for one notification send,
 * the transport behind LaravelQueueNotificationQueue (the async default for
 * any host whose `queue.default` is already async: `database` needs a table,
 * not a broker, so the queue-shaped default costs no new infrastructure).
 *
 * WHY A CLASS AND NOT THE RAW PAYLOAD: Laravel's Queue::push() serializes
 * the job OBJECT, so the payload cannot be a bare array (a closure job would
 * carry the send logic across the wire, which is exactly the closure the
 * NotificationJob docblock forbids). This object is the serializable unit and
 * holds nothing but the plain-array wire payload from
 * NotificationJob::toArray() — no session string, no bot token (targets are
 * chat ids / thread ids; the transport resolves credentials at handle time).
 *
 * ATTACHMENT CAVEAT (operator-visible, deliberate): the payload's
 * `attachment.path` is a local filesystem path. A worker on another host (or
 * another container) only finds it if `attachment_dir` is shared storage —
 * configure teleframe.notify.attachment_dir accordingly before running a
 * distributed worker.
 *
 * TWO WORKERS, TWO OWNERS (documented, not accidental):
 *   - the host's `php artisan queue:work` runs handle(): the send happens on
 *     the worker process and a throw propagates, so LARAVEL owns the attempt
 *     count — which is why $tries below is pinned to the driver's own
 *     RETRY_CAP and failed() parks on the SAME dead-letter queue, so this
 *     path and `teleframe:notify work` cannot disagree about where a
 *     cap-exhausted job goes;
 *   - `php artisan teleframe:notify work` calls the driver's consume(), where
 *     the DRIVER owns retry accounting (attempts+1 up to the cap, then
 *     tframe.notify.dl) before deleting the envelope.
 * A dead-letter envelope is inert by construction: handle() returns without
 * sending, so an operator inspecting tframe.notify.dl can never re-send by
 * accident.
 */
final class QueuedNotifyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * THE RETRY CONTRACT THE ADVERTISED DL ESCAPE DEPENDS ON
     * (NotificationQueueContract::deadLetterName): on this path Laravel owns
     * the attempts, so without a cap a failing notify retries forever and the
     * dead-letter the contract advertises never receives anything. The number
     * is the DRIVER's RETRY_CAP, not a new constant — the two paths must not
     * disagree about how many attempts a job gets.
     */
    public int $tries = LaravelQueueNotificationQueue::RETRY_CAP;

    /**
     * Seconds before the attempt is killed and failed to a worker. Matches
     * queue:work's own default, so this never shortens the host's window: a
     * notify send is one Telegram round-trip, not a batch.
     */
    public int $timeout = 60;

    /** Seconds between attempts, so a Telegram-side failure is not a hot loop. */
    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $payload  NotificationJob::toArray()
     * @param  bool  $deadLetter  true = parked for operator inspection; never sent
     * @param  string|null  $connection  the queue connection this job was
     *                                   pushed on, so failed() parks the
     *                                   dead-letter envelope on the SAME
     *                                   backend a host is already watching
     */
    public function __construct(
        public readonly array $payload,
        public readonly bool $deadLetter = false,
        ?string $connection = null,
    ) {
        if ($connection !== null) {
            $this->connection = $connection;
        }
    }

    /**
     * The send. NotifyService is injected (CallQueuedHandler resolves it from
     * the container on the worker), and handle() rethrows by contract — the
     * logger never swallows.
     */
    public function handle(NotifyService $service): void
    {
        if ($this->deadLetter) {
            return;
        }

        $service->handle(NotificationJob::fromArray($this->payload));
    }

    /**
     * Laravel's LAST attempt: the job exhausted $tries. This is what makes
     * NotificationQueueContract::deadLetterName() TRUE on the host's own
     * `queue:work` path — without it the envelope goes to Laravel's
     * `failed_jobs` table and the advertised tframe.notify.dl escape does not
     * exist on the primary driver.
     */
    public function failed(?Throwable $e = null): void
    {
        $connection = $this->connection;

        try {
            $container = Container::getInstance();

            if (! $container->bound('queue')) {
                throw new \RuntimeException('the host app has no queue service (illuminate/queue is not registered)');
            }

            /** @var QueueFactory $queues */
            $queues = $container->make('queue');
            $queues->connection($connection)->pushOn(
                LaravelQueueNotificationQueue::DEAD_LETTER,
                new self($this->payload, true, $connection)
            );
        } catch (Throwable $parkingFailure) {
            // Losing the job to the dead-letter is a real loss, so it is loud
            // rather than silent — but a throw HERE would take the worker down
            // while it is already handling a failure, which loses MORE than
            // this does. Log the parking failure at error and let Laravel's own
            // failed_jobs row remain the last record.
            $this->logger()->error('teleframe.notify: could not park a failed job on the dead-letter queue', [
                'dead_letter' => LaravelQueueNotificationQueue::DEAD_LETTER,
                'job_id' => $this->payload['job_id'] ?? null,
                'connection' => $connection,
                'error' => $parkingFailure->getMessage(),
            ]);
        }
    }

    /** The host's PSR-3 logger when there is one; silence is the default. */
    private function logger(): LoggerInterface
    {
        $container = Container::getInstance();
        $logger = $container->bound(LoggerInterface::class)
            ? $container->make(LoggerInterface::class)
            : null;

        return $logger instanceof LoggerInterface ? $logger : new NullLogger;
    }
}
