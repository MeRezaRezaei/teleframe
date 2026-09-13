<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for smsjobs.status of smsjobs.Status.
 */
final class TlSmsjobsStatusData extends TlSmsjobsStatusAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'allowInternational' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $allowInternational,
    public int $recentSent,
    public int $recentSince,
    public int $recentRemains,
    public int $totalSent,
    public int $totalSince,
    public ?string $lastGiftSlug,
    public string $termsUrl,
    ) {
    }
}
