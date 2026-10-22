<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\BotApiTransport;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotificationTransportContract;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * D1 — AN ATTACHMENT MUST REACH TELEGRAM ON THE DEFAULT TRANSPORT.
 *
 * `NotificationSender` hands every transport the same Bot-API-shaped params,
 * and for `sendDocument` that means `document` carrying a LOCAL FILESYSTEM
 * PATH (the rendered report sits in a temp file). `BotClient::call()` posts
 * that array with `asJson()`, and JSON cannot carry a file: Telegram's
 * `sendDocument` accepts only a `file_id`, an HTTP URL, or `attach://<name>`
 * together with a `multipart/form-data` body holding the part under that name.
 * A bare local path in a JSON body is none of the three, so the API refused the
 * send and the report was lost — on the DEFAULT bot transport, i.e. on every
 * shipped install.
 *
 * The suite was green only because `FakeBotClient` did
 * `file_get_contents($params['document'])`, which resolved a local path and
 * silently modelled a capability the wire does not have. That is why this test
 * does NOT use the fake at its seam: it runs the REAL `BotApiTransport`
 * multipart poster against a recording HTTP factory, so what it asserts is the
 * request that actually goes out — multipart, `attach://`, real bytes.
 *
 * HONEST LIMIT: no live round-trip. `api.telegram.org` is not reachable from
 * this lane, so the proof is STRUCTURAL — the outgoing request's method, its
 * `Content-Type`, its form fields and the exact bytes of its file part. Only a
 * real Telegram account can confirm the API accepts the assembled request.
 *
 * Each test below is a restore-the-defect tripwire: put `$params['document'] =
 * $attachment['path']` back into `NotificationSender` (i.e. let the transport
 * see the raw path and post JSON) and the FIRST assertion of its test goes red.
 */
final class NotifyAttachmentReachesTelegramTest extends TestbenchTestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-attach-'.bin2hex(random_bytes(4));
        mkdir($this->tmp.'/tpl', 0777, true);
        mkdir($this->tmp.'/cache', 0777, true);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $file) {
            is_dir($file) ? rmdir($file) : unlink($file);
        }
        @rmdir($this->tmp);

        parent::tearDown();
    }

    /** THE headline proof: a report attachment goes out as real multipart with the real bytes. */
    public function test_the_outgoing_request_is_multipart_with_an_attach_name_and_the_real_bytes(): void
    {
        $html = "<!DOCTYPE html>\n<html><body><h1>Deploy summary</h1><p>the report body</p></body></html>";
        $path = $this->attachment($html);

        $spy = new OutgoingRequestSpy;
        $this->senderWith($spy)->send('Deploy summary', ['chat' => 4242], [
            'name' => 'report.html',
            'path' => $path,
            'mime' => 'text/html',
            'bytes' => strlen($html),
        ]);

        self::assertCount(1, $spy->requests(), 'the report was never sent at all');
        $request = $spy->last();

        // 1. It is a sendDocument...
        self::assertSame('POST', $request->method());
        self::assertStringContainsString('/sendDocument', $request->url());

        // 2. ...carried as multipart/form-data, which is the ONLY way Telegram
        //    accepts a file. A JSON body cannot express one — that was the defect.
        self::assertStringContainsString('multipart/form-data', $request->contentType());
        self::assertStringContainsString('boundary=', $request->contentType());

        // 3. the `document` field is an attach:// reference, not a filesystem path.
        $document = $request->field('document');
        self::assertNotNull($document, 'no `document` form field went out');
        self::assertStringStartsWith('attach://', $document);
        self::assertSame('attach://'.basename($path), $document);
        self::assertStringStartsWith('attach://', $document, 'the `document` field must never be a bare filesystem path');

        // 4. THE BYTES ARE THERE. The report is not a caption; it is the file.
        self::assertSame($html, $request->fileBytes(basename($path)), 'the attachment bytes did not reach the wire');
        self::assertSame(basename($path), $request->fileName(basename($path)));

        // 5. and the caption still carries the message.
        self::assertSame('Deploy summary', $request->field('caption'));

        // 6. the internal `mime` hint is not smuggled into the Bot API payload —
        //    it became the part's Content-Type instead.
        self::assertNull($request->field('mime'));
        self::assertSame(
            'text/html',
            $request->fileHeaders(basename($path))['Content-Type'] ?? null,
            'the mime hint belongs on the file part, not as a form field',
        );
    }

    /** A JSON-only `document` is the defect made visible at the HTTP layer. */
    public function test_the_never_posted_json_path_shape_is_exactly_what_this_fixes(): void
    {
        $path = $this->attachment('payload');
        $spy = new OutgoingRequestSpy;
        $this->senderWith($spy)->send('m', ['chat' => 1], [
            'name' => 'a.bin', 'path' => $path, 'mime' => 'application/octet-stream', 'bytes' => 7,
        ]);

        self::assertStringNotContainsString(
            'application/json',
            $spy->last()->contentType(),
            'the attachment went out as JSON, which is the pre-fix behaviour',
        );
        self::assertStringNotContainsString("\r\n\r\n".$path, $spy->last()->body());
    }

    /** Non-attachment calls are untouched: the JSON path is still one `BotClient::call()`. */
    public function test_a_message_without_an_attachment_still_goes_out_as_plain_json(): void
    {
        $bot = new FakeBotClient;
        $spy = new OutgoingRequestSpy;
        $transport = new BotApiTransport($bot, $spy->poster());

        $this->senderWith($spy, $transport)->send('[INFO] app.info', ['chat' => 77]);

        self::assertCount(1, $bot->calls);
        self::assertSame('sendMessage', $bot->calls[0]['method']);
        self::assertSame(77, $bot->calls[0]['params']['chat_id']);
        self::assertStringContainsString('[INFO] app.info', (string) $bot->calls[0]['params']['text']);
        self::assertCount(0, $spy->requests(), 'a plain message must not acquire a multipart body');
    }

    /** The transport must not intercept shapes the Bot API accepts as-is. */
    public function test_a_file_id_and_a_url_are_passed_through_untouched(): void
    {
        foreach (['AgACAgIAAxkBAAIB', 'https://example.test/report.html', 'attach://already'] as $reference) {
            $bot = new FakeBotClient;
            $spy = new OutgoingRequestSpy;
            (new BotApiTransport($bot, $spy->poster()))->call('sendDocument', [
                'chat_id' => 1,
                'document' => $reference,
                'caption' => 'c',
            ]);

            self::assertCount(0, $spy->requests(), $reference.' was wrongly treated as a local file');
            self::assertSame($reference, $bot->calls[0]['params']['document']);
        }
    }

    /** An unreadable attachment is refused loudly, never reduced to a caption. */
    public function test_an_unreadable_attachment_is_refused_loudly(): void
    {
        $this->expectException(NotifyException::class);
        $this->expectExceptionMessage('silently lost its report');

        $path = tempnam($this->tmp, 'empty-');
        self::assertIsString($path);
        file_put_contents($path, '');

        $this->senderWith(new OutgoingRequestSpy)->send('m', ['chat' => 1], [
            'name' => 'empty.bin', 'path' => $path, 'mime' => 'application/octet-stream', 'bytes' => 0,
        ]);
    }

    /** The FakeBotClient guard: it can no longer model a wire capability that does not exist. */
    public function test_the_fake_bot_client_refuses_a_raw_local_path_as_a_document(): void
    {
        $path = $this->attachment('x');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('multipart/form-data');

        (new FakeBotClient)->call('sendDocument', ['chat_id' => 1, 'document' => $path]);
    }

    /**
     * The negative control for the guard: the FakeBotClient DID resolve local
     * paths before, which is exactly why the suite was green over a wire that
     * rejects them. That behaviour is gone on purpose.
     */
    public function test_the_fake_no_longer_resolves_a_local_path_into_content(): void
    {
        $path = $this->attachment('the-only-copy');

        $threw = false;
        try {
            (new FakeBotClient)->call('sendDocument', ['chat_id' => 1, 'document' => $path]);
        } catch (\RuntimeException) {
            $threw = true;
        }

        self::assertTrue($threw, 'FakeBotClient silently modelled the wire capability again');
    }

    // ---- doubles ---------------------------------------------------------

    private function attachment(string $contents): string
    {
        $path = $this->tmp.'/report-'.bin2hex(random_bytes(4)).'.html';
        file_put_contents($path, $contents);

        return $path;
    }

    private function senderWith(OutgoingRequestSpy $spy, ?NotificationTransportContract $transport = null): NotificationSender
    {
        return new NotificationSender(
            new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'),
            new FakeBotClient,
            null,
            120,
            3,
            static function (): void {},
            $transport ?? new BotApiTransport(new FakeBotClient, $spy->poster()),
        );
    }
}
