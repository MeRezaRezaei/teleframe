<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webDomainException of WebDomainException.
 */
final class WebDomainExceptionData extends TlWebDomainExceptionAbstractData
{
    public function __construct(
    public int $flags,
    public string $domain,
    public string $url,
    public string $title,
    public ?int $favicon,
    ) {
    }
}
