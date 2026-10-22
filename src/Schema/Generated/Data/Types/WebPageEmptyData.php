<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webPageEmpty of WebPage.
 */
final class WebPageEmptyData extends TlWebPageAbstractData
{
    public function __construct(
    public int $flags,
    public int $id,
    public ?string $url,
    ) {
    }
}
