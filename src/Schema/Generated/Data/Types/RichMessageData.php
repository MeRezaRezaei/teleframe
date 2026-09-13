<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for richMessage of RichMessage.
 */
final class RichMessageData extends TlRichMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'rtl' => ['flags', 0],
        'part' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $rtl,
    public ?bool $part,
    public array $blocks,
    public array $photos,
    public array $documents,
    ) {
    }
}
