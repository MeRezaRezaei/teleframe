<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for exportedChatlistInvite of ExportedChatlistInvite.
 */
final class ExportedChatlistInviteData extends TlExportedChatlistInviteAbstractData
{
    public function __construct(
    public int $flags,
    public string $title,
    public string $url,
    public array $peers,
    ) {
    }
}
