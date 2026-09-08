<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.dismissSuggestion (crc32 f50dbaa1), returns Bool. */
final class TlHelpDismissSuggestionData extends Data
{
    public const METHOD = 'help.dismissSuggestion';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $suggestion,
    ) {
    }
}
