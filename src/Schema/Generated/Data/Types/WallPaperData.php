<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for wallPaper of WallPaper.
 */
final class WallPaperData extends TlWallPaperAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'creator' => ['flags', 0],
        'default' => ['flags', 1],
        'pattern' => ['flags', 3],
        'dark' => ['flags', 4],
    ];

    public function __construct(
    public int $id,
    public int $flags,
    public ?bool $creator,
    public ?bool $default,
    public ?bool $pattern,
    public ?bool $dark,
    public int $accessHash,
    public string $slug,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $document,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWallPaperSettingsAbstractData $settings,
    ) {
    }
}
