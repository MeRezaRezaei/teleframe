<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use InvalidArgumentException;

/**
 * AttachmentFile (design §2, §7): the typed file payload the long section
 * of a notification becomes — {name, path, mime, bytes}. The design names
 * this class (PendingNotify::attach(AttachmentFile), §9 first-slice list);
 * the JSON wire shape (NotificationJob::attachment) carries the same four
 * keys, so fromArray()/toArray() are the wire round-trip. Immutable typed
 * value object: zero-regex, plain-PHP constructible, no container calls.
 */
final class AttachmentFile
{
    public function __construct(
        private readonly string $name,
        private readonly string $path,
        private readonly string $mime,
        private readonly int $bytes,
    ) {}

    /**
     * @param  array{name?: mixed, path?: mixed, mime?: mixed, bytes?: mixed}  $row
     */
    public static function fromArray(array $row): self
    {
        $name = $row['name'] ?? null;
        $path = $row['path'] ?? null;
        $mime = $row['mime'] ?? null;
        $bytes = $row['bytes'] ?? null;

        if (! is_string($name) || $name === ''
            || ! is_string($path) || $path === ''
            || ! is_string($mime) || $mime === ''
            || ! is_int($bytes) || $bytes < 0) {
            throw new InvalidArgumentException('attachment payload needs name, path, mime (non-empty strings) and bytes (int >= 0)');
        }

        return new self($name, $path, $mime, $bytes);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function mime(): string
    {
        return $this->mime;
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    /** @return array{name: string, path: string, mime: string, bytes: int} the wire shape (design §5 attachment block) */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path,
            'mime' => $this->mime,
            'bytes' => $this->bytes,
        ];
    }
}
