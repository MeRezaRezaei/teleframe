<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateShortSentMessage of Updates.
 */
final class UpdateShortSentMessageData extends TlUpdatesAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'out' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $out,
    public int $id,
    public int $pts,
    public int $ptsCount,
    public int $date,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    public ?array $entities,
    public ?int $ttlPeriod,
    ) {
    }
}
