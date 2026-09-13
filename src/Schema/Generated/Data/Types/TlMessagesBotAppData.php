<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.botApp of messages.BotApp.
 */
final class TlMessagesBotAppData extends TlMessagesBotAppAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inactive' => ['flags', 0],
        'requestWriteAccess' => ['flags', 1],
        'hasSettings' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inactive,
    public ?bool $requestWriteAccess,
    public ?bool $hasSettings,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotAppAbstractData $app,
    ) {
    }
}
