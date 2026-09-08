<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for businessRecipients of BusinessRecipients.
 */
final class BusinessRecipientsData extends TlBusinessRecipientsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'existingChats' => ['flags', 0],
        'newChats' => ['flags', 1],
        'contacts' => ['flags', 2],
        'nonContacts' => ['flags', 3],
        'excludeSelected' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $existingChats,
    public ?bool $newChats,
    public ?bool $contacts,
    public ?bool $nonContacts,
    public ?bool $excludeSelected,
    public ?array $users,
    ) {
    }
}
