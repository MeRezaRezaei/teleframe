<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendMessage (crc32 fef48f62), returns Updates. */
final class TlMessagesSendMessageData extends Data
{
    public const METHOD = 'messages.sendMessage';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $noWebpage,
    public ?bool $silent,
    public ?bool $background,
    public ?bool $clearDraft,
    public ?bool $noforwards,
    public ?bool $updateStickersetsOrder,
    public ?bool $invertMedia,
    public ?bool $allowPaidFloodskip,
    public mixed $peer,
    public mixed $replyTo,
    public string $message,
    public int $randomId,
    public mixed $replyMarkup,
    public ?array $entities,
    public ?int $scheduleDate,
    public ?int $scheduleRepeatPeriod,
    public mixed $sendAs,
    public mixed $quickReplyShortcut,
    public ?int $effect,
    public ?int $allowPaidStars,
    public mixed $suggestedPost,
    public mixed $richMessage,
    ) {
    }
}
