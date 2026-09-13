<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.composeMessageWithAI (crc32 daecc589), returns messages.ComposedMessageWithAI. */
final class TlMessagesComposeMessageWithAIData extends Data
{
    public const METHOD = 'messages.composeMessageWithAI';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $proofread,
    public ?bool $emojify,
    public mixed $text,
    public ?string $translateToLang,
    public mixed $tone,
    ) {
    }
}
