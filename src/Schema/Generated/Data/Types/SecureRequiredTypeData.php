<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for secureRequiredType of SecureRequiredType.
 */
final class SecureRequiredTypeData extends TlSecureRequiredTypeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nativeNames' => ['flags', 0],
        'selfieRequired' => ['flags', 1],
        'translationRequired' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nativeNames,
    public ?bool $selfieRequired,
    public ?bool $translationRequired,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecureValueTypeAbstractData $type,
    ) {
    }
}
