<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for theme of Theme.
 */
final class ThemeData extends TlThemeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'creator' => ['flags', 0],
        'default' => ['flags', 1],
        'forChat' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $creator,
    public ?bool $default,
    public ?bool $forChat,
    public int $id,
    public int $accessHash,
    public string $slug,
    public string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $document,
    public ?array $settings,
    public ?string $emoticon,
    public ?int $installsCount,
    ) {
    }
}
