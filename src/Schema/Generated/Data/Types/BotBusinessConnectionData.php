<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botBusinessConnection of BotBusinessConnection.
 */
final class BotBusinessConnectionData extends TlBotBusinessConnectionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'disabled' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $disabled,
    public string $connectionId,
    public int $userId,
    public int $dcId,
    public int $date,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBusinessBotRightsAbstractData $rights,
    ) {
    }
}
