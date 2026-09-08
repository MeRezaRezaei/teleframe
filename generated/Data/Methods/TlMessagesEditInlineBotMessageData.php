<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.editInlineBotMessage (crc32 a423bb51), returns Bool. */
final class TlMessagesEditInlineBotMessageData extends Data
{
    public const METHOD = 'messages.editInlineBotMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $noWebpage,
    public ?bool $invertMedia,
    public mixed $id,
    public ?string $message,
    public mixed $media,
    public mixed $replyMarkup,
    public ?array $entities,
    public mixed $richMessage,
    ) {
    }
}
