<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method contacts.resolveUsername (crc32 725afbbc), returns contacts.ResolvedPeer. */
final class TlContactsResolveUsernameData extends Data
{
    public const METHOD = 'contacts.resolveUsername';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $username,
    public ?string $referer,
    ) {
    }
}
