<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webPage of WebPage.
 */
final class WebPageData extends TlWebPageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasLargeMedia' => ['flags', 13],
        'videoCoverPhoto' => ['flags', 14],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasLargeMedia,
    public ?bool $videoCoverPhoto,
    public int $id,
    public string $url,
    public string $displayUrl,
    public int $hash,
    public ?string $type,
    public ?string $siteName,
    public ?string $title,
    public ?string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $photo,
    public ?string $embedUrl,
    public ?string $embedType,
    public ?int $embedWidth,
    public ?int $embedHeight,
    public ?int $duration,
    public ?string $author,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $document,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPageAbstractData $cachedPage,
    public ?array $attributes,
    ) {
    }
}
