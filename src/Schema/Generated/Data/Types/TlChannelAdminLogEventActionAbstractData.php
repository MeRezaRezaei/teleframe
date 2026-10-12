<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/**
 * Union DTO base for TL type ChannelAdminLogEventAction.
 *
 * @method static static hydrate(array $payload)
 */
abstract class TlChannelAdminLogEventActionAbstractData extends Data
{
    /** @var array<string, class-string<self>> */
    protected const DISPATCH = [
        'channelAdminLogEventActionChangeAbout' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeAboutData::class,
        'channelAdminLogEventActionChangeAvailableReactions' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeAvailableReactionsData::class,
        'channelAdminLogEventActionChangeEmojiStatus' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeEmojiStatusData::class,
        'channelAdminLogEventActionChangeEmojiStickerSet' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeEmojiStickerSetData::class,
        'channelAdminLogEventActionChangeHistoryTTL' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeHistoryTTLData::class,
        'channelAdminLogEventActionChangeLinkedChat' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeLinkedChatData::class,
        'channelAdminLogEventActionChangeLocation' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeLocationData::class,
        'channelAdminLogEventActionChangePeerColor' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangePeerColorData::class,
        'channelAdminLogEventActionChangePhoto' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangePhotoData::class,
        'channelAdminLogEventActionChangeProfilePeerColor' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeProfilePeerColorData::class,
        'channelAdminLogEventActionChangeStickerSet' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeStickerSetData::class,
        'channelAdminLogEventActionChangeTitle' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeTitleData::class,
        'channelAdminLogEventActionChangeUsername' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeUsernameData::class,
        'channelAdminLogEventActionChangeUsernames' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeUsernamesData::class,
        'channelAdminLogEventActionChangeWallpaper' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionChangeWallpaperData::class,
        'channelAdminLogEventActionCreateTopic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionCreateTopicData::class,
        'channelAdminLogEventActionDefaultBannedRights' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionDefaultBannedRightsData::class,
        'channelAdminLogEventActionDeleteMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionDeleteMessageData::class,
        'channelAdminLogEventActionDeleteTopic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionDeleteTopicData::class,
        'channelAdminLogEventActionDiscardGroupCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionDiscardGroupCallData::class,
        'channelAdminLogEventActionEditMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionEditMessageData::class,
        'channelAdminLogEventActionEditTopic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionEditTopicData::class,
        'channelAdminLogEventActionExportedInviteDelete' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionExportedInviteDeleteData::class,
        'channelAdminLogEventActionExportedInviteEdit' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionExportedInviteEditData::class,
        'channelAdminLogEventActionExportedInviteRevoke' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionExportedInviteRevokeData::class,
        'channelAdminLogEventActionParticipantEditRank' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantEditRankData::class,
        'channelAdminLogEventActionParticipantInvite' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantInviteData::class,
        'channelAdminLogEventActionParticipantJoin' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantJoinData::class,
        'channelAdminLogEventActionParticipantJoinByInvite' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantJoinByInviteData::class,
        'channelAdminLogEventActionParticipantJoinByRequest' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantJoinByRequestData::class,
        'channelAdminLogEventActionParticipantLeave' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantLeaveData::class,
        'channelAdminLogEventActionParticipantMute' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantMuteData::class,
        'channelAdminLogEventActionParticipantSubExtend' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantSubExtendData::class,
        'channelAdminLogEventActionParticipantToggleAdmin' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantToggleAdminData::class,
        'channelAdminLogEventActionParticipantToggleBan' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantToggleBanData::class,
        'channelAdminLogEventActionParticipantUnmute' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantUnmuteData::class,
        'channelAdminLogEventActionParticipantVolume' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionParticipantVolumeData::class,
        'channelAdminLogEventActionPinTopic' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionPinTopicData::class,
        'channelAdminLogEventActionSendMessage' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionSendMessageData::class,
        'channelAdminLogEventActionStartGroupCall' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionStartGroupCallData::class,
        'channelAdminLogEventActionStopPoll' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionStopPollData::class,
        'channelAdminLogEventActionToggleAntiSpam' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleAntiSpamData::class,
        'channelAdminLogEventActionToggleAutotranslation' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleAutotranslationData::class,
        'channelAdminLogEventActionToggleForum' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleForumData::class,
        'channelAdminLogEventActionToggleGroupCallSetting' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleGroupCallSettingData::class,
        'channelAdminLogEventActionToggleInvites' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleInvitesData::class,
        'channelAdminLogEventActionToggleNoForwards' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleNoForwardsData::class,
        'channelAdminLogEventActionTogglePreHistoryHidden' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionTogglePreHistoryHiddenData::class,
        'channelAdminLogEventActionToggleSignatureProfiles' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleSignatureProfilesData::class,
        'channelAdminLogEventActionToggleSignatures' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleSignaturesData::class,
        'channelAdminLogEventActionToggleSlowMode' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionToggleSlowModeData::class,
        'channelAdminLogEventActionUpdatePinned' => \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\ChannelAdminLogEventActionUpdatePinnedData::class,
    ];

    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [];

    /** Dispatch on the constructor name carried under the '_' key of a decoded wire payload. */
    public static function hydrate(array $payload): static
    {
        $class = static::DISPATCH[$payload['_']]
            ?? throw new \InvalidArgumentException('Unknown constructor ' . $payload['_'] . ' for ChannelAdminLogEventAction');
        foreach ((new \ReflectionMethod($class, '__construct'))->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $payload)) {
                continue;
            }
            $bits = $class::TL_FLAG_BITS[$name] ?? null;
            if ($bits !== null) {
                $word = (int) ($payload[$bits[0]] ?? 0);
                $payload[$name] = (bool) ($word >> $bits[1] & 1);
                continue;
            }
            $wireKey = self::tlWireKey($name);
            $payload[$name] = array_key_exists($wireKey, $payload) ? $payload[$wireKey] : null;
        }
        /** @var static */
        return $class::from($payload);
    }

    /** camelCase constructor param name to snake_case wire key (regex-free). */
    private static function tlWireKey(string $name): string
    {
        $out = '';
        foreach (str_split($name) as $i => $ch) {
            $out .= $i > 0 && $ch >= 'A' && $ch <= 'Z' ? '_' . strtolower($ch) : $ch;
        }
        return $out;
    }
}
