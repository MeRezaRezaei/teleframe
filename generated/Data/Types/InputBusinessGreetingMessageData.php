<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBusinessGreetingMessage of InputBusinessGreetingMessage.
 */
final class InputBusinessGreetingMessageData extends TlInputBusinessGreetingMessageAbstractData
{
    public function __construct(
    public int $shortcutId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputBusinessRecipientsAbstractData $recipients,
    public int $noActivityDays,
    ) {
    }
}
