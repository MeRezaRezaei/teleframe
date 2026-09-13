<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineMessageMediaAuto of InputBotInlineMessage.
 */
final class InputBotInlineMessageMediaAutoData extends TlInputBotInlineMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'invertMedia' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $invertMedia,
    public string $message,
    public ?array $entities,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    ) {
    }
}
