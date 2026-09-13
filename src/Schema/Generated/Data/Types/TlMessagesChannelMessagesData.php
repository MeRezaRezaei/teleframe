<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.channelMessages of messages.Messages.
 */
final class TlMessagesChannelMessagesData extends TlMessagesMessagesAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inexact' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inexact,
    public int $pts,
    public int $count,
    public ?int $offsetIdOffset,
    public array $messages,
    public array $topics,
    public array $chats,
    public array $users,
    ) {
    }
}
