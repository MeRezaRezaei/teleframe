<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.emojiGameDiceInfo of messages.EmojiGameInfo.
 */
final class TlMessagesEmojiGameDiceInfoData extends TlMessagesEmojiGameInfoAbstractData
{
    public function __construct(
    public int $flags,
    public string $gameHash,
    public int $prevStake,
    public int $currentStreak,
    public array $params,
    public ?int $playsLeft,
    ) {
    }
}
