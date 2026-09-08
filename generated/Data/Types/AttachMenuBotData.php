<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for attachMenuBot of AttachMenuBot.
 */
final class AttachMenuBotData extends TlAttachMenuBotAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inactive' => ['flags', 0],
        'hasSettings' => ['flags', 1],
        'requestWriteAccess' => ['flags', 2],
        'showInAttachMenu' => ['flags', 3],
        'showInSideMenu' => ['flags', 4],
        'sideMenuDisclaimerNeeded' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inactive,
    public ?bool $hasSettings,
    public ?bool $requestWriteAccess,
    public ?bool $showInAttachMenu,
    public ?bool $showInSideMenu,
    public ?bool $sideMenuDisclaimerNeeded,
    public int $botId,
    public string $shortName,
    public ?array $peerTypes,
    public array $icons,
    ) {
    }
}
