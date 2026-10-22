<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messageMediaDocument of MessageMedia.
 */
final class MessageMediaDocumentData extends TlMessageMediaAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'nopremium' => ['flags', 3],
        'spoiler' => ['flags', 4],
        'video' => ['flags', 6],
        'round' => ['flags', 7],
        'voice' => ['flags', 8],
    ];

    public function __construct(
    public int $flags,
    public ?bool $nopremium,
    public ?bool $spoiler,
    public ?bool $video,
    public ?bool $round,
    public ?bool $voice,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $document,
    public ?array $altDocuments,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $videoCover,
    public ?int $videoTimestamp,
    public ?int $ttlSeconds,
    ) {
    }
}
