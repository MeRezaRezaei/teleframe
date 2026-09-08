<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botInlineMessageMediaWebPage of BotInlineMessage.
 */
final class BotInlineMessageMediaWebPageData extends TlBotInlineMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'invertMedia' => ['flags', 3],
        'forceLargeMedia' => ['flags', 4],
        'forceSmallMedia' => ['flags', 5],
        'manual' => ['flags', 7],
        'safe' => ['flags', 8],
    ];

    public function __construct(
    public int $flags,
    public ?bool $invertMedia,
    public ?bool $forceLargeMedia,
    public ?bool $forceSmallMedia,
    public ?bool $manual,
    public ?bool $safe,
    public string $message,
    public ?array $entities,
    public string $url,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    ) {
    }
}
