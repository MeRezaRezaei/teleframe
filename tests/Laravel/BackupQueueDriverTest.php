<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Store\Queue\AmqpExtension;
use MeRezaRezaei\Teleframe\Store\Queue\AmqpUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Queue\UploadQueueContract;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * Cycle 19 follow-up: `teleframe.store.backup.queue` was DECLARED and
 * documented ("sync|amqp — reuses the tstore upload queue seams") and read by
 * NOTHING. An operator set TELEFRAME_STORE_BACKUP_QUEUE, saw it in the
 * published config, and reasonably believed their backups became async
 * uploads. They did not.
 *
 * There is exactly ONE upload queue: DatabaseBackupService is built over the
 * tstore StoreService seam, and StoreService takes the UploadQueueContract in
 * its constructor. So the key decides which driver builds the shared
 * teleclient.tstore.queue — that is what these tests pin.
 *
 * EVERY TEST HERE PINS ITS OWN BRANCH (pipeline #206, and the #203/#204 pair
 * before it: a test must not let the ENVIRONMENT pick the branch). The two
 * tests this file used to carry were green locally and errored in CI for a
 * reason that had nothing to do with the wiring: `new AmqpUploadQueue` (the
 * sentinel) and the provider's own `new AmqpUploadQueue([...])` both ran the
 * constructor's inline `class_exists(\AMQPConnection::class)` guard, so on an
 * image without ext-amqp the assertions about the *backup queue driver* never
 * executed. The CI image has no ext-amqp; this machine does. A green run here
 * was evidence about the machine, not about the code.
 *
 * So the transport-availability fact is now an injected seam
 * (AmqpExtension), the queue exposes its effective broker config
 * (brokerConfig(), dial-free), and each test declares which answer it is
 * asserting: the amqp arm runs on machines with AND without the extension, the
 * refusal arm runs on machines with AND without it. The suite is green in
 * both environments because it asks the same question in both.
 */
final class BackupQueueDriverTest extends TestCase
{
    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    /**
     * The ambient declaration is process-wide, so every test starts from
     * detection and leaves detection behind it. Without this, one test's
     * `declare()` would silently disable the refusal for every test that runs
     * after it in the same process.
     */
    protected function setUp(): void
    {
        parent::setUp();

        AmqpExtension::detect();
    }

    protected function tearDown(): void
    {
        AmqpExtension::detect();

        parent::tearDown();
    }

    private function resolveQueue(): UploadQueueContract
    {
        return $this->app->make(TstoreCommand::QUEUE_KEY);
    }

    /**
     * Stands in for the upload queue a host ALREADY installed on the tstore
     * key — async, like the amqp driver would be.
     *
     * Deliberately not an AmqpUploadQueue: the property under test in
     * test_the_shipped_default_is_sync_and_defers_to_the_tstore_queue_key is
     * binding PRECEDENCE (the backup module must not bind over a decision the
     * host already made), and the sentinel's transport is irrelevant to it.
     * The old test constructed a real AmqpUploadQueue here, which is what made
     * it error on a machine without ext-amqp.
     */
    private function installedTstoreQueue(): UploadQueueContract
    {
        return new class implements UploadQueueContract
        {
            public function enqueue(array $payload): string
            {
                return 'installed-queue';
            }

            public function consume(callable $handler, int $max = 1): int
            {
                return 0;
            }

            public function isAsync(): bool
            {
                return true; // the amqp-shaped host queue
            }
        };
    }

    public function test_the_shipped_default_is_sync(): void
    {
        // 'sync' is what the published config ships and what the provider reads
        // when the key is absent, so assert the shipped value itself rather
        // than only the behaviour of a value the test wrote.
        self::assertSame('sync', $this->app['config']->get('teleframe.store.backup.queue'));
    }

    public function test_the_shipped_default_defers_to_the_tstore_queue_key(): void
    {
        // The shipped default ('sync') must not change behaviour for a host
        // that already picked a driver on the tstore key — the backup module
        // must NOT bind over that key, or a host's amqp tstore queue would be
        // silently swapped for an inline sync upload.
        //
        // The binding below stands in for the one TstoreCommand installs; the
        // provider is then registered over it, and the sentinel is asserted to
        // SURVIVE. (Binding the sentinel after registering would clobber the
        // provider's own binding and prove nothing about it.)
        $sentinel = $this->installedTstoreQueue();
        $this->app['config']->set('teleframe.store.backup.queue', 'sync');
        $this->app['config']->set('teleframe.tstore.queue', 'amqp');
        $this->app->bind(TstoreCommand::QUEUE_KEY, static fn (): UploadQueueContract => $sentinel);

        (new StoreBackupServiceProvider($this->app))->register();

        self::assertSame($sentinel, $this->resolveQueue(), 'the sync default must leave the tstore queue binding untouched');
    }

    public function test_the_configured_amqp_driver_builds_the_amqp_queue_with_the_configured_broker(): void
    {
        // Before the fix this key was read by nothing: the queue stayed sync
        // no matter what the operator configured. So the driver must reach the
        // queue, the queue must be the amqp one, and the broker the operator
        // configured must be the broker it dials.
        //
        // The transport fact is DECLARED here, not inherited: the provider's
        // closure constructs the queue as `new AmqpUploadQueue([...])`, and
        // construction dials nothing, so pinning the transport is enough to
        // assert the wiring on a CI image with no ext-amqp. On this machine the
        // same assertions run with the extension present.
        AmqpExtension::declare(true);

        $this->app['config']->set('teleframe.store.backup.queue', 'amqp');
        $this->app['config']->set('teleframe.tstore.queue', 'sync');
        $this->app['config']->set('teleframe.tstore.amqp.host', 'broker.teleframe.test');
        $this->app['config']->set('teleframe.tstore.amqp.port', 5673);
        $this->app['config']->set('teleframe.tstore.amqp.vhost', '/tstore');
        $this->app['config']->set('teleframe.tstore.amqp.user', 'tstore-uploader');
        $this->app['config']->set('teleframe.tstore.amqp.password', 'not-a-real-secret');
        (new StoreBackupServiceProvider($this->app))->register();

        $queue = $this->resolveQueue();

        self::assertInstanceOf(AmqpUploadQueue::class, $queue, 'the declared queue driver must reach the queue — this is the key that was read by nothing');
        self::assertNotInstanceOf(SyncUploadQueue::class, $queue);

        $broker = $queue->brokerConfig();
        self::assertSame('broker.teleframe.test', $broker['host'], 'the configured broker host must reach the queue');
        self::assertSame(5673, $broker['port'], 'the configured broker port must reach the queue');
        self::assertSame('/tstore', $broker['vhost']);
        self::assertSame('tstore-uploader', $broker['user'], 'the configured broker identity must reach the queue');
        self::assertSame('not-a-real-secret', $broker['pass']);

        self::assertTrue($queue->isAsync(), 'the amqp driver is the async one — teleframe:tstore gates on this');
    }

    public function test_the_configured_amqp_driver_refuses_by_name_when_the_transport_is_absent(): void
    {
        // THE ARM CI WAS PROVING BY ACCIDENT, now asserted on purpose: with
        // no ext-amqp the host that configured `amqp` is told so BY NAME. It is
        // not handed a SyncUploadQueue and left believing its backups are
        // queued while the dump uploads inline on the triggering request.
        AmqpExtension::declare(false);

        $this->app['config']->set('teleframe.store.backup.queue', 'amqp');
        $this->app['config']->set('teleframe.tstore.queue', 'sync');
        (new StoreBackupServiceProvider($this->app))->register();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ext-amqp is not loaded');

        $this->resolveQueue();
    }

    public function test_the_constructor_itself_is_where_the_refusal_is_decided(): void
    {
        // The provider arm above proves the wiring refuses; this proves the
        // refusal lives in the queue class itself, so every way of building it
        // refuses — not just the one path a test happened to take. On a machine
        // WITHOUT ext-amqp this is the only reachable shape, and it must say
        // the same thing.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(AmqpExtension::missingMessage());

        new AmqpUploadQueue(extension: AmqpExtension::absent());
    }

    public function test_the_ambient_answer_is_detection_unless_a_caller_declares_one(): void
    {
        // The seam must not become a way to disable the refusal by accident:
        // undeclared, the answer is what this process actually has, and a
        // declaration is visible and reversible. This also catches a leaked
        // declaration from another test in the same process.
        self::assertSame(extension_loaded('amqp'), AmqpExtension::detected()->isLoaded());

        AmqpExtension::declare(true);
        self::assertTrue(AmqpExtension::detected()->isLoaded(), 'a declared fact is what the constructor consults');

        AmqpExtension::detect();
        self::assertSame(extension_loaded('amqp'), AmqpExtension::detected()->isLoaded(), 'detect() restores detection');
    }

    public function test_an_unknown_queue_driver_fails_loudly(): void
    {
        $this->app['config']->set('teleframe.store.backup.queue', 'carrier-pigeon');
        (new StoreBackupServiceProvider($this->app))->register();

        // Silently degrading to sync would leave the host believing backups
        // are queued while the dump uploads inline on the triggering request.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected sync|amqp');

        $this->resolveQueue();
    }
}
