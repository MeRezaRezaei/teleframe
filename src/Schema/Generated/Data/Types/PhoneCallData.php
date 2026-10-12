<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneCall of PhoneCall.
 *
 * bytes params carried as base64 strings: g_a_or_b
 */
final class PhoneCallData extends TlPhoneCallAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'p2pAllowed' => ['flags', 5],
        'video' => ['flags', 6],
        'conferenceSupported' => ['flags', 8],
    ];

    public function __construct(
    public int $flags,
    public ?bool $p2pAllowed,
    public ?bool $video,
    public ?bool $conferenceSupported,
    public int $id,
    public int $accessHash,
    public int $date,
    public int $adminId,
    public int $participantId,
    public string $gAOrB,
    public int $keyFingerprint,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhoneCallProtocolAbstractData $protocol,
    public array $connections,
    public int $startDate,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDataJSONAbstractData $customParameters,
    ) {
    }
}
