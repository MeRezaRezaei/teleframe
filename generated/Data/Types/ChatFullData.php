<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatFull of ChatFull.
 */
final class ChatFullData extends TlChatFullAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'canSetUsername' => ['flags', 7],
        'hasScheduled' => ['flags', 8],
        'translationsDisabled' => ['flags', 19],
    ];

    public function __construct(
    public int $flags,
    public ?bool $canSetUsername,
    public ?bool $hasScheduled,
    public ?bool $translationsDisabled,
    public int $id,
    public string $about,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatParticipantsAbstractData $participants,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $chatPhoto,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerNotifySettingsAbstractData $notifySettings,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlExportedChatInviteAbstractData $exportedInvite,
    public ?array $botInfo,
    public ?int $pinnedMsgId,
    public ?int $folderId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputGroupCallAbstractData $call,
    public ?int $ttlPeriod,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $groupcallDefaultJoinAs,
    public ?string $themeEmoticon,
    public ?int $requestsPending,
    public ?array $recentRequesters,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatReactionsAbstractData $availableReactions,
    public ?int $reactionsLimit,
    ) {
    }
}
