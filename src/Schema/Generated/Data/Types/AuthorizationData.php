<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for authorization of Authorization.
 */
final class AuthorizationData extends TlAuthorizationAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'current' => ['flags', 0],
        'officialApp' => ['flags', 1],
        'passwordPending' => ['flags', 2],
        'encryptedRequestsDisabled' => ['flags', 3],
        'callRequestsDisabled' => ['flags', 4],
        'unconfirmed' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $current,
    public ?bool $officialApp,
    public ?bool $passwordPending,
    public ?bool $encryptedRequestsDisabled,
    public ?bool $callRequestsDisabled,
    public ?bool $unconfirmed,
    public int $hash,
    public string $deviceModel,
    public string $platform,
    public string $systemVersion,
    public int $apiId,
    public string $appName,
    public string $appVersion,
    public int $dateCreated,
    public int $dateActive,
    public string $ip,
    public string $country,
    public string $region,
    ) {
    }
}
