<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messagePeerReaction of MessagePeerReaction.
 */
final class MessagePeerReactionData extends TlMessagePeerReactionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'big' => ['flags', 0],
        'unread' => ['flags', 1],
        'my' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $big,
    public ?bool $unread,
    public ?bool $my,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $reaction,
    ) {
    }
}
