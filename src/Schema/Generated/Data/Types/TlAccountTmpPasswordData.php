<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.tmpPassword of account.TmpPassword.
 *
 * bytes params carried as base64 strings: tmp_password
 */
final class TlAccountTmpPasswordData extends TlAccountTmpPasswordAbstractData
{
    public function __construct(
    public string $tmpPassword,
    public int $validUntil,
    ) {
    }
}
