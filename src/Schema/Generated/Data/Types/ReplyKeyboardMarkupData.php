<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for replyKeyboardMarkup of ReplyMarkup.
 */
final class ReplyKeyboardMarkupData extends TlReplyMarkupAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'resize' => ['flags', 0],
        'singleUse' => ['flags', 1],
        'selective' => ['flags', 2],
        'persistent' => ['flags', 4],
    ];

    public function __construct(
    public int $flags,
    public ?bool $resize,
    public ?bool $singleUse,
    public ?bool $selective,
    public ?bool $persistent,
    public array $rows,
    public ?string $placeholder,
    ) {
    }
}
