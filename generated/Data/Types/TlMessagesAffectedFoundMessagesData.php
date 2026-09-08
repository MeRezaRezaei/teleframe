<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.affectedFoundMessages of messages.AffectedFoundMessages.
 */
final class TlMessagesAffectedFoundMessagesData extends TlMessagesAffectedFoundMessagesAbstractData
{
    public function __construct(
    public int $pts,
    public int $ptsCount,
    public int $offset,
    public array $messages,
    ) {
    }
}
