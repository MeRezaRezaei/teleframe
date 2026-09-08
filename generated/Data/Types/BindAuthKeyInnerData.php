<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for bind_auth_key_inner of BindAuthKeyInner.
 */
final class BindAuthKeyInnerData extends TlBindAuthKeyInnerAbstractData
{
    public function __construct(
    public int $nonce,
    public int $tempAuthKeyId,
    public int $permAuthKeyId,
    public int $tempSessionId,
    public int $expiresAt,
    ) {
    }
}
