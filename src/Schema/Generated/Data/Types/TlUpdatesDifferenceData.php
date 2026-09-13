<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updates.difference of updates.Difference.
 */
final class TlUpdatesDifferenceData extends TlUpdatesDifferenceAbstractData
{
    public function __construct(
    public array $newMessages,
    public array $newEncryptedMessages,
    public array $otherUpdates,
    public array $chats,
    public array $users,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUpdatesStateAbstractData $state,
    ) {
    }
}
