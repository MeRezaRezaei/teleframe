<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pageRelatedArticle of PageRelatedArticle.
 */
final class PageRelatedArticleData extends TlPageRelatedArticleAbstractData
{
    public function __construct(
    public int $flags,
    public string $url,
    public int $webpageId,
    public ?string $title,
    public ?string $description,
    public ?int $photoId,
    public ?string $author,
    public ?int $publishedDate,
    ) {
    }
}
