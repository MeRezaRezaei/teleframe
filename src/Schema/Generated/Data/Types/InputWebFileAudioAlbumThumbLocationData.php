<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputWebFileAudioAlbumThumbLocation of InputWebFileLocation.
 */
final class InputWebFileAudioAlbumThumbLocationData extends TlInputWebFileLocationAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'small' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $small,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputDocumentAbstractData $document,
    public ?string $title,
    public ?string $performer,
    ) {
    }
}
