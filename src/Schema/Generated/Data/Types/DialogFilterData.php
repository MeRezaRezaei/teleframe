<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for dialogFilter of DialogFilter.
 */
final class DialogFilterData extends TlDialogFilterAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'contacts' => ['flags', 0],
        'nonContacts' => ['flags', 1],
        'groups' => ['flags', 2],
        'broadcasts' => ['flags', 3],
        'bots' => ['flags', 4],
        'excludeMuted' => ['flags', 11],
        'excludeRead' => ['flags', 12],
        'excludeArchived' => ['flags', 13],
        'titleNoanimate' => ['flags', 28],
    ];

    public function __construct(
    public int $flags,
    public ?bool $contacts,
    public ?bool $nonContacts,
    public ?bool $groups,
    public ?bool $broadcasts,
    public ?bool $bots,
    public ?bool $excludeMuted,
    public ?bool $excludeRead,
    public ?bool $excludeArchived,
    public ?bool $titleNoanimate,
    public int $id,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $title,
    public ?string $emoticon,
    public ?int $color,
    public array $pinnedPeers,
    public array $includePeers,
    public array $excludePeers,
    ) {
    }
}
