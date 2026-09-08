<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.resetPasswordFailedWait of account.ResetPasswordResult.
 */
final class TlAccountResetPasswordFailedWaitData extends TlAccountResetPasswordResultAbstractData
{
    public function __construct(
    public int $retryDate,
    ) {
    }
}
