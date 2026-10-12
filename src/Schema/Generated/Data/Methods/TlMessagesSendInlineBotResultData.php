<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendInlineBotResult (crc32 c0cf7646), returns Updates. */
final class TlMessagesSendInlineBotResultData extends Data
{
    public const METHOD = 'messages.sendInlineBotResult';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $silent,
    public ?bool $background,
    public ?bool $clearDraft,
    public ?bool $hideVia,
    public mixed $peer,
    public mixed $replyTo,
    public int $randomId,
    public int $queryId,
    public string $id,
    public ?int $scheduleDate,
    public mixed $sendAs,
    public mixed $quickReplyShortcut,
    public ?int $allowPaidStars,
    ) {
    }
}
