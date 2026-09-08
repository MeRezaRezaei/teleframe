<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneConnectionWebrtc of PhoneConnection.
 */
final class PhoneConnectionWebrtcData extends TlPhoneConnectionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'turn' => ['flags', 0],
        'stun' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $turn,
    public ?bool $stun,
    public int $id,
    public string $ip,
    public string $ipv6,
    public int $port,
    public string $username,
    public string $password,
    ) {
    }
}
