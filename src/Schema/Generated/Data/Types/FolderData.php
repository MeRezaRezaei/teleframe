<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for folder of Folder.
 */
final class FolderData extends TlFolderAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'autofillNewBroadcasts' => ['flags', 0],
        'autofillPublicGroups' => ['flags', 1],
        'autofillNewCorrespondents' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $autofillNewBroadcasts,
    public ?bool $autofillPublicGroups,
    public ?bool $autofillNewCorrespondents,
    public int $id,
    public string $title,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatPhotoAbstractData $photo,
    ) {
    }
}
