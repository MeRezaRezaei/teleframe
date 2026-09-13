<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.appendTodoList (crc32 21a61057), returns Updates. */
final class TlMessagesAppendTodoListData extends Data
{
    public const METHOD = 'messages.appendTodoList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $msgId,
    public array $list,
    ) {
    }
}
