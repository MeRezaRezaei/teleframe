<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chat of Chat.
 */
final class ChatData extends TlChatAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'creator' => ['flags', 0],
        'left' => ['flags', 2],
        'deactivated' => ['flags', 5],
        'callActive' => ['flags', 23],
        'callNotEmpty' => ['flags', 24],
        'noforwards' => ['flags', 25],
    ];

    public function __construct(
    public int $flags,
    public ?bool $creator,
    public ?bool $left,
    public ?bool $deactivated,
    public ?bool $callActive,
    public ?bool $callNotEmpty,
    public ?bool $noforwards,
    public int $id,
    public string $title,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatPhotoAbstractData $photo,
    public int $participantsCount,
    public int $date,
    public int $version,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputChannelAbstractData $migratedTo,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAdminRightsAbstractData $adminRights,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatBannedRightsAbstractData $defaultBannedRights,
    ) {
    }
}
