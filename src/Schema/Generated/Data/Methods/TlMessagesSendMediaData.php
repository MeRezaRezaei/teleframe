<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendMedia (crc32 0330e77f), returns Updates. */
final class TlMessagesSendMediaData extends Data
{
    public const METHOD = 'messages.sendMedia';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $silent,
    public ?bool $background,
    public ?bool $clearDraft,
    public ?bool $noforwards,
    public ?bool $updateStickersetsOrder,
    public ?bool $invertMedia,
    public ?bool $allowPaidFloodskip,
    public mixed $peer,
    public mixed $replyTo,
    public mixed $media,
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
    ) {
    }
}
