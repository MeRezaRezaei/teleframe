<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateStickerSetsOrder of Update.
 */
final class UpdateStickerSetsOrderData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'masks' => ['flags', 0],
        'emojis' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $masks,
    public ?bool $emojis,
    public array $order,
    ) {
    }
}
