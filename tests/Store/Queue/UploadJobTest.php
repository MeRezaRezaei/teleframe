<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Queue;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\Queue\UploadJob;
use PHPUnit\Framework\TestCase;

/**
 * UploadJob (verbatim S9): the JSON queue payload for one store put.
 * Covers the toArray/fromArray roundtrip, the passphrase-less raw variant,
 * strict rejection of a malformed payload — and the two things a queue
 * message must NOT be able to do: name an arbitrary path for the worker to
 * read/delete, and carry the vault passphrase.
 */
final class UploadJobTest extends TestCase
{
    /** @var list<string> */
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();
        UploadJob::forgetSourceRoots();
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            @unlink($path);
        }
        $this->created = [];
        UploadJob::forgetSourceRoots();
        parent::tearDown();
    }

    /** A real file inside the default (temp) root. */
    private function tempFile(string $contents = 'x'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tf-upload-test-');
        file_put_contents($path, $contents);
        $this->created[] = $path;

        return $path;
    }

    /** @param  array<string, mixed>  $overrides */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'account_id' => 7,
            'bucket' => 'media-pool',
            'name' => 'a.txt',
            'mode' => 'raw',
            'source_path' => $this->tempFile(),
            'size' => 1,
        ];
    }

    public function test_a_source_inside_the_workspace_root_is_accepted(): void
    {
        $job = UploadJob::fromArray($this->payload());

        $this->assertSame('a.txt', $job->name);
    }

    public function test_a_source_outside_every_workspace_root_is_refused(): void
    {
        // Someone who can publish to tstore.upload must not be able to name
        // any path the worker can reach: fopen()+unlink() on an arbitrary path
        // is a local file read (exfiltrated to a Telegram channel) plus an
        // arbitrary delete.
        $outside = tempnam(sys_get_temp_dir(), 'tf-outside-');
        file_put_contents($outside, 'some other file');
        $this->created[] = $outside;

        // Narrow the root to a private directory the job does not live in.
        $root = sys_get_temp_dir().'/tf-root-'.bin2hex(random_bytes(4));
        mkdir($root);
        $this->created[] = $root;
        UploadJob::allowSourceRoot($root);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside every registered workspace root');
        UploadJob::fromArray($this->payload(['source_path' => $outside]));
    }

    public function test_a_symlink_escaping_the_root_cannot_smuggle_the_path(): void
    {
        $root = sys_get_temp_dir().'/tf-root-'.bin2hex(random_bytes(4));
        mkdir($root);
        $this->created[] = $root;

        $target = tempnam(sys_get_temp_dir(), 'tf-target-');
        file_put_contents($target, 'secret');
        $this->created[] = $target;

        $link = $root.'/looks-local';
        symlink($target, $link);

        UploadJob::allowSourceRoot($root);

        // realpath() resolves the symlink, so the job is judged by where the
        // bytes really live — outside the root.
        $this->expectException(InvalidArgumentException::class);
        UploadJob::fromArray($this->payload(['source_path' => $link]));
    }

    public function test_a_traversal_path_out_of_the_root_is_refused(): void
    {
        $root = sys_get_temp_dir().'/tf-root-'.bin2hex(random_bytes(4));
        mkdir($root);
        $this->created[] = $root;
        UploadJob::allowSourceRoot($root);

        $this->expectException(InvalidArgumentException::class);
        UploadJob::fromArray($this->payload(['source_path' => $root.'/../../etc/hostname']));
    }

    public function test_a_path_that_resolves_to_no_file_is_refused_before_any_open(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UploadJob::fromArray($this->payload(['source_path' => sys_get_temp_dir().'/tf-does-not-exist-'.bin2hex(random_bytes(4))]));
    }

    public function test_the_wire_payload_never_carries_the_passphrase(): void
    {
        $source = $this->tempFile();
        $job = new UploadJob(
            accountId: 7,
            bucket: 'media-pool',
            name: 'docs/q3.zip',
            mode: 'sealed',
            sourcePath: $source,
            size: 1_048_576,
            chunkShas: ['ab'.str_repeat('1', 62), 'cd'.str_repeat('2', 62)],
            passphrase: 'hunter2-secret',
        );

        $array = $job->toArray();
        $encoded = (string) json_encode($array);

        $this->assertArrayNotHasKey('passphrase', $array);
        $this->assertTrue($array['has_passphrase'], 'the row still says the job was sealed');
        $this->assertStringNotContainsString('hunter2-secret', $encoded);

        // The non-secret half of the row still roundtrips.
        $this->assertSame(7, $array['account_id']);
        $this->assertSame('media-pool', $array['bucket']);
        $this->assertSame('docs/q3.zip', $array['name']);
        $this->assertSame('sealed', $array['mode']);
        $this->assertSame($source, $array['source_path']);
        $this->assertSame(1_048_576, $array['size']);
        $this->assertSame(['ab'.str_repeat('1', 62), 'cd'.str_repeat('2', 62)], $array['chunk_shas']);
    }

    public function test_a_raw_job_roundtrips_with_its_chunk_plan(): void
    {
        $source = $this->tempFile();
        $job = new UploadJob(7, 'media-pool', 'docs/q3.zip', 'raw', $source, 1_048_576, ['ab'.str_repeat('1', 62)]);

        $restored = UploadJob::fromArray($job->toArray());

        $this->assertSame('raw', $restored->mode);
        $this->assertSame($source, $restored->sourcePath);
        $this->assertSame(1_048_576, $restored->size);
        $this->assertSame(['ab'.str_repeat('1', 62)], $restored->chunkShas);
        $this->assertNull($restored->passphrase);
    }

    public function test_a_sealed_job_is_refused_loudly_instead_of_uploading_unsealed(): void
    {
        // The secret is deliberately absent from the wire, so a replayed sealed
        // job has no key. Silently dropping the mode would upload the object
        // in the clear; the job must be refused instead.
        $payload = $this->payload(['mode' => 'sealed', 'has_passphrase' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot ride the queue');
        UploadJob::fromArray($payload);
    }

    public function test_a_sealed_job_carrying_a_passphrase_on_the_wire_is_also_refused(): void
    {
        $payload = $this->payload(['mode' => 'sealed', 'passphrase' => 'smuggled']);

        $this->expectException(InvalidArgumentException::class);
        UploadJob::fromArray($payload);
    }

    public function test_raw_job_roundtrips(): void
    {
        $source = $this->tempFile();
        $job = new UploadJob(1, 'media-pool', 'a.txt', 'raw', $source, 10);
        $restored = UploadJob::fromArray($job->toArray());

        $this->assertSame('raw', $restored->mode);
        $this->assertNull($restored->passphrase);
        $this->assertSame([], $restored->chunkShas);
    }

    public function test_malformed_payload_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UploadJob::fromArray(['bucket' => 'media-pool', 'name' => 'a.txt']);
    }

    public function test_a_registered_root_must_exist_to_grant_anything(): void
    {
        UploadJob::allowSourceRoot(sys_get_temp_dir().'/tf-not-a-dir-'.bin2hex(random_bytes(4)));

        $this->assertSame([realpath(sys_get_temp_dir())], UploadJob::sourceRoots());
    }
}
