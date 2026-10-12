<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for pollAnswerVoters of PollAnswerVoters.
 *
 * bytes params carried as base64 strings: option
 */
final class PollAnswerVotersData extends TlPollAnswerVotersAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'chosen' => ['flags', 0],
        'correct' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $chosen,
    public ?bool $correct,
    public string $option,
    public ?int $voters,
    public ?array $recentVoters,
    ) {
    }
}
