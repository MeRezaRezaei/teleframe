<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.contentSettings of account.ContentSettings.
 */
final class TlAccountContentSettingsData extends TlAccountContentSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'sensitiveEnabled' => ['flags', 0],
        'sensitiveCanChange' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $sensitiveEnabled,
    public ?bool $sensitiveCanChange,
    ) {
    }
}
