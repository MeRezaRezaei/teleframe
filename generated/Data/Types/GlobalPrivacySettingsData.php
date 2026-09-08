<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for globalPrivacySettings of GlobalPrivacySettings.
 */
final class GlobalPrivacySettingsData extends TlGlobalPrivacySettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'archiveAndMuteNewNoncontactPeers' => ['flags', 0],
        'keepArchivedUnmuted' => ['flags', 1],
        'keepArchivedFolders' => ['flags', 2],
        'hideReadMarks' => ['flags', 3],
        'newNoncontactPeersRequirePremium' => ['flags', 4],
        'displayGiftsButton' => ['flags', 7],
    ];

    public function __construct(
    public int $flags,
    public ?bool $archiveAndMuteNewNoncontactPeers,
    public ?bool $keepArchivedUnmuted,
    public ?bool $keepArchivedFolders,
    public ?bool $hideReadMarks,
    public ?bool $newNoncontactPeersRequirePremium,
    public ?bool $displayGiftsButton,
    public ?int $noncontactPeersPaidStars,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDisallowedGiftsSettingsAbstractData $disallowedGifts,
    ) {
    }
}
