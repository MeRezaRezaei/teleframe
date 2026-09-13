<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for smsJob of SmsJob.
 */
final class SmsJobData extends TlSmsJobAbstractData
{
    public function __construct(
    public string $jobId,
    public string $phoneNumber,
    public string $text,
    ) {
    }
}
