<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Queue;

use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use PHPUnit\Framework\TestCase;

/**
 * SyncUploadQueue (verbatim S9): the "none queue" default — put() invokes
 * the handler inline in the caller's process. Covers inline dispatch with
 * the payload shape, the no-handler placeholder, consume-as-noop, and the
 * isAsync() gate that keeps teleframe:tstore work honest.
 */
final class SyncUploadQueueTest extends TestCase
{
    /** @var list<string> */
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            @unlink($path);
        }
        $this->created = [];
        parent::tearDown();
    }

    /**
     * A real file: UploadJob confines source_path to a registered workspace
     * root, so a payload naming a path that does not exist is refused before
     * any open — these tests exercise dispatch, not the confinement (that is
     * UploadJobTest's job).
     */
    private function source(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tf-sync-queue-');
        file_put_contents($path, 'x');
        $this->created[] = $path;

        return $path;
    }

    public function test_enqueue_dispatches_handler_inline(): void
    {
        $seen = null;
        $queue = new SyncUploadQueue(function (array $payload) use (&$seen): void {
            $seen = $payload;
        });

        $id = $queue->enqueue([
            'account_id' => 7,
            'bucket' => 'media-pool',
            'name' => 'docs/q3.zip',
            'mode' => 'sealed',
            'source_path' => $this->source(),
            'size' => 10,
        ]);

        $this->assertSame('7', $id);
        $this->assertSame('docs/q3.zip', $seen['name'] ?? null);
        $this->assertSame('sealed', $seen['mode'] ?? null);
    }

    public function test_enqueue_without_handler_is_a_noop(): void
    {
        $queue = new SyncUploadQueue;

        $this->assertSame('7', $queue->enqueue([
            'account_id' => 7,
            'bucket' => 'b',
            'name' => 'a.txt',
            'mode' => 'raw',
            'source_path' => $this->source(),
            'size' => 1,
        ]));
    }

    public function test_consume_is_noop_and_is_async_is_false(): void
    {
        $queue = new SyncUploadQueue;
        $handled = 0;
        $consumed = $queue->consume(function () use (&$handled): void {
            $handled++;
        });

        $this->assertSame(0, $consumed);
        $this->assertSame(0, $handled);
        $this->assertFalse($queue->isAsync(), 'sync queue must report non-async');
    }
}
