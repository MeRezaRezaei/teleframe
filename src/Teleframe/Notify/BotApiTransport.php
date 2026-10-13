<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory as HttpFactory;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use RuntimeException;

/**
 * BotApiTransport — the shipped default transport: the notification core
 * over the Bot API, through the engine's own `BotClient`.
 *
 * It is a thin adapter, on purpose. `BotClient::call(string, array): array`
 * already IS the contract's shape, so nothing is translated, nothing is
 * cached, and no failure is intercepted: the Bot API's own error becomes
 * the exception the rate-limit policy classifies (a 429 stays a 429, with
 * its "retry after N" description intact — the whole reason the policy
 * reads that string).
 *
 * The point of the class existing at all is symmetry: `UserScopeTransport`
 * is the other half of `NotificationSender`, and "which transport" is then
 * a constructor argument instead of a rewrite of the send process. It also
 * makes the bot path injectable in tests without a `BotClient` subclass
 * when a stub is easier than a fake client.
 *
 * ONE SHAPE DOES NOT FIT `BotClient::call()` — an ATTACHMENT.
 * `NotificationSender` hands every transport the same Bot-API-shaped params,
 * and for `sendDocument` that means `document` carrying a LOCAL FILESYSTEM
 * PATH: the rendered report is written to a temp file and its path is what
 * travels. `BotClient::call()` posts that array with `asJson()`, and JSON
 * cannot carry a file. Telegram's `sendDocument` accepts only a `file_id`, an
 * HTTP URL, or `attach://<name>` together with a `multipart/form-data` body
 * holding the part under that name; a bare local path is none of the three,
 * so the API refused the send and the report was lost — on the DEFAULT
 * transport, i.e. on every shipped install. This class therefore detects a
 * real local file and sends it as genuine multipart, and passes every other
 * reference (`file_id`, URL, an already-resolved `attach://`) straight
 * through untouched, because those the Bot API accepts as-is.
 */
final class BotApiTransport implements NotificationTransportContract
{
    public function __construct(
        private readonly BotClient $bot,
        private readonly ?Closure $multipart = null,
        private readonly ?HttpFactory $http = null,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws NotifyException when an attachment's path exists but its bytes cannot be read
     * @throws TelegramException when the Bot API answers not-ok (a 429 keeps its code + description)
     */
    public function call(string $method, array $params = []): array
    {
        $path = $this->localAttachment($params);

        if ($path !== null) {
            return $this->sendMultipart($method, $params, $path);
        }

        return $this->bot->call($method, $params);
    }

    /** Log-safe label — no token, ever. */
    public function describe(): string
    {
        return 'bot-api';
    }

    /**
     * The local FILE this send must upload, or null when `document` is
     * something the Bot API already accepts.
     *
     * `attach://` is excluded explicitly: it is a multipart reference, not a
     * path, and re-uploading it would corrupt an already-assembled part.
     */
    private function localAttachment(array $params): ?string
    {
        $document = $params['document'] ?? null;

        if (! is_string($document) || $document === '') {
            return null;
        }

        if (str_starts_with($document, 'attach://')) {
            return null;
        }

        return is_file($document) ? $document : null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sendMultipart(string $method, array $params, string $path): array
    {
        if ($this->bot->botToken === '') {
            throw new RuntimeException('Telegram Bot token is required.');
        }

        $bytes = file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            // Same posture as MtProtoDocumentUploader::read(): a report that
            // cannot be read is refused, never silently reduced to its caption.
            throw new NotifyException(
                'teleframe.notify: cannot send the attachment — '.$path.' returned no readable bytes. '
                .'Refusing rather than sending a notification that silently lost its report.',
            );
        }

        $name = basename($path);

        $fields = $params;
        $fields['document'] = 'attach://'.$name;
        // `mime` is this package's own hint, not a Bot API sendDocument field.
        unset($fields['mime']);

        $file = [
            'name' => $name,
            'mime' => (string) ($params['mime'] ?? 'application/octet-stream'),
            'bytes' => $bytes,
        ];

        $poster = $this->multipart;

        return $poster !== null
            ? $poster($method, $fields, $file)
            : $this->postMultipart($method, $fields, $file);
    }

    /**
     * The real multipart POST. The reply is read exactly as `BotClient::call()`
     * reads it, so the rate-limit policy classifies a 429 from this path
     * identically to a 429 from the JSON path.
     *
     * @param  array<string, mixed>  $fields
     * @param  array{name: string, mime: string, bytes: string}  $file
     * @return array<string, mixed>
     */
    private function postMultipart(string $method, array $fields, array $file): array
    {
        $request = $this->factory()->timeout($this->bot->timeout);

        if ($this->bot->retries > 0) {
            $request = $request->retry($this->bot->retries, 100, throw: false);
        }

        $proxy = $this->proxyUrl();
        if ($proxy !== null) {
            $request = $request->withOptions(['proxy' => $proxy]);
        }

        $response = $request
            ->attach($file['name'], $file['bytes'], $file['name'], ['Content-Type' => $file['mime']])
            ->post(BotClient::API_BASE_URL.$this->bot->botToken.'/'.$method, $fields);

        $json = $response->json();

        // An empty body is not a delivered file. The status has to be checked:
        // Telegram answers 200 with a JSON body for every real call, so a
        // non-2xx with an empty body is a failure and must not be reported as
        // the `bot_result` success this path used to synthesise.
        if ($json === null && $response->body() === '' && $response->successful()) {
            return [
                'ok' => true,
                'result' => [
                    '_' => 'bot_result',
                    'method' => $method,
                    'params' => $fields,
                ],
            ];
        }

        if (! $json || empty($json['ok'])) {
            $errorCode = is_array($json) ? ($json['error_code'] ?? $response->status()) : $response->status();
            $description = is_array($json) ? ($json['description'] ?? 'Telegram Bot API error') : 'Telegram Bot API error';

            throw new TelegramException("Telegram Bot API [{$errorCode}]: {$description}", (int) $errorCode);
        }

        return $json;
    }

    /**
     * The proxy hop, in the same shape `BotClient::call()` builds it, so an
     * attachment goes out through the host's configured proxy exactly as a
     * JSON send does.
     */
    private function proxyUrl(): ?string
    {
        $proxy = $this->bot->proxyConfig;
        if (empty($proxy['host']) || empty($proxy['port'])) {
            return null;
        }

        $proxyType = strtolower((string) ($proxy['type'] ?? 'socks5'));
        $auth = '';

        if (! empty($proxy['username']) && ! empty($proxy['password'])) {
            $auth = $proxy['username'].':'.$proxy['password'].'@';
        }

        return $proxyType.'://'.$auth.$proxy['host'].':'.$proxy['port'];
    }

    /** The container's factory when a host bound one, else a fresh factory (plain PHP). */
    private function factory(): HttpFactory
    {
        if ($this->http !== null) {
            return $this->http;
        }

        $container = Container::getInstance();
        if ($container->bound(HttpFactory::class)) {
            return $container->make(HttpFactory::class);
        }

        return new HttpFactory;
    }
}
