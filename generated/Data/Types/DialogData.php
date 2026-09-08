<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for dialog of Dialog.
 */
final class DialogData extends TlDialogAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'pinned' => ['flags', 2],
        'unreadMark' => ['flags', 3],
        'viewForumAsMessages' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $pinned,
    public ?bool $unreadMark,
    public ?bool $viewForumAsMessages,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $topMessage,
    public int $readInboxMaxId,
    public int $readOutboxMaxId,
    public int $unreadCount,
    public int $unreadMentionsCount,
    public int $unreadReactionsCount,
    public int $unreadPollVotesCount,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerNotifySettingsAbstractData $notifySettings,
    public ?int $pts,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDraftMessageAbstractData $draft,
    public ?int $folderId,
    public ?int $ttlPeriod,
    ) {
    }
}
