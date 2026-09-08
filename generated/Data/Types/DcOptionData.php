<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for dcOption of DcOption.
 *
 * bytes params carried as base64 strings: secret
 */
final class DcOptionData extends TlDcOptionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'ipv6' => ['flags', 0],
        'mediaOnly' => ['flags', 1],
        'tcpoOnly' => ['flags', 2],
        'cdn' => ['flags', 3],
        'static' => ['flags', 4],
        'thisPortOnly' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $ipv6,
    public ?bool $mediaOnly,
    public ?bool $tcpoOnly,
    public ?bool $cdn,
    public ?bool $static,
    public ?bool $thisPortOnly,
    public int $id,
    public string $ipAddress,
    public int $port,
    public ?string $secret,
    ) {
    }
}
