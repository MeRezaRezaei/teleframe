<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.forwardMessages (crc32 13704a7c), returns Updates. */
final class TlMessagesForwardMessagesData extends Data
{
    public const METHOD = 'messages.forwardMessages';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $silent,
    public ?bool $background,
    public ?bool $withMyScore,
    public ?bool $dropAuthor,
    public ?bool $dropMediaCaptions,
    public ?bool $noforwards,
    public ?bool $allowPaidFloodskip,
    public mixed $fromPeer,
    public array $id,
    public array $randomId,
    public mixed $toPeer,
    public ?int $topMsgId,
    public mixed $replyTo,
    public ?int $scheduleDate,
    public ?int $scheduleRepeatPeriod,
    public mixed $sendAs,
    public mixed $quickReplyShortcut,
    public ?int $effect,
    public ?int $videoTimestamp,
    public ?int $allowPaidStars,
    public mixed $suggestedPost,
    ) {
    }
}
