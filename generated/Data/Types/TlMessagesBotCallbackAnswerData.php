<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.botCallbackAnswer of messages.BotCallbackAnswer.
 */
final class TlMessagesBotCallbackAnswerData extends TlMessagesBotCallbackAnswerAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'alert' => ['flags', 1],
        'hasUrl' => ['flags', 3],
        'nativeUi' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $alert,
    public ?bool $hasUrl,
    public ?bool $nativeUi,
    public ?string $message,
    public ?string $url,
    public int $cacheTime,
    ) {
    }
}
