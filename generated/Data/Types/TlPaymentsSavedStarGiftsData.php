<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.savedStarGifts of payments.SavedStarGifts.
 */
final class TlPaymentsSavedStarGiftsData extends TlPaymentsSavedStarGiftsAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $chatNotificationsEnabled,
    public array $gifts,
    public ?string $nextOffset,
    public array $chats,
    public array $users,
    ) {
    }
}
