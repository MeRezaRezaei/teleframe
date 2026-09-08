<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for destroy_session_none of DestroySessionRes.
 */
final class DestroySessionNoneData extends TlDestroySessionResAbstractData
{
    public function __construct(
    public int $sessionId,
    ) {
    }
}
