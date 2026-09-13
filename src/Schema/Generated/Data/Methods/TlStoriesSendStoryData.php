<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.sendStory (crc32 8f9e6898), returns Updates. */
final class TlStoriesSendStoryData extends Data
{
    public const METHOD = 'stories.sendStory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $pinned,
    public ?bool $noforwards,
    public ?bool $fwdModified,
    public mixed $peer,
    public mixed $media,
    public ?array $mediaAreas,
    public ?string $caption,
    public ?array $entities,
    public array $privacyRules,
    public int $randomId,
    public ?int $period,
    public mixed $fwdFromId,
    public ?int $fwdFromStory,
    public ?array $albums,
    public mixed $music,
    ) {
    }
}
