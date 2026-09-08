<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionSuggestedPostApproval of MessageAction.
 */
final class MessageActionSuggestedPostApprovalData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'rejected' => ['flags', 0],
        'balanceTooLow' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $rejected,
    public ?bool $balanceTooLow,
    public ?string $rejectComment,
    public ?int $scheduleDate,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlStarsAmountAbstractData $price,
    ) {
    }
}
