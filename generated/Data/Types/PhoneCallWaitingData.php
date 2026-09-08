<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneCallWaiting of PhoneCall.
 */
final class PhoneCallWaitingData extends TlPhoneCallAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'video' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $video,
    public int $id,
    public int $accessHash,
    public int $date,
    public int $adminId,
    public int $participantId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhoneCallProtocolAbstractData $protocol,
    public ?int $receiveDate,
    ) {
    }
}
