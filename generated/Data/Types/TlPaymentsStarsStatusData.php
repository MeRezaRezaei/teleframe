<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for payments.starsStatus of payments.StarsStatus.
 */
final class TlPaymentsStarsStatusData extends TlPaymentsStarsStatusAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $balance,
    public ?array $subscriptions,
    public ?string $subscriptionsNextOffset,
    public ?int $subscriptionsMissingBalance,
    public ?array $history,
    public ?string $nextOffset,
    public array $chats,
    public array $users,
    ) {
    }
}
