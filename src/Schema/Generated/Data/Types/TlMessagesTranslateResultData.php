<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.translateResult of messages.TranslatedText.
 */
final class TlMessagesTranslateResultData extends TlMessagesTranslatedTextAbstractData
{
    public function __construct(
    public array $result,
    ) {
    }
}
