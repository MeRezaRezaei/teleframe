<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.appUpdate of help.AppUpdate.
 */
final class TlHelpAppUpdateData extends TlHelpAppUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'canNotSkip' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $canNotSkip,
    public int $id,
    public string $version,
    public string $text,
    public array $entities,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $document,
    public ?string $url,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $sticker,
    ) {
    }
}
