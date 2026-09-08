<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelForbidden of Chat.
 */
final class ChannelForbiddenData extends TlChatAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'broadcast' => ['flags', 5],
        'megagroup' => ['flags', 8],
        'monoforum' => ['flags', 10],
    ];

    public function __construct(
    public int $flags,
    public ?bool $broadcast,
    public ?bool $megagroup,
    public ?bool $monoforum,
    public int $id,
    public int $accessHash,
    public string $title,
    public ?int $untilDate,
    ) {
    }
}
