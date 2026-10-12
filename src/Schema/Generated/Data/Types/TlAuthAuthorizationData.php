<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.authorization of auth.Authorization.
 *
 * bytes params carried as base64 strings: future_auth_token
 */
final class TlAuthAuthorizationData extends TlAuthAuthorizationAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'setupPasswordRequired' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $setupPasswordRequired,
    public ?int $otherwiseReloginDays,
    public ?int $tmpSessions,
    public ?string $futureAuthToken,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlUserAbstractData $user,
    ) {
    }
}
