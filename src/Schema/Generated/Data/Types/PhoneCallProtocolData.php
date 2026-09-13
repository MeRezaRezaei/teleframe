<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneCallProtocol of PhoneCallProtocol.
 */
final class PhoneCallProtocolData extends TlPhoneCallProtocolAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'udpP2p' => ['flags', 0],
        'udpReflector' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $udpP2p,
    public ?bool $udpReflector,
    public int $minLayer,
    public int $maxLayer,
    public array $libraryVersions,
    ) {
    }
}
