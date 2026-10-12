<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for message of Message.
 */
final class MessageData extends TlMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'out' => ['flags', 1],
        'mentioned' => ['flags', 4],
        'mediaUnread' => ['flags', 5],
        'silent' => ['flags', 13],
        'post' => ['flags', 14],
        'fromScheduled' => ['flags', 18],
        'legacy' => ['flags', 19],
        'editHide' => ['flags', 21],
        'pinned' => ['flags', 24],
        'noforwards' => ['flags', 26],
        'invertMedia' => ['flags', 27],
        'offline' => ['flags2', 1],
        'videoProcessingPending' => ['flags2', 4],
        'paidSuggestedPostStars' => ['flags2', 8],
        'paidSuggestedPostTon' => ['flags2', 9],
    ];

    public function __construct(
    public int $flags,
    public ?bool $out,
    public ?bool $mentioned,
    public ?bool $mediaUnread,
    public ?bool $silent,
    public ?bool $post,
    public ?bool $fromScheduled,
    public ?bool $legacy,
    public ?bool $editHide,
    public ?bool $pinned,
    public ?bool $noforwards,
    public ?bool $invertMedia,
    public int $flags2,
    public ?bool $offline,
    public ?bool $videoProcessingPending,
    public ?bool $paidSuggestedPostStars,
    public ?bool $paidSuggestedPostTon,
    public int $id,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $fromId,
    public ?int $fromBoostsApplied,
    public ?string $fromRank,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peerId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $savedPeerId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageFwdHeaderAbstractData $fwdFrom,
    public ?int $viaBotId,
    public ?int $viaBusinessBotId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $guestchatViaFrom,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageReplyHeaderAbstractData $replyTo,
    public int $date,
    public string $message,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    public ?array $entities,
    public ?int $views,
    public ?int $forwards,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageRepliesAbstractData $replies,
    public ?int $editDate,
    public ?string $postAuthor,
    public ?int $groupedId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageReactionsAbstractData $reactions,
    public ?array $restrictionReason,
    public ?int $ttlPeriod,
    public ?int $quickReplyShortcutId,
    public ?int $effect,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlFactCheckAbstractData $factcheck,
    public ?int $reportDeliveryUntilDate,
    public ?int $paidMessageStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlSuggestedPostAbstractData $suggestedPost,
    public ?int $scheduleRepeatPeriod,
    public ?string $summaryFromLanguage,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlRichMessageAbstractData $richMessage,
    ) {
    }
}
