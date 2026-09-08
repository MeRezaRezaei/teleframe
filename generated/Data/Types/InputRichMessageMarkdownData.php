<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputRichMessageMarkdown of InputRichMessage.
 */
final class InputRichMessageMarkdownData extends TlInputRichMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'rtl' => ['flags', 0],
        'noautolink' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $rtl,
    public ?bool $noautolink,
    public string $markdown,
    public ?array $files,
    ) {
    }
}
