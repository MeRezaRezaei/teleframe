<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for businessAwayMessage of BusinessAwayMessage.
 */
final class BusinessAwayMessageData extends TlBusinessAwayMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'offlineOnly' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $offlineOnly,
    public int $shortcutId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessAwayMessageScheduleAbstractData $schedule,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessRecipientsAbstractData $recipients,
    ) {
    }
}
