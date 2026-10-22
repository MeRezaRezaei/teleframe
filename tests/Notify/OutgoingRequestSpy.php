<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

/**
 * A recording stand-in for the outbound multipart POST, for the tests that
 * must assert what the wire actually carries.
 *
 * It is deliberately NOT a fake `BotClient`: `BotClient::call()` posts JSON,
 * and no JSON body can carry a file. `BotApiTransport` builds a real
 * `multipart/form-data` request; this double records that request's method,
 * URL, headers, form fields and — the part that matters — the exact bytes of
 * the file part under the `attach://` name. So a test using it asserts the
 * OUTGOING REQUEST, not what a test double decided to model.
 */
final class OutgoingRequestSpy
{
    /** @var list<OutgoingRequest> */
    private array $requests = [];

    /**
     * The `$multipart` seam shape `BotApiTransport` takes:
     * (method, form fields, attached file) => decoded Bot API reply.
     *
     * @return \Closure(string, array<string, mixed>, array{name: string, mime: string, bytes: string}): array<string, mixed>
     */
    public function poster(): \Closure
    {
        return function (string $method, array $fields, array $file): array {
            $body = $this->body($fields, $file);

            $this->requests[] = new OutgoingRequest(
                'POST',
                'https://api.telegram.org/bottest-token/'.$method,
                ['Content-Type' => 'multipart/form-data; boundary='.OutgoingRequest::BOUNDARY],
                $fields,
                $file,
                $body,
            );

            return ['ok' => true, 'result' => ['message_id' => 1]];
        };
    }

    /** @return list<OutgoingRequest> */
    public function requests(): array
    {
        return $this->requests;
    }

    public function last(): OutgoingRequest
    {
        if ($this->requests === []) {
            throw new \RuntimeException('no request was recorded');
        }

        return $this->requests[count($this->requests) - 1];
    }

    /** The real bytes of the file part under $name. */
    public function fileBytes(string $name): ?string
    {
        return $this->last()->fileBytes($name);
    }

    /**
     * A `multipart/form-data` body in the shape Guzzle sends: each field as
     * its own part, then the file part carrying its filename.
     *
     * @param  array<string, mixed>  $fields
     * @param  array{name: string, mime: string, bytes: string}  $file
     */
    private function body(array $fields, array $file): string
    {
        $body = '';
        foreach ($fields as $name => $value) {
            $body .= '--'.OutgoingRequest::BOUNDARY."\r\n";
            $body .= 'Content-Disposition: form-data; name="'.$name."\"\r\n\r\n";
            $body .= (is_scalar($value) ? (string) $value : json_encode($value))."\r\n";
        }

        $body .= '--'.OutgoingRequest::BOUNDARY."\r\n";
        $body .= 'Content-Disposition: form-data; name="'.$file['name'].'"; filename="'.$file['name']."\"\r\n";
        $body .= 'Content-Type: '.$file['mime']."\r\n\r\n";
        $body .= $file['bytes']."\r\n";
        $body .= '--'.OutgoingRequest::BOUNDARY."--\r\n";

        return $body;
    }
}

/** One recorded outbound multipart request, with accessors over its parts. */
final class OutgoingRequest
{
    public const BOUNDARY = '----tframeBoundary0000000000000000';

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $fields
     * @param  array{name: string, mime: string, bytes: string}  $file
     */
    public function __construct(
        private readonly string $method,
        private readonly string $url,
        private readonly array $headers,
        private readonly array $fields,
        private readonly array $file,
        private readonly string $body,
    ) {}

    public function method(): string
    {
        return $this->method;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function contentType(): string
    {
        return $this->headers['Content-Type'] ?? '';
    }

    /** One form field's value, or null when the request carries no such field. */
    public function field(string $name): ?string
    {
        if (! array_key_exists($name, $this->fields)) {
            return null;
        }

        $value = $this->fields[$name];

        return is_scalar($value) ? (string) $value : json_encode($value);
    }

    /** The bytes of the file part named $name — null when there is no such part. */
    public function fileBytes(string $name): ?string
    {
        return $this->file['name'] === $name ? $this->file['bytes'] : null;
    }

    public function fileName(string $name): ?string
    {
        return $this->file['name'] === $name ? $this->file['name'] : null;
    }

    /** @return array<string, string> the file part's headers */
    public function fileHeaders(string $name): array
    {
        return $this->file['name'] === $name
            ? ['Content-Type' => $this->file['mime'], 'filename' => $this->file['name']]
            : [];
    }
}
