<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionPhoneCall of MessageAction.
 */
final class MessageActionPhoneCallData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'video' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $video,
    public int $callId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhoneCallDiscardReasonAbstractData $reason,
    public ?int $duration,
    ) {
    }
}
