<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for postAddress of PostAddress.
 */
final class PostAddressData extends TlPostAddressAbstractData
{
    public function __construct(
    public string $streetLine1,
    public string $streetLine2,
    public string $city,
    public string $state,
    public string $countryIso2,
    public string $postCode,
    ) {
    }
}
