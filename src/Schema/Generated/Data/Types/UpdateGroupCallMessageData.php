<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateGroupCallMessage of Update.
 */
final class UpdateGroupCallMessageData extends TlUpdateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGroupCallAbstractData $call,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGroupCallMessageAbstractData $message,
    ) {
    }
}
