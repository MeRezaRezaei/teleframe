<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageActionBotAllowed of MessageAction.
 */
final class MessageActionBotAllowedData extends TlMessageActionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'attachMenu' => ['flags', 1],
        'fromRequest' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $attachMenu,
    public ?bool $fromRequest,
    public ?string $domain,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotAppAbstractData $app,
    ) {
    }
}
