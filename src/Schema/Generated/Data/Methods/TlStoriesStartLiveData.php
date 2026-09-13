<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stories.startLive (crc32 d069ccde), returns Updates. */
final class TlStoriesStartLiveData extends Data
{
    public const METHOD = 'stories.startLive';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $pinned,
    public ?bool $noforwards,
    public ?bool $rtmpStream,
    public mixed $peer,
    public ?string $caption,
    public ?array $entities,
    public array $privacyRules,
    public int $randomId,
    public mixed $messagesEnabled,
    public ?int $sendPaidMessagesStars,
    ) {
    }
}
