<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneConnection of PhoneConnection.
 *
 * bytes params carried as base64 strings: peer_tag
 */
final class PhoneConnectionData extends TlPhoneConnectionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'tcp' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $tcp,
    public int $id,
    public string $ip,
    public string $ipv6,
    public int $port,
    public string $peerTag,
    ) {
    }
}
