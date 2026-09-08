<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for groupCallMessage of GroupCallMessage.
 */
final class GroupCallMessageData extends TlGroupCallMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'fromAdmin' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $fromAdmin,
    public int $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    public ?int $paidMessageStars,
    ) {
    }
}
