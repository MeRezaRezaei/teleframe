<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateDeleteMessages of Update.
 */
final class UpdateDeleteMessagesData extends TlUpdateAbstractData
{
    public function __construct(
    public array $messages,
    public int $pts,
    public int $ptsCount,
    ) {
    }
}
