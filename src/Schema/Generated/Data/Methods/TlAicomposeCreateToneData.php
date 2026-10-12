<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method aicompose.createTone (crc32 4aa83913), returns AiComposeTone. */
final class TlAicomposeCreateToneData extends Data
{
    public const METHOD = 'aicompose.createTone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $displayAuthor,
    public int $emojiId,
    public string $title,
    public string $prompt,
    ) {
    }
}
