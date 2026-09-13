<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for businessIntro of BusinessIntro.
 */
final class BusinessIntroData extends TlBusinessIntroAbstractData
{
    public function __construct(
    public int $flags,
    public string $title,
    public string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $sticker,
    ) {
    }
}
