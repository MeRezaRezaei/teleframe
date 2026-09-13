<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for account.password of account.Password.
 *
 * bytes params carried as base64 strings: srp_B, secure_random
 */
final class TlAccountPasswordData extends TlAccountPasswordAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasRecovery' => ['flags', 0],
        'hasSecureValues' => ['flags', 1],
        'hasPassword' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasRecovery,
    public ?bool $hasSecureValues,
    public ?bool $hasPassword,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPasswordKdfAlgoAbstractData $currentAlgo,
    public ?string $srpB,
    public ?int $srpId,
    public ?string $hint,
    public ?string $emailUnconfirmedPattern,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPasswordKdfAlgoAbstractData $newAlgo,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSecurePasswordKdfAlgoAbstractData $newSecureAlgo,
    public string $secureRandom,
    public ?int $pendingResetDate,
    public ?string $loginEmailPattern,
    ) {
    }
}
