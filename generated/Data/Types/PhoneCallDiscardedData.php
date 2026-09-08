<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneCallDiscarded of PhoneCall.
 */
final class PhoneCallDiscardedData extends TlPhoneCallAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'needRating' => ['flags', 2],
        'needDebug' => ['flags', 3],
        'video' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $needRating,
    public ?bool $needDebug,
    public ?bool $video,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhoneCallDiscardReasonAbstractData $reason,
    public ?int $duration,
    ) {
    }
}
