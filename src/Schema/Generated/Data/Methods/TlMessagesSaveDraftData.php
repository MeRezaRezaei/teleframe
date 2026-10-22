<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.saveDraft (crc32 ad0fa15c), returns Bool. */
final class TlMessagesSaveDraftData extends Data
{
    public const METHOD = 'messages.saveDraft';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $noWebpage,
    public ?bool $invertMedia,
    public mixed $replyTo,
    public mixed $peer,
    public string $message,
    public ?array $entities,
    public mixed $media,
    public ?int $effect,
    public mixed $suggestedPost,
    public mixed $richMessage,
    ) {
    }
}
