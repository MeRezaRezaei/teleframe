<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.sendMultiMedia (crc32 1bf89d74), returns Updates. */
final class TlMessagesSendMultiMediaData extends Data
{
    public const METHOD = 'messages.sendMultiMedia';

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
    public array $multiMedia,
    public ?int $scheduleDate,
    public mixed $sendAs,
    public mixed $quickReplyShortcut,
    public ?int $effect,
    public ?int $allowPaidStars,
    ) {
    }
}
