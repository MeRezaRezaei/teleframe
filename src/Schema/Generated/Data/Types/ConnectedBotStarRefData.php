<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for connectedBotStarRef of ConnectedBotStarRef.
 */
final class ConnectedBotStarRefData extends TlConnectedBotStarRefAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'revoked' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $revoked,
    public string $url,
    public int $date,
    public int $botId,
    public int $commissionPermille,
    public ?int $durationMonths,
    public int $participants,
    public int $revenue,
    ) {
    }
}
