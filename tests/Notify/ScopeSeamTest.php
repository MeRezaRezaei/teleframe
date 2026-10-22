<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\LazyUserScope;
use MeRezaRezaei\Teleframe\Notify\MtProtoDocumentUploader;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * The notify module's shared scope seam: `LazyUserScope` (one memoized
 * user-account scope for every user-scope seam) and
 * `MtProtoDocumentUploader` (the production `uploader` seam that turns a
 * rendered report into an `inputMediaUploadedDocument`).
 *
 * Both are proven through an INJECTED double, never through whatever the
 * machine happens to have: no socket, no session string, no credential, and
 * nothing that lets the environment pick a branch. The upload path's wire
 * keys are asserted against the TL registry's own declarations
 * (`upload.saveFilePart#b304a621`, `upload.saveBigFilePart#de7b673d`,
 * `inputFile#f52ff27f`, `inputMediaUploadedDocument#37c9330`,
 * `documentAttributeFilename#15590068`), which is the strongest evidence
 * available offline — and no more than that, because this path has never
 * run against a live Telegram user session.
 */
final class ScopeSeamTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-notify-scopeseam-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $file) {
            is_file($file) && unlink($file);
        }
        @rmdir($this->tmp);
    }

    public function test_the_scope_is_not_resolved_at_construction(): void
    {
        $dialer = new FakeScopeDialer;
        $scope = new LazyUserScope($dialer->resolver());

        self::assertFalse($scope->resolved(), 'a notifier that has not sent must not have dialled Telegram at bind time');
        self::assertSame(0, $scope->resolutions(), 'construction must not invoke the resolver, even once');
        self::assertSame(0, $dialer->resolutions, 'the injected resolver is the only thing that can dial, and it did not run');
        self::assertSame([], $dialer->calls);
    }

    public function test_the_scope_resolves_on_first_use_and_is_memoized(): void
    {
        $dialer = new FakeScopeDialer;
        $scope = new LazyUserScope($dialer->resolver());

        $first = $scope->scope();
        $second = $scope->scope();

        self::assertSame($first, $second, 'both seams must read ONE resolved scope, not a fresh one per use');
        self::assertTrue($scope->resolved());
        self::assertSame(1, $scope->resolutions(), 'a second send must not dial the account a second time');
        self::assertSame(1, $dialer->resolutions, 'the resolver ran exactly once across two uses');
    }

    public function test_an_unbound_holder_refuses_loudly(): void
    {
        $scope = new LazyUserScope;

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no MTProto user scope bound');

        $scope->scope();
    }

    public function test_a_throwing_resolver_is_reported_as_no_scope_bound(): void
    {
        $scope = new LazyUserScope(static fn (): object => throw new RuntimeException('vault locked'));

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('no MTProto user scope bound');

        $scope->scope();
    }

    public function test_a_non_object_resolution_is_refused(): void
    {
        $scope = new LazyUserScope(static fn (): string => 'session-string-pretending-to-be-a-scope');

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('not an object');

        $scope->scope();
    }

    public function test_a_scope_without_a_call_method_is_refused(): void
    {
        $scope = new LazyUserScope(static fn (): object => new stdClass);

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('does not expose call(string, array)');

        $scope->scope();
    }

    public function test_the_uploader_touches_neither_the_scope_nor_the_disk_at_construction(): void
    {
        $dialer = new FakeScopeDialer;
        $scope = new LazyUserScope($dialer->resolver());
        $path = $this->write('report.html', '<p>hello</p>');

        $uploader = new MtProtoDocumentUploader($scope);

        unset($uploader);

        self::assertFalse($scope->resolved(), 'building the uploader must not resolve the account scope');
        self::assertSame(0, $dialer->resolutions, 'no dial at construction: the uploader is a promise, not an upload');
        self::assertSame([], $dialer->calls);
        self::assertFileExists($path);
    }

    public function test_a_missing_file_is_refused_by_path_and_dials_nothing(): void
    {
        [$dialer, $uploader] = $this->uploader();
        $missing = $this->tmp.'/never-written.html';

        try {
            $uploader($missing, 'text/html', 'report.html');
            self::fail('a missing attachment must be a loud refusal, not a caption-only send');
        } catch (NotifyException $e) {
            self::assertStringContainsString($missing, $e->getMessage(), 'the refusal must name the path a human has to fix');
        }

        self::assertSame([], $dialer->calls, 'no scope was resolved and no part was dialled for a file that does not exist');
    }

    public function test_a_directory_is_refused_and_dials_nothing(): void
    {
        [$dialer, $uploader] = $this->uploader();

        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('not a regular file');

        try {
            $uploader($this->tmp, 'text/html', 'report.html');
        } finally {
            self::assertSame([], $dialer->calls, 'a directory is not an upload');
        }
    }

    public function test_an_empty_file_is_refused_and_dials_nothing(): void
    {
        [$dialer, $uploader] = $this->uploader();
        $empty = $this->write('empty.html', '');

        try {
            $uploader($empty, 'text/html', 'empty.html');
            self::fail('an empty report must be a loud refusal, not an empty document send');
        } catch (NotifyException $e) {
            self::assertStringContainsString('empty', $e->getMessage());
        }

        self::assertSame([], $dialer->calls, 'nothing was uploaded, so nothing was dialled');
    }

    public function test_a_small_file_uploads_one_part_and_returns_an_uploaded_document(): void
    {
        [$dialer, $uploader] = $this->uploader();
        $bytes = '<html><body><h1>incident 42</h1></body></html>';
        $path = $this->write('report.html', $bytes);

        $media = $uploader($path, 'text/html', 'incident-42.html');

        self::assertSame(['upload.saveFilePart'], $dialer->methods(), 'a small file is one saveFilePart, nothing else');

        $params = $dialer->onlyParams();
        self::assertSame(0, $params['file_part'], 'parts are numbered from zero');
        self::assertSame($bytes, $params['bytes'], 'the whole file rides in the one part');
        self::assertIsInt($params['file_id']);
        self::assertGreaterThanOrEqual(1, $params['file_id']);
        self::assertArrayNotHasKey('file_total_parts', $params, 'the small-file method declares no part count');

        self::assertSame('inputMediaUploadedDocument', $media['_']);
        self::assertSame('text/html', $media['mime_type'], 'the declared mime is what Telegram will serve');
        self::assertSame([
            ['_' => 'documentAttributeFilename', 'file_name' => 'incident-42.html'],
        ], $media['attributes'], 'the file name Telegram shows comes from the attribute, not the inputFile');
        self::assertSame('inputFile', $media['file']['_']);
        self::assertSame($params['file_id'], $media['file']['id'], 'the media must point at the id the parts were uploaded under');
        self::assertSame(1, $media['file']['parts']);
        self::assertSame('incident-42.html', $media['file']['name']);
        self::assertSame(md5($bytes), $media['file']['md5_checksum']);
    }

    public function test_a_file_over_the_big_threshold_uses_the_big_file_method(): void
    {
        [$dialer, $uploader] = $this->uploader();
        $size = MtProtoDocumentUploader::BIG_FILE_THRESHOLD + 1;
        $path = $this->write('big.bin', str_repeat('x', $size));
        $expectedParts = (int) ceil($size / MtProtoDocumentUploader::PART_SIZE);

        $media = $uploader($path, 'application/octet-stream', 'big.bin');

        self::assertSame(
            array_fill(0, $expectedParts, 'upload.saveBigFilePart'),
            $dialer->methods(),
            'above 10 MB the loop must be the big-file one, and saveFilePart must never appear',
        );
        self::assertSame($expectedParts, $media['file']['parts']);

        foreach ($dialer->calls as $index => $call) {
            self::assertSame($index, $call['params']['file_part'], 'every part carries its own index');
            self::assertSame(
                $expectedParts,
                $call['params']['file_total_parts'] ?? null,
                'upload.saveBigFilePart#de7b673d declares file_total_parts — the registry key, not the vault\'s older total_parts',
            );
            self::assertSame(
                $index === $expectedParts - 1 ? 1 : MtProtoDocumentUploader::PART_SIZE,
                strlen($call['params']['bytes']),
                'parts are 512 KB (TelegramVault\'s part size) except the last, which carries the remainder',
            );
        }

        self::assertSame($media['file']['id'], $dialer->calls[0]['params']['file_id'], 'the media points at the id the parts were uploaded under');
        self::assertGreaterThanOrEqual(1, $media['file']['id'], 'the file id is a random non-zero long');
        self::assertSame(
            [$media['file']['id']],
            array_values(array_unique(array_column(array_column($dialer->calls, 'params'), 'file_id'))),
            'one file_id per upload, across every part — a second id would orphan the parts already sent',
        );

        $full = 0;
        foreach ($dialer->calls as $call) {
            $full += strlen($call['params']['bytes']);
        }
        self::assertSame($size, $full, 'the parts together are the whole file, with no gap and no overlap');
    }

    public function test_two_seams_share_one_scope_for_one_upload(): void
    {
        $dialer = new FakeScopeDialer;
        $scope = new LazyUserScope($dialer->resolver());
        $uploader = new MtProtoDocumentUploader($scope);
        $path = $this->write('shared.html', 'shared report');

        $uploader($path, 'text/html', 'shared.html');
        $uploader($path, 'text/html', 'shared.html');

        self::assertSame(1, $scope->resolutions(), 'two uploads, one account scope: the whole point of the shared holder');
        self::assertCount(2, $dialer->calls, 'each upload still sends its own part');
    }

    public function test_a_failed_part_upload_is_never_swallowed(): void
    {
        [$dialer, $uploader] = $this->uploader();
        $path = $this->write('report.html', str_repeat('y', MtProtoDocumentUploader::PART_SIZE + 1));
        $dialer->failWith(new RuntimeException('FLOOD_WAIT_30'), 1);

        try {
            $uploader($path, 'text/html', 'report.html');
            self::fail('a part that failed to upload must never be reported as a success');
        } catch (RuntimeException $e) {
            self::assertSame('FLOOD_WAIT_30', $e->getMessage(), 'the rate-limit error reaches the notify queue, which owns the retry');
        }

        self::assertCount(1, $dialer->calls, 'the loop stops at the first failure instead of burning the rest of the parts');
    }

    /**
     * The uploader over a recording dialer, sharing one lazy scope.
     *
     * @return array{0: FakeScopeDialer, 1: MtProtoDocumentUploader}
     */
    private function uploader(): array
    {
        $dialer = new FakeScopeDialer;

        return [$dialer, new MtProtoDocumentUploader(new LazyUserScope($dialer->resolver()))];
    }

    /** Write a fixture file inside the per-test temp dir and return its path. */
    private function write(string $name, string $contents): string
    {
        $path = $this->tmp.'/'.$name;
        file_put_contents($path, $contents);

        return $path;
    }
}
