<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channels.adminLogResults of channels.AdminLogResults.
 */
final class TlChannelsAdminLogResultsData extends TlChannelsAdminLogResultsAbstractData
{
    public function __construct(
    public array $events,
    public array $chats,
    public array $users,
    ) {
    }
}
