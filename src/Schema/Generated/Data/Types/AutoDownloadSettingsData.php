<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for autoDownloadSettings of AutoDownloadSettings.
 */
final class AutoDownloadSettingsData extends TlAutoDownloadSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'disabled' => ['flags', 0],
        'videoPreloadLarge' => ['flags', 1],
        'audioPreloadNext' => ['flags', 2],
        'phonecallsLessData' => ['flags', 3],
        'storiesPreload' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $disabled,
    public ?bool $videoPreloadLarge,
    public ?bool $audioPreloadNext,
    public ?bool $phonecallsLessData,
    public ?bool $storiesPreload,
    public int $photoSizeMax,
    public int $videoSizeMax,
    public int $fileSizeMax,
    public int $videoUploadMaxbitrate,
    public int $smallQueueActiveOperationsMax,
    public int $largeQueueActiveOperationsMax,
    ) {
    }
}
