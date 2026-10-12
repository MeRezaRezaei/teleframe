<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for page of Page.
 */
final class PageData extends TlPageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'part' => ['flags', 0],
        'rtl' => ['flags', 1],
        'v2' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $part,
    public ?bool $rtl,
    public ?bool $v2,
    public string $url,
    public array $blocks,
    public array $photos,
    public array $documents,
    public ?int $views,
    ) {
    }
}
