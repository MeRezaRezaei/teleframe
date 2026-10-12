<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.botResults of messages.BotResults.
 */
final class TlMessagesBotResultsData extends TlMessagesBotResultsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'gallery' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $gallery,
    public int $queryId,
    public ?string $nextOffset,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInlineBotSwitchPMAbstractData $switchPm,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInlineBotWebViewAbstractData $switchWebview,
    public array $results,
    public int $cacheTime,
    public array $users,
    ) {
    }
}
