<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for availableReaction of AvailableReaction.
 */
final class AvailableReactionData extends TlAvailableReactionAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'inactive' => ['flags', 0],
        'premium' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $inactive,
    public ?bool $premium,
    public string $reaction,
    public string $title,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $staticIcon,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $appearAnimation,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $selectAnimation,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $activateAnimation,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $effectAnimation,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $aroundAnimation,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $centerIcon,
    ) {
    }
}
