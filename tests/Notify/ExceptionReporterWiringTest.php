<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use RuntimeException;

/**
 * The wiring Task 1 claimed and did not have: with
 * teleframe.notify.exceptions.enabled on, an exception handed to the HOST's
 * real Laravel exception handler must actually reach the notification path —
 * no host code, no `Notify::report()` by hand. The handler used here is
 * Illuminate\Foundation\Exceptions\Handler straight out of the container, the
 * same object a real request would report through.
 *
 * And the second, cardinal property: a notification failure must never
 * replace the exception being reported. Laravel runs reportable callbacks
 * OUTSIDE its own try/catch, so a throwing callback here would surface
 * "telegram is down" to the operator in place of the real failure.
 */
final class ExceptionReporterWiringTest extends TestbenchTestCase
{
    private string $tmp;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    protected function setUp(): void
    {
        // Before parent::setUp(): the provider boots inside it, and the
        // reporter reads teleframe.notify.exceptions.enabled at boot.
        $this->tmp = sys_get_temp_dir().'/tframe-notify-exc-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);

        parent::setUp();

        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        $this->app->singleton(BotClient::class, fn (): FakeBotClient => new FakeBotClient);

        // D1: an attachment now travels as a real `multipart/form-data` POST,
        // which BotClient::call() cannot express (it posts JSON). So the HTTP
        // seam is faked at the layer that actually builds the multipart body —
        // and the requests it captured are what these tests now read. A local
        // path in a JSON body would be a 400 from Telegram and a lost report.
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);

        parent::tearDown();
    }

    protected function getEnvironmentSetUp(mixed $app): void
    {
        $app['config']->set('teleframe.notify.default_chat', '@ops');
        $app['config']->set('teleframe.notify.exceptions.enabled', true);
    }

    public function test_an_unhandled_exception_reaches_the_notification_path(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        // The host's own error path — no Notify::report(), no wiring.
        $handler->report(new RuntimeException('session refused on dc2'));

        $document = $this->uploadedAttachment();
        self::assertStringContainsString('RuntimeException', $document);
        self::assertStringContainsString('session refused on dc2', $document);
    }

    public function test_a_failing_notification_never_masks_the_original_exception(): void
    {
        // Armed to fail on EITHER wire: the JSON path (BotClient) and the
        // multipart path (the HTTP seam) both refuse, so the notification
        // fails whichever one the exception report takes.
        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        $bot->failure = new RuntimeException('telegram is down');
        Http::fake(['api.telegram.org/*' => Http::response('gateway down', 502)]);

        $handler = $this->app->make(ExceptionHandler::class);
        $original = new RuntimeException('the real failure');

        // Must not throw: the host keeps its own exception, its own log, and
        // the operator keeps the failure they actually needed to see.
        $handler->report($original);

        self::assertSame('the real failure', $original->getMessage());
        self::assertTrue($handler->shouldReport($original), 'the host handler still reports this exception itself');
        self::assertSame(1, $bot->attempts + $this->multipartAttempts(), 'the send was attempted, not skipped');
    }

    public function test_a_secret_shaped_value_never_reaches_the_chat(): void
    {
        $session = '1xAbCdEfGhIjKlMnOpQrStUvWxYz0123456789-_AbCdEfGhIjKlMnOpQrStUvWxYz01234';
        $token = '123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw';

        $this->app->make(ExceptionHandler::class)->report(
            new RuntimeException("auth failed: auth_key={$session} bot_token={$token}"),
        );

        $sent = $this->everythingSent();

        self::assertStringContainsString('auth failed', $sent, 'the operator still needs the message');
        self::assertStringNotContainsString($session, $sent);
        self::assertStringNotContainsString('AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw', $sent);
    }

    public function test_a_configured_topic_override_routes_the_exception_to_a_log_row(): void
    {
        // teleframe.notify.exceptions.topic is a shipped config key, so it
        // must be LIVE: with a topic set the exception goes out as a
        // level-tagged log line on that row, not as the HTML report.
        config([
            'teleframe.notify.exceptions.topic' => 'app.error',
            'teleframe.notify.exceptions.level' => 'critical',
        ]);

        $this->app->make(ExceptionHandler::class)->report(new RuntimeException('on the floor'));

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertNotSame([], $bot->calls);
        self::assertSame('sendMessage', $bot->calls[0]['method']);
        self::assertSame(5, $bot->calls[0]['params']['message_thread_id'], 'app.error thread');
        self::assertStringContainsString('[CRITICAL] app.error', (string) $bot->calls[0]['params']['text']);
        self::assertStringContainsString('on the floor', (string) $bot->calls[0]['params']['text']);
    }

    /** @return list<Request> every faked request the notification path dialled */
    private function sentRequests(): array
    {
        $seen = [];
        Http::assertSent(function (Request $request) use (&$seen): bool {
            $seen[] = $request;

            return true;
        });

        return $seen;
    }

    /** How many of those went out as a multipart attachment POST. */
    private function multipartAttempts(): int
    {
        $count = 0;
        foreach ($this->sentRequests() as $request) {
            if (str_contains($request->header('Content-Type')[0] ?? '', 'multipart/form-data')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * The bytes of the attachment that actually went out on the wire — read
     * out of the captured multipart body, so this is what Telegram would have
     * received, not what a test double decided to model.
     */
    private function uploadedAttachment(): string
    {
        foreach ($this->sentRequests() as $request) {
            $contentType = $request->header('Content-Type')[0] ?? '';
            if (! str_contains($contentType, 'multipart/form-data')) {
                continue;
            }
            // The part's own header line is what names the attach:// target.
            self::assertStringContainsString('filename=', $request->body());
            self::assertMatchesRegularExpression('/name="document"\\s*\\r\\n\\r\\nattach:\/\//', $request->body());

            return $request->body();
        }

        self::fail('no multipart/form-data attachment request reached the wire — the report was never uploaded');
    }

    /** Every byte of every call the notification path made, on either wire. */
    private function everythingSent(): string
    {
        $sent = '';
        foreach ($this->sentRequests() as $request) {
            $sent .= $request->body()."\n";
        }

        $bot = $this->app->make(BotClient::class);
        if ($bot instanceof FakeBotClient) {
            foreach ($bot->calls as $call) {
                $sent .= (string) ($call['params']['text'] ?? '')."\n".(string) ($call['params']['caption'] ?? '')."\n";
            }
        }

        return $sent;
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
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
