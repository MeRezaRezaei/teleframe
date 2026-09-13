<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.translateText (crc32 a5eec345), returns messages.TranslatedText. */
final class TlMessagesTranslateTextData extends Data
{
    public const METHOD = 'messages.translateText';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public ?array $id,
    public ?array $text,
    public string $toLang,
    public ?string $tone,
    ) {
    }
}
