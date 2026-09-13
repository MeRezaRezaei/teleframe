<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for phoneCallAccepted of PhoneCall.
 *
 * bytes params carried as base64 strings: g_b
 */
final class PhoneCallAcceptedData extends TlPhoneCallAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'video' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $video,
    public int $id,
    public int $accessHash,
    public int $date,
    public int $adminId,
    public int $participantId,
    public string $gB,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhoneCallProtocolAbstractData $protocol,
    ) {
    }
}
