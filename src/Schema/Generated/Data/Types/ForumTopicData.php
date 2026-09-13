<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for forumTopic of ForumTopic.
 */
final class ForumTopicData extends TlForumTopicAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'my' => ['flags', 1],
        'closed' => ['flags', 2],
        'pinned' => ['flags', 3],
        'short' => ['flags', 5],
        'hidden' => ['flags', 6],
        'titleMissing' => ['flags', 7],
    ];

    public function __construct(
    public int $flags,
    public ?bool $my,
    public ?bool $closed,
    public ?bool $pinned,
    public ?bool $short,
    public ?bool $hidden,
    public ?bool $titleMissing,
    public int $id,
    public int $date,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public string $title,
    public int $iconColor,
    public ?int $iconEmojiId,
    public int $topMessage,
    public int $readInboxMaxId,
    public int $readOutboxMaxId,
    public int $unreadCount,
    public int $unreadMentionsCount,
    public int $unreadReactionsCount,
    public int $unreadPollVotesCount,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerNotifySettingsAbstractData $notifySettings,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDraftMessageAbstractData $draft,
    ) {
    }
}
