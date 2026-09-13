<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for bots.popularAppBots of bots.PopularAppBots.
 */
final class TlBotsPopularAppBotsData extends TlBotsPopularAppBotsAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $nextOffset,
    public array $users,
    ) {
    }
}
