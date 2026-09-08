<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.highScores of messages.HighScores.
 */
final class TlMessagesHighScoresData extends TlMessagesHighScoresAbstractData
{
    public function __construct(
    public array $scores,
    public array $users,
    ) {
    }
}
