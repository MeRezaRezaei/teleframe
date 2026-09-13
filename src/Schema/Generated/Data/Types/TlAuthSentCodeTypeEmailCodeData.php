<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for auth.sentCodeTypeEmailCode of auth.SentCodeType.
 */
final class TlAuthSentCodeTypeEmailCodeData extends TlAuthSentCodeTypeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'appleSigninAllowed' => ['flags', 0],
        'googleSigninAllowed' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $appleSigninAllowed,
    public ?bool $googleSigninAllowed,
    public string $emailPattern,
    public int $length,
    public ?int $resetAvailablePeriod,
    public ?int $resetPendingDate,
    ) {
    }
}
