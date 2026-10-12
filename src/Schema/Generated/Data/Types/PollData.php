<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for poll of Poll.
 */
final class PollData extends TlPollAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'closed' => ['flags', 0],
        'publicVoters' => ['flags', 1],
        'multipleChoice' => ['flags', 2],
        'quiz' => ['flags', 3],
        'openAnswers' => ['flags', 6],
        'revotingDisabled' => ['flags', 7],
        'shuffleAnswers' => ['flags', 8],
        'hideResultsUntilClose' => ['flags', 9],
        'creator' => ['flags', 10],
        'subscribersOnly' => ['flags', 11],
    ];

    public function __construct(
    public int $id,
    public int $flags,
    public ?bool $closed,
    public ?bool $publicVoters,
    public ?bool $multipleChoice,
    public ?bool $quiz,
    public ?bool $openAnswers,
    public ?bool $revotingDisabled,
    public ?bool $shuffleAnswers,
    public ?bool $hideResultsUntilClose,
    public ?bool $creator,
    public ?bool $subscribersOnly,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $question,
    public array $answers,
    public ?int $closePeriod,
    public ?int $closeDate,
    public ?array $countriesIso2,
    public int $hash,
    ) {
    }
}
