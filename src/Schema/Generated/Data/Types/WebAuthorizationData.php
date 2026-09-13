<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for webAuthorization of WebAuthorization.
 */
final class WebAuthorizationData extends TlWebAuthorizationAbstractData
{
    public function __construct(
    public int $hash,
    public int $botId,
    public string $domain,
    public string $browser,
    public string $platform,
    public int $dateCreated,
    public int $dateActive,
    public string $ip,
    public string $region,
    ) {
    }
}
