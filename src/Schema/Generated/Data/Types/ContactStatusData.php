<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for contactStatus of ContactStatus.
 */
final class ContactStatusData extends TlContactStatusAbstractData
{
    public function __construct(
    public int $userId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserStatusAbstractData $status,
    ) {
    }
}
