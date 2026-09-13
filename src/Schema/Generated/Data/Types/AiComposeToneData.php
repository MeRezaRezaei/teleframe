<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for aiComposeTone of AiComposeTone.
 */
final class AiComposeToneData extends TlAiComposeToneAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'creator' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $creator,
    public int $id,
    public int $accessHash,
    public string $slug,
    public string $title,
    public ?int $emojiId,
    public ?string $prompt,
    public ?int $installsCount,
    public ?int $authorId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlAiComposeToneExampleAbstractData $exampleEnglish,
    ) {
    }
}
