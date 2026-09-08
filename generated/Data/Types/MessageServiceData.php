<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageService of Message.
 */
final class MessageServiceData extends TlMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'out' => ['flags', 1],
        'mentioned' => ['flags', 4],
        'mediaUnread' => ['flags', 5],
        'reactionsArePossible' => ['flags', 9],
        'silent' => ['flags', 13],
        'post' => ['flags', 14],
        'legacy' => ['flags', 19],
    ];

    public function __construct(
    public int $flags,
    public ?bool $out,
    public ?bool $mentioned,
    public ?bool $mediaUnread,
    public ?bool $reactionsArePossible,
    public ?bool $silent,
    public ?bool $post,
    public ?bool $legacy,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $savedPeerId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageReplyHeaderAbstractData $replyTo,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageActionAbstractData $action,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageReactionsAbstractData $reactions,
    public ?int $ttlPeriod,
    ) {
    }
}
