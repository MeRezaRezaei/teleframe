<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Illuminate\Console\Command;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Facades\Notify as NotifyFacade;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

/**
 * artisan teleframe:notify (design §2, relocated by the assembly plan from
 * Laravel\Console into the Notify module so the bind-key constants live
 * beside FacadeSupport): the CLI shell over NotifyService.
 *
 * Actions:
 *   send       — probe one send through the facade (log path)
 *   topics     — list the topic registry rows
 *   work       — drain the async queue (sync driver: nothing to drain)
 *   watch      — wave-2 watchdog: register/status/touch/check
 *                (--register=TOPIC --every=SECS, --touch=TOPIC, --check)
 *   schedules  — wave-2 schedule seam: describe() (default), probe-install
 *                the watchdog check through it (--install)
 *
 * Every dependency comes from the container through the public bind keys
 * below (mirrors TstoreCommand seams). The provider default resolves from
 * teleframe.notify config; hosts rebind any key. The watchdog is the
 * SHARED container singleton when a module binds
 * FacadeSupport::WATCHDOG_KEY (the store backup module does, with the
 * backup no-show derived from the durable schedule spec), else one built
 * from the container seams here (PSR-16 cache → WatchState, queue → alert
 * sender, optional PSR-3 logger, FileWatchAlertSink → durable record) with
 * the InMemoryCache fallback. BOTH paths carry the sink: a watchdog whose
 * alert rides the queue it is watching is a comment, not a watchdog (F8).
 *
 * WHERE THE CHECK RUNS. `teleframe:notify watch --check` is the check, and
 * it is now safe to run in ANY process on ANY cadence: the expectations it
 * checks are derived from durable state, not from whatever this process
 * registered. The store backup module registers that command on Laravel's
 * scheduler (hourly) when teleframe.store.backup.schedule.enabled, which
 * matches BackupScheduleSpec's backup-watchdog-check cadence; a host
 * without the scheduler runs the same line from cron, and a host WITH
 * Temporal maps the backup-watchdog-check activity
 * ('teleclient.notify.check-no-show') onto it.
 */
final class NotifyCommand extends Command
{
    /** Container seam: NotifyService (facade accessor — design §3). */
    public const SERVICE_KEY = FacadeSupport::SERVICE_KEY;

    /** Container seam: NotificationQueueContract. */
    public const QUEUE_KEY = FacadeSupport::QUEUE_KEY;

    /** Container seam: SchedulePublisher (null default). */
    public const SCHEDULES_KEY = FacadeSupport::SCHEDULES_KEY;

    private const ACTIONS = ['send', 'topics', 'work', 'watch', 'schedules'];

    protected $signature = 'teleframe:notify
        {action : send|topics|work|watch|schedules}
        {--brief=teleframe:notify probe : Message text for the send probe}
        {--topic= : Topic override for the send probe (default: level topic)}
        {--level=info : PSR-3 level for the send probe: debug|info|notice|warning|error|critical|alert|emergency}
        {--max=1 : Job count for work}
        {--register= : Watchdog: watch this topic (needs --every)}
        {--every= : Watchdog: expected heartbeat interval seconds for --register}
        {--touch= : Watchdog: record a heartbeat for this topic as observed now}
        {--check : Watchdog: run the no-show check and print the alerts}
        {--install : Schedules: probe-install the watchdog check through the schedule seam}';

    protected $description = 'Notification/logger core: send (probe), topics (list), work (drain queue), watch (register/status/check), schedules (probe/install)';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        if (! in_array($action, self::ACTIONS, true)) {
            $this->error("unknown action \"{$action}\" — expected ".implode('|', self::ACTIONS).'.');

            return self::FAILURE;
        }

        try {
            return match ($action) {
                'send' => $this->send(),
                'topics' => $this->topics(),
                'work' => $this->work(),
                'watch' => $this->watch(),
                'schedules' => $this->schedules(),
            };
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function send(): int
    {
        $level = Level::fromPsr3((string) $this->option('level'));
        $topic = (string) $this->option('topic');
        $job = NotifyFacade::log($level, (string) $this->option('brief'), ['source' => 'cli'], $topic !== '' ? $topic : null);
        $this->info("Enqueued notify job {$job} (".$level->value.($topic !== '' ? ", topic {$topic}" : ', topic '.$level->defaultTopic()).').');

        return self::SUCCESS;
    }

    private function topics(): int
    {
        $rows = $this->notifyService()->registry()->topics();
        if ($rows === []) {
            $this->info('No topics registered yet.');

            return self::SUCCESS;
        }

        $this->table(['name', 'level', 'description', 'chat', 'thread_id', 'throttle_per_hour'], array_map(
            static fn (Topic $def): array => $def->toRow(),
            $rows,
        ));

        return self::SUCCESS;
    }

    private function work(): int
    {
        $queue = $this->getLaravel()->make(self::QUEUE_KEY);
        if (! $queue->isAsync()) {
            $this->info('Queue driver is sync — nothing to drain. The amqp driver (wave 2) adds a real broker.');

            return self::SUCCESS;
        }

        $service = $this->notifyService();
        $max = max(1, (int) $this->option('max'));
        $handled = $queue->consume(
            static function (NotificationJob $job) use ($service): void {
                $service->handle($job);
            },
            $max,
        );
        $this->info("Drained {$handled} notify job(s).");

        return self::SUCCESS;
    }

    /**
     * Wave-2 watchdog surface (design §8): register a watched topic
     * (--register/--every), record a heartbeat (--touch), run the
     * no-show check (--check — alerts land on `{topic}.missing`) and
     * always print the registration status. No options = status only.
     */
    private function watch(): int
    {
        $watchdog = $this->watchdog();

        $register = (string) $this->option('register');
        if ($register !== '') {
            $every = (int) $this->option('every');
            if ($every <= 0) {
                $this->error('--register needs --every=N (expected heartbeat interval in seconds, > 0).');

                return self::FAILURE;
            }
            // Deliberately per-process: an ad-hoc CLI registration is a
            // one-off decision for THIS run, and it layers over the
            // derived rows (see NoShowWatchdog::effectiveRegistrations()).
            $watchdog->register($register, $every);
            $this->info("Registered watchdog topic \"{$register}\" (expect a heartbeat every {$every}s — no-show lands on {$register}.missing).");
        }

        $touch = (string) $this->option('touch');
        if ($touch !== '') {
            $watchdog->touch($touch);
            $this->info("Recorded heartbeat for \"{$touch}\" (last-seen = now).");
        }

        if ((bool) $this->option('check')) {
            $alerts = $watchdog->check();
            if ($alerts === []) {
                $this->info('Watchdog check: no no-show alerts — every watched topic is within its expected window.');
            } else {
                $this->table(
                    ['topic', 'expected_every_s', 'last_seen_s', 'age_s'],
                    array_map(static fn (WatchAlert $alert): array => $alert->toRow(), $alerts),
                );
                $this->warn(count($alerts).' no-show alert(s) — emitted at alert level and enqueued on the <topic>.missing topic when a notify queue is wired.');
            }
        }

        return $this->watchStatus($watchdog);
    }

    /**
     * Wave-2 schedule seam (design §8): default = describe() the
     * SchedulePublisher binding (null driver: schedules off); --install
     * probes install() with the watchdog registrations as the schedule
     * payload (interval = min over the rows, activity
     * teleframe.notify.check-no-show) and prints the per-schedule status
     * the publisher returned.
     */
    private function schedules(): int
    {
        $publisher = $this->getLaravel()->make(self::SCHEDULES_KEY);
        if (! $publisher instanceof SchedulePublisher) {
            throw new RuntimeException(self::SCHEDULES_KEY.' must bind a SchedulePublisher.');
        }

        if (! (bool) $this->option('install')) {
            $this->info($publisher->describe());

            return self::SUCCESS;
        }

        $registrations = $this->watchdog()->registrations();
        if ($registrations === []) {
            $this->warn('No watchdog registrations — nothing to install. Register first: teleframe:notify watch --register=backup --every=3600.');

            return self::SUCCESS;
        }

        $intervals = array_map(
            static fn (array $registration): int => (int) $registration['expected_every'],
            $registrations,
        );
        $status = $publisher->install([
            'teleframe.notify.watchdog' => [
                'interval_seconds' => min($intervals),
                'activity' => 'teleframe.notify.check-no-show',
                'registrations' => $registrations,
            ],
        ]);

        $rows = [];
        foreach ($status as $id => $state) {
            $rows[] = ['schedule' => (string) $id, 'status' => (string) $state];
        }
        $this->table(['schedule', 'status'], $rows);

        return self::SUCCESS;
    }

    /**
     * The watchdog the CLI checks with: the SHARED container singleton when
     * a module binds one (the store backup module binds
     * FacadeSupport::WATCHDOG_KEY), else a watchdog built here from the same
     * container seams.
     *
     * WHY THE SHARED ONE MATTERS (the defect this fixes): building a fresh
     * watchdog with zero registrations made `--check` structurally blind — a
     * registration is per-process state, so a CLI process can never see the
     * registration the application process made at boot, and the backup
     * no-show could never fire from here. The shared binding carries the
     * DERIVED registrations, so a check in any process watches what the
     * durable schedule spec says it watches.
     */
    private function watchdog(): NoShowWatchdog
    {
        $app = $this->getLaravel();

        if ($app->bound(FacadeSupport::WATCHDOG_KEY)) {
            $shared = $app->make(FacadeSupport::WATCHDOG_KEY);
            if ($shared instanceof NoShowWatchdog) {
                return $shared;
            }
        }

        return $this->buildWatchdog($app);
    }

    /**
     * Build a watchdog from the container seams (no dedicated bind key):
     * PSR-16 cache → WatchState (InMemoryCache fallback, Teleframe.php
     * pattern), the notify queue → alert sender, optional PSR-3 logger, and
     * the out-of-band FileWatchAlertSink (the durable record, written before
     * any queue or network call — see alertLogPath() for why this fallback
     * must not be deleted as redundant).
     * Plain-PHP constructible — no provider change needed.
     */
    private function buildWatchdog(mixed $app): NoShowWatchdog
    {
        $cache = $app->bound(CacheInterface::class) ? $app->make(CacheInterface::class) : null;
        if (! $cache instanceof CacheInterface) {
            $cache = new InMemoryCache;
        }

        $queue = $app->make(self::QUEUE_KEY);
        $notify = $queue instanceof NotificationQueueContract ? $queue : null;

        $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;
        $logger = $logger instanceof LoggerInterface ? $logger : null;

        return new NoShowWatchdog(
            state: new WatchState($cache),
            notify: $notify,
            logger: $logger,
            alertSink: new FileWatchAlertSink(self::alertLogPath($app)),
        );
    }

    /**
     * WHY THIS FALLBACK MUST NOT BE DELETED AS REDUNDANT.
     *
     * It looks redundant: when a real Laravel host has the package installed,
     * StoreBackupServiceProvider binds FacadeSupport::WATCHDOG_KEY and
     * watchdog() returns THAT singleton, never this. But the two paths are not
     * the same build. This is the bare-console / no-store-backup-module path,
     * and it was left sink-less by accident of history: NoShowWatchdog grew a
     * durable out-of-band record (F8, D-045.6) precisely because a watchdog
     * whose alert rides the queue it is watching is "a comment, not a
     * watchdog", and this fallback was not migrated with it. That made it
     * strictly WORSE than before the fix — not merely quiet, but actively
     * misleading, because a reader of this method would now assume the sink is
     * always present. So the sink is wired here too, and the deletion
     * argument is exactly backwards: deleting this fallback restores a
     * sink-less watchdog rather than removing a duplicate one.
     *
     * WHERE A NO-SHOW FINDING LANDS HERE. The path resolution deliberately
     * mirrors StoreBackupServiceProvider::alertLogPath() — same config key,
     * same storage/teleframe/watchdog-alerts.jsonl default, same temp-directory
     * posture for a host with no base path — so a finding is in the file the
     * config comment promises on BOTH paths. The duplication is load-bearing:
     * this method is reached precisely when that provider is absent, so it
     * cannot call into it.
     *
     * THE CONFIG KEY IS THE STORE-BACKUP ONE (teleframe.store.backup.watchdog.alert_log)
     * because that is the only key that names this file. It is read
     * defensively: no config repository bound, or a repository that throws, is
     * the unconfigured posture, and it must degrade to the path default rather
     * than take the command down. A blank resolved path is still refused by
     * FileWatchAlertSink — loudly, which is correct: a watchdog that cannot name
     * its record is a wiring error, never a silently discarded alert.
     */
    private static function alertLogPath(mixed $app): string
    {
        $configured = '';
        if (is_object($app) && $app->bound('config')) {
            try {
                $configured = trim((string) ($app->make('config')->get('teleframe.store.backup.watchdog.alert_log') ?? ''));
            } catch (Throwable) {
                // No readable config repository: the documented default below.
            }
        }

        if ($configured !== '') {
            return $configured;
        }

        if (is_object($app) && method_exists($app, 'basePath')) {
            try {
                return $app->basePath('storage'.DIRECTORY_SEPARATOR.'teleframe')
                    .DIRECTORY_SEPARATOR.'watchdog-alerts.jsonl';
            } catch (Throwable) {
                // Fall through to the temp-directory posture.
            }
        }

        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'teleframe-watchdog-alerts.jsonl';
    }

    /** Print the registration status table (registrations, last-seen, age, state). */
    private function watchStatus(NoShowWatchdog $watchdog): int
    {
        $registrations = $watchdog->registrations();
        if ($registrations === []) {
            $this->info(
                'No watchdog registrations. Either nothing is watched (the backup no-show derives from '
                .'teleframe.store.backup.schedule.* — enable it, or bind '.FacadeSupport::WATCHDOG_KEY.'), '
                .'or register one: teleframe:notify watch --register=backup --every=3600 '
                .'(the backup heartbeat no-show lands on backup.missing).'
            );

            return self::SUCCESS;
        }

        $now = time();
        $rows = [];
        foreach ($registrations as $topic => $registration) {
            $expected = (int) $registration['expected_every'];
            $lastSeen = $watchdog->lastSeen($topic);
            $elapsed = $lastSeen === null ? null : $now - $lastSeen;
            $rows[] = [
                'topic' => $topic,
                'expect_every_s' => $expected,
                'last_seen' => $lastSeen === null ? 'never (grace)' : date('c', $lastSeen),
                'age_s' => $elapsed === null ? '-' : (string) $elapsed,
                'status' => $lastSeen === null ? 'grace' : ($elapsed > $expected ? 'overdue' : 'ok'),
            ];
        }
        $this->table(['topic', 'expect_every_s', 'last_seen', 'age_s', 'status'], $rows);

        return self::SUCCESS;
    }

    private function notifyService(): NotifyService
    {
        $service = $this->getLaravel()->make(self::SERVICE_KEY);
        if (! $service instanceof NotifyService) {
            throw new RuntimeException(self::SERVICE_KEY.' must bind a NotifyService.');
        }

        return $service;
    }
}
