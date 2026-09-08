<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateReadMessagesContents of Update.
 */
final class UpdateReadMessagesContentsData extends TlUpdateAbstractData
{
    public function __construct(
    public int $flags,
    public array $messages,
    public int $pts,
    public int $ptsCount,
    public ?int $date,
    ) {
    }
}
