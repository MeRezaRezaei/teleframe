<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editMessage (crc32 b106e66c), returns Updates. */
final class TlMessagesEditMessageData extends Data
{
    public const METHOD = 'messages.editMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $noWebpage,
    public ?bool $invertMedia,
    public mixed $peer,
    public int $id,
    public ?string $message,
    public mixed $media,
    public mixed $replyMarkup,
    public ?array $entities,
    public ?int $scheduleDate,
    public ?int $scheduleRepeatPeriod,
    public ?int $quickReplyShortcutId,
    public mixed $richMessage,
    ) {
    }
}
