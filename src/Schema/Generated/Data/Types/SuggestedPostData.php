<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for suggestedPost of SuggestedPost.
 */
final class SuggestedPostData extends TlSuggestedPostAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'accepted' => ['flags', 1],
        'rejected' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $accepted,
    public ?bool $rejected,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $price,
    public ?int $scheduleDate,
    ) {
    }
}
