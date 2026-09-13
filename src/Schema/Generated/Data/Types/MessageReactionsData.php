<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageReactions of MessageReactions.
 */
final class MessageReactionsData extends TlMessageReactionsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'min' => ['flags', 0],
        'canSeeList' => ['flags', 2],
        'reactionsAsTags' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $min,
    public ?bool $canSeeList,
    public ?bool $reactionsAsTags,
    public array $results,
    public ?array $recentReactions,
    public ?array $topReactors,
    ) {
    }
}
