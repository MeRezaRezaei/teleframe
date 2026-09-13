<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.availableReactions of messages.AvailableReactions.
 */
final class TlMessagesAvailableReactionsData extends TlMessagesAvailableReactionsAbstractData
{
    public function __construct(
    public int $hash,
    public array $reactions,
    ) {
    }
}
