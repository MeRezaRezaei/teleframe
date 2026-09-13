<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for savedDialog of SavedDialog.
 */
final class SavedDialogData extends TlSavedDialogAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'pinned' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $pinned,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $topMessage,
    ) {
    }
}
