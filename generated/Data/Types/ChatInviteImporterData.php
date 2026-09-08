<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for chatInviteImporter of ChatInviteImporter.
 */
final class ChatInviteImporterData extends TlChatInviteImporterAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'requested' => ['flags', 0],
        'viaChatlist' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $requested,
    public ?bool $viaChatlist,
    public int $userId,
    public int $date,
    public ?string $about,
    public ?int $approvedBy,
    ) {
    }
}
