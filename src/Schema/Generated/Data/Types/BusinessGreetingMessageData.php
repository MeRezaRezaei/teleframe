<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for businessGreetingMessage of BusinessGreetingMessage.
 */
final class BusinessGreetingMessageData extends TlBusinessGreetingMessageAbstractData
{
    public function __construct(
    public int $shortcutId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessRecipientsAbstractData $recipients,
    public int $noActivityDays,
    ) {
    }
}
