<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBotInlineMessageMediaWebPage of InputBotInlineMessage.
 */
final class InputBotInlineMessageMediaWebPageData extends TlInputBotInlineMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'invertMedia' => ['flags', 3],
        'forceLargeMedia' => ['flags', 4],
        'forceSmallMedia' => ['flags', 5],
        'optional' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $invertMedia,
    public ?bool $forceLargeMedia,
    public ?bool $forceSmallMedia,
    public ?bool $optional,
    public string $message,
    public ?array $entities,
    public string $url,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    ) {
    }
}
