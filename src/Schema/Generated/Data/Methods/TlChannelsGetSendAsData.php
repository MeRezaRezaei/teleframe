<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method channels.getSendAs (crc32 e785a43f), returns channels.SendAsPeers. */
final class TlChannelsGetSendAsData extends Data
{
    public const METHOD = 'channels.getSendAs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $forPaidReactions,
    public ?bool $forLiveStories,
    public mixed $peer,
    ) {
    }
}
