<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for sponsoredMessage of SponsoredMessage.
 *
 * bytes params carried as base64 strings: random_id
 */
final class SponsoredMessageData extends TlSponsoredMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'recommended' => ['flags', 5],
        'canReport' => ['flags', 12],
    ];

    public function __construct(
    public int $flags,
    public ?bool $recommended,
    public ?bool $canReport,
    public string $randomId,
    public string $url,
    public string $title,
    public string $message,
    public ?array $entities,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $photo,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerColorAbstractData $color,
    public string $buttonText,
    public ?string $sponsorInfo,
    public ?string $additionalInfo,
    public ?int $minDisplayDuration,
    public ?int $maxDisplayDuration,
    ) {
    }
}
