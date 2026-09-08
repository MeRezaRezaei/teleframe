<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.sentCodeTypeFirebaseSms of auth.SentCodeType.
 *
 * bytes params carried as base64 strings: nonce, play_integrity_nonce
 */
final class TlAuthSentCodeTypeFirebaseSmsData extends TlAuthSentCodeTypeAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $nonce,
    public ?int $playIntegrityProjectId,
    public ?string $playIntegrityNonce,
    public ?string $receipt,
    public ?int $pushTimeout,
    public int $length,
    ) {
    }
}
