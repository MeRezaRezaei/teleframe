<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatBannedRights of ChatBannedRights.
 */
final class ChatBannedRightsData extends TlChatBannedRightsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'viewMessages' => ['flags', 0],
        'sendMessages' => ['flags', 1],
        'sendMedia' => ['flags', 2],
        'sendStickers' => ['flags', 3],
        'sendGifs' => ['flags', 4],
        'sendGames' => ['flags', 5],
        'sendInline' => ['flags', 6],
        'embedLinks' => ['flags', 7],
        'sendPolls' => ['flags', 8],
        'changeInfo' => ['flags', 10],
        'inviteUsers' => ['flags', 15],
        'pinMessages' => ['flags', 17],
        'manageTopics' => ['flags', 18],
        'sendPhotos' => ['flags', 19],
        'sendVideos' => ['flags', 20],
        'sendRoundvideos' => ['flags', 21],
        'sendAudios' => ['flags', 22],
        'sendVoices' => ['flags', 23],
        'sendDocs' => ['flags', 24],
        'sendPlain' => ['flags', 25],
        'editRank' => ['flags', 26],
        'sendReactions' => ['flags', 27],
    ];

    public function __construct(
    public int $flags,
    public ?bool $viewMessages,
    public ?bool $sendMessages,
    public ?bool $sendMedia,
    public ?bool $sendStickers,
    public ?bool $sendGifs,
    public ?bool $sendGames,
    public ?bool $sendInline,
    public ?bool $embedLinks,
    public ?bool $sendPolls,
    public ?bool $changeInfo,
    public ?bool $inviteUsers,
    public ?bool $pinMessages,
    public ?bool $manageTopics,
    public ?bool $sendPhotos,
    public ?bool $sendVideos,
    public ?bool $sendRoundvideos,
    public ?bool $sendAudios,
    public ?bool $sendVoices,
    public ?bool $sendDocs,
    public ?bool $sendPlain,
    public ?bool $editRank,
    public ?bool $sendReactions,
    public int $untilDate,
    ) {
    }
}
