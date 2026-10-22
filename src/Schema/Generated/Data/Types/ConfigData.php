<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for config of Config.
 */
final class ConfigData extends TlConfigAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'defaultP2pContacts' => ['flags', 3],
        'preloadFeaturedStickers' => ['flags', 4],
        'revokePmInbox' => ['flags', 6],
        'blockedMode' => ['flags', 8],
        'forceTryIpv6' => ['flags', 14],
    ];

    public function __construct(
    public int $flags,
    public ?bool $defaultP2pContacts,
    public ?bool $preloadFeaturedStickers,
    public ?bool $revokePmInbox,
    public ?bool $blockedMode,
    public ?bool $forceTryIpv6,
    public int $date,
    public int $expires,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $testMode,
    public int $thisDc,
    public array $dcOptions,
    public string $dcTxtDomainName,
    public int $chatSizeMax,
    public int $megagroupSizeMax,
    public int $forwardedCountMax,
    public int $onlineUpdatePeriodMs,
    public int $offlineBlurTimeoutMs,
    public int $offlineIdleTimeoutMs,
    public int $onlineCloudTimeoutMs,
    public int $notifyCloudDelayMs,
    public int $notifyDefaultDelayMs,
    public int $pushChatPeriodMs,
    public int $pushChatLimit,
    public int $editTimeLimit,
    public int $revokeTimeLimit,
    public int $revokePmTimeLimit,
    public int $ratingEDecay,
    public int $stickersRecentLimit,
    public int $channelsReadMediaPeriod,
    public ?int $tmpSessions,
    public int $callReceiveTimeoutMs,
    public int $callRingTimeoutMs,
    public int $callConnectTimeoutMs,
    public int $callPacketTimeoutMs,
    public string $meUrlPrefix,
    public ?string $autoupdateUrlPrefix,
    public ?string $gifSearchUsername,
    public ?string $venueSearchUsername,
    public ?string $imgSearchUsername,
    public ?string $staticMapsProvider,
    public int $captionLengthMax,
    public int $messageLengthMax,
    public int $webfileDcId,
    public ?string $suggestedLangCode,
    public ?int $langPackVersion,
    public ?int $baseLangPackVersion,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReactionAbstractData $reactionsDefault,
    public ?string $autologinToken,
    ) {
    }
}
