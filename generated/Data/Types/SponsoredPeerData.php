<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for sponsoredPeer of SponsoredPeer.
 *
 * bytes params carried as base64 strings: random_id
 */
final class SponsoredPeerData extends TlSponsoredPeerAbstractData
{
    public function __construct(
    public int $flags,
    public string $randomId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public ?string $sponsorInfo,
    public ?string $additionalInfo,
    ) {
    }
}
