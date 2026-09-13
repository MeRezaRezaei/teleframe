<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.summarizeText (crc32 abbbd346), returns TextWithEntities. */
final class TlMessagesSummarizeTextData extends Data
{
    public const METHOD = 'messages.summarizeText';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $id,
    public ?string $toLang,
    public ?string $tone,
    ) {
    }
}
