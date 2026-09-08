<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteMessages (crc32 e58e95d2), returns messages.AffectedMessages. */
final class TlMessagesDeleteMessagesData extends Data
{
    public const METHOD = 'messages.deleteMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $revoke,
    public array $id,
    ) {
    }
}
