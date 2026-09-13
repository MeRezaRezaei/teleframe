<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for resPQ of ResPQ.
 */
final class ResPQData extends TlResPQAbstractData
{
    public function __construct(
    public string $nonce,
    public string $serverNonce,
    public string $pq,
    public array $serverPublicKeyFingerprints,
    ) {
    }
}
