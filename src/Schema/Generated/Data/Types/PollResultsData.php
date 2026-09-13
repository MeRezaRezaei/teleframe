<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pollResults of PollResults.
 */
final class PollResultsData extends TlPollResultsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'min' => ['flags', 0],
        'hasUnreadVotes' => ['flags', 6],
        'canViewStats' => ['flags', 7],
    ];

    public function __construct(
    public int $flags,
    public ?bool $min,
    public ?bool $hasUnreadVotes,
    public ?bool $canViewStats,
    public ?array $results,
    public ?int $totalVoters,
    public ?array $recentVoters,
    public ?string $solution,
    public ?array $solutionEntities,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $solutionMedia,
    ) {
    }
}
