<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\HtmlAttachment;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\MessageSegmenter;
use PHPUnit\Framework\TestCase;

/**
 * Context/file split (design §7, spec G6/G7): split() yields the two-section
 * shape {text, brief, metadata_block, attachment?}; exception/trace keys are
 * NEVER inlined; an over-budget brief keeps only a UTF-8-safe head plus the
 * truncation marker (the full text survives in the file); HtmlAttachment
 * renders ONE self-contained, escaped, browser-safe HTML document.
 */
final class SegmenterAttachmentTest extends TestCase
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

    public function test_split_returns_the_two_section_shape_without_attachment(): void
    {
        $split = $this->segmenter()->split(
            Level::Info,
            'backup completed',
            ['set' => 'default', 'bytes' => 48127],
            null,
            800,
        );

        self::assertSame(['text', 'brief', 'metadata_block'], array_keys($split));
        self::assertArrayNotHasKey('attachment', $split);
        self::assertStringContainsString('[INFO] app.info', $split['text']);
        self::assertStringContainsString('backup completed', $split['text']);
        self::assertSame('backup completed', $split['brief']);
        self::assertStringContainsString('set=default', $split['metadata_block']);
        self::assertStringContainsString('bytes=48127', $split['metadata_block']);
    }

    public function test_split_renders_an_attachment_for_the_long_body(): void
    {
        $split = $this->segmenter()->split(
            Level::Error,
            'head',
            ['account_id' => 7],
            str_repeat('long body ', 2000),
            800,
        );

        self::assertArrayHasKey('attachment', $split);
        $attachment = $split['attachment'];
        self::assertSame(['name', 'path', 'mime', 'bytes'], array_keys($attachment));
        self::assertSame('text/html', $attachment['mime']);
        self::assertFileExists($attachment['path']);
        self::assertSame((int) filesize($attachment['path']), $attachment['bytes']);
        self::assertStringStartsWith('report-', $attachment['name']);
        self::assertStringEndsWith('.html', $attachment['name']);
    }

    public function test_split_never_inlines_exception_or_trace_keys(): void
    {
        $split = $this->segmenter()->split(
            Level::Critical,
            'short brief',
            ['exception' => 'boom happened', 'trace' => ['frame #0'], 'account_id' => 7],
            null,
            800,
        );

        self::assertArrayHasKey('attachment', $split); // file-only keys force the file
        self::assertStringNotContainsString('boom happened', $split['text']);
        self::assertStringNotContainsString('exception', $split['metadata_block']);
        self::assertStringNotContainsString('trace', $split['metadata_block']);
        self::assertStringContainsString('account_id=7', $split['metadata_block']);
    }

    public function test_split_truncates_over_budget_brief_utf8_safe_with_marker(): void
    {
        // 'ش' is a 2-byte UTF-8 char — a naive byte cut would split it.
        $split = $this->segmenter()->split(Level::Info, str_repeat('ش', 600).' tail', [], null, 100);

        self::assertTrue(mb_check_encoding($split['brief'], 'UTF-8'));
        self::assertStringEndsWith('… [truncated]', $split['brief']);
        self::assertArrayHasKey('attachment', $split); // truncated => the full brief survives in the file
    }

    public function test_html_render_is_self_contained_and_escaped(): void
    {
        $file = (new HtmlAttachment)->render(
            'Deploy <script>alert(1)</script>',
            ['k' => '<b>&"x"'],
            'body <tag>',
            $this->tmp,
        );

        self::assertSame('text/html', $file['mime']);
        self::assertFileExists($file['path']);
        $html = (string) file_get_contents($file['path']);

        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<style>', $html);
        self::assertStringContainsString('</style>', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringContainsString('&lt;b&gt;', $html);
        self::assertStringContainsString('&lt;tag&gt;', $html);
        self::assertStringNotContainsString('http://', $html);
        self::assertStringNotContainsString('https://', $html);
        self::assertStringContainsString('Raw payload', $html);
    }

    public function test_html_render_escapes_values_but_keeps_the_raw_payload_readable(): void
    {
        $body = 'user said: hello & goodbye';
        $file = (new HtmlAttachment)->render('notice', [], $body, $this->tmp);
        $html = (string) file_get_contents($file['path']);

        self::assertStringContainsString('hello &amp; goodbye', $html);
        self::assertGreaterThan(0, $file['bytes']);
        self::assertSame((int) filesize($file['path']), $file['bytes']);
    }

    private function segmenter(): MessageSegmenter
    {
        return new MessageSegmenter(new HtmlAttachment, $this->tmp);
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
