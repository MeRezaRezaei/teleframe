<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webPageAttributeStory of WebPageAttribute.
 */
final class WebPageAttributeStoryData extends TlWebPageAttributeAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStoryItemAbstractData $story,
    ) {
    }
}
