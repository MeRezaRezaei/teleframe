<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionWebViewDataSentMe of MessageAction.
 */
final class MessageActionWebViewDataSentMeData extends TlMessageActionAbstractData
{
    public function __construct(
    public string $text,
    public string $data,
    ) {
    }
}
