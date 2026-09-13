<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.support of help.Support.
 */
final class TlHelpSupportData extends TlHelpSupportAbstractData
{
    public function __construct(
    public string $phoneNumber,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserAbstractData $user,
    ) {
    }
}
