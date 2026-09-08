<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method aicompose.updateTone (crc32 903bcf59), returns AiComposeTone. */
final class TlAicomposeUpdateToneData extends Data
{
    public const METHOD = 'aicompose.updateTone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $tone,
    public mixed $displayAuthor,
    public ?int $emojiId,
    public ?string $title,
    public ?string $prompt,
    ) {
    }
}
