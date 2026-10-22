<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use RuntimeException;
use Throwable;

/**
 * Offline Bot Client terminal for the notification-core tests: records every
 * call (method + params), never touches the network, and can be armed to
 * throw (transport-failure path).
 *
 * IT REFUSES A LOCAL PATH AS A DOCUMENT (D1). This double used to
 * `file_get_contents($params['document'])`, which resolved a bare local
 * filesystem path and so silently MODELLED a capability the wire does not
 * have: Telegram's `sendDocument` accepts only a `file_id`, an HTTP URL, or
 * `attach://<name>` WITH a `multipart/form-data` body, and `BotClient::call()`
 * posts JSON, so a local path was rejected by the API and the report was lost
 * — on the default transport, i.e. on every shipped install. The suite was
 * green precisely because this double read the file the API never would.
 *
 * So a readable local file handed to `sendDocument` now FAILS LOUDLY here,
 * naming the defect. A green test that reaches this double with a path means
 * the multipart translation was removed.
 */
final class FakeBotClient extends BotClient
{
    /** @var list<array{method: string, params: array<string, mixed>}> */
    public array $calls = [];

    /** @var list<string|null> contents of each sendDocument payload (null on read failure) */
    public array $documents = [];

    /** @var Throwable|null when set, call() throws this instead of recording */
    public ?Throwable $failure = null;

    /** Every call() that reached the terminal, including one that failed. */
    public int $attempts = 0;

    public function __construct()
    {
        parent::__construct('test-token', null, 1, 0);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $method, array $params = []): array
    {
        $this->attempts++;

        if ($this->failure !== null) {
            throw $this->failure;
        }

        $this->assertNoLocalPathAsDocument($method, $params);

        $this->calls[] = ['method' => $method, 'params' => $params];

        if ($method === 'sendDocument' && isset($params['document']) && is_string($params['document'])) {
            $content = @file_get_contents($params['document']);
            $this->documents[] = $content === false ? null : $content;
        }

        return ['ok' => true, 'result' => ['message_id' => 1]];
    }

    /**
     * Record an attachment send the way the WIRE carried it — a multipart
     * `attach://` reference plus the part's real bytes — so a test that
     * watches `$bot->calls` / `$bot->documents` watches what actually goes
     * out, whether it travelled by JSON (`call()`) or by multipart
     * (`BotApiTransport`'s multipart seam, which calls this).
     *
     * @param  array<string, mixed>  $fields
     * @param  array{name: string, mime: string, bytes: string}  $file
     */
    public function recordMultipart(string $method, array $fields, array $file): void
    {
        $this->calls[] = ['method' => $method, 'params' => $fields];
        $this->documents[] = $file['bytes'];
    }

    /**
     * The wire's own rule, enforced: a `document` that is a readable local
     * file cannot be expressed in a JSON body, so this double must not
     * pretend otherwise.
     *
     * @param  array<string, mixed>  $params
     */
    private function assertNoLocalPathAsDocument(string $method, array $params): void
    {
        $document = $params['document'] ?? null;
        if (! is_string($document) || $document === '') {
            return;
        }

        // The reference shapes FIRST: `is_file()` on `attach://x` or an
        // `http://` URL makes PHP look for a stream wrapper named "attach"
        // and warn, which a host's error handler turns into an exception.
        if (str_starts_with($document, 'attach://')
            || str_starts_with($document, 'http://')
            || str_starts_with($document, 'https://')) {
            return;
        }

        if (! is_file($document)) {
            return;
        }

        throw new RuntimeException(
            'FakeBotClient: '.$method.' was handed the local path "'.$document.'" as its document. '
            .'The Bot API accepts only a file_id, an HTTP URL, or attach://<name> with a '
            .'multipart/form-data body — and BotClient::call() posts JSON. BotApiTransport must '
            .'translate a local path into a real multipart attach BEFORE the call reaches BotClient.',
        );
    }
}
