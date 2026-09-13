<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageFwdHeader of MessageFwdHeader.
 */
final class MessageFwdHeaderData extends TlMessageFwdHeaderAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'imported' => ['flags', 7],
        'savedOut' => ['flags', 11],
    ];

    public function __construct(
    public int $flags,
    public ?bool $imported,
    public ?bool $savedOut,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public ?string $fromName,
    public int $date,
    public ?int $channelPost,
    public ?string $postAuthor,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $savedFromPeer,
    public ?int $savedFromMsgId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $savedFromId,
    public ?string $savedFromName,
    public ?int $savedDate,
    public ?string $psaType,
    ) {
    }
}
