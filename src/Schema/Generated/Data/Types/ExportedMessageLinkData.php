<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for exportedMessageLink of ExportedMessageLink.
 */
final class ExportedMessageLinkData extends TlExportedMessageLinkAbstractData
{
    public function __construct(
    public string $link,
    public string $html,
    ) {
    }
}
