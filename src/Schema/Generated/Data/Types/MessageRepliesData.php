<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageReplies of MessageReplies.
 */
final class MessageRepliesData extends TlMessageRepliesAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'comments' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $comments,
    public int $replies,
    public int $repliesPts,
    public ?array $recentRepliers,
    public ?int $channelId,
    public ?int $maxId,
    public ?int $readMaxId,
    ) {
    }
}
