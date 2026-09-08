<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.messagesSlice of messages.Messages.
 */
final class TlMessagesMessagesSliceData extends TlMessagesMessagesAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inexact' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inexact,
    public int $count,
    public ?int $nextRate,
    public ?int $offsetIdOffset,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSearchPostsFloodAbstractData $searchFlood,
    public array $messages,
    public array $topics,
    public array $chats,
    public array $users,
    ) {
    }
}
