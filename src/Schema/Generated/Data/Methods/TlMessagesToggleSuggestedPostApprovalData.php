<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.toggleSuggestedPostApproval (crc32 8107455c), returns Updates. */
final class TlMessagesToggleSuggestedPostApprovalData extends Data
{
    public const METHOD = 'messages.toggleSuggestedPostApproval';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $reject,
    public mixed $peer,
    public int $msgId,
    public ?int $scheduleDate,
    public ?string $rejectComment,
    ) {
    }
}
