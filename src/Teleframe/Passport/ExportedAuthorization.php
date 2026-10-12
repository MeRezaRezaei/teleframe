<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * ExportedAuthorization (Cycle 18 W3) — the value object behind
 * `auth.exportedAuthorization#b434e2b8 id:long bytes:bytes` (the response of
 * `auth.exportAuthorization#e5bfffcd`).
 *
 * `bytes` IS a credential: it is the serialized authorization that lets its
 * holder act as the exporting user. So this class never renders it, never
 * exposes it in `toArray()` under a friendly key, and never logs it. `toArray()`
 * carries the id and the byte LENGTH only; `bytes()` is an explicit, named
 * accessor so every read is a deliberate act at the call site (the importer).
 *
 * Zero-regex; plain data.
 */
final class ExportedAuthorization
{
    public function __construct(
        public readonly int $id,
        public readonly string $bytes,
    ) {}

    /**
     * Parse the wire answer. Loud on anything that is not a usable
     * authorization: an id must be positive and the bytes non-empty, or the
     * id would be silently wrong at import time.
     *
     * @param  array<string, mixed>  $response
     *
     * @throws PassportLoginException
     */
    public static function fromResponse(array $response): self
    {
        $id = $response['id'] ?? null;
        $bytes = $response['bytes'] ?? null;

        if (! is_int($id) && ! (is_string($id) && ltrim($id, '0123456789') === '')) {
            throw new PassportLoginException(
                'auth.exportAuthorization returned no usable id (the authorization cannot be imported)'
            );
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new PassportLoginException(
                'auth.exportAuthorization returned empty bytes (the authorization cannot be imported)'
            );
        }

        return new self((int) $id, $bytes);
    }

    /**
     * Credential-free representation. The bytes are deliberately ABSENT — only
     * their length, so a status line can prove an export happened without
     * putting an authorization anywhere it could be logged or persisted.
     *
     * @return array{id: int, bytes_length: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'bytes_length' => strlen($this->bytes),
        ];
    }
}
