<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for inputBusinessChatLink of InputBusinessChatLink.
 */
final class InputBusinessChatLinkData extends TlInputBusinessChatLinkAbstractData
{
    public function __construct(
    public int $flags,
    public string $message,
    public ?array $entities,
    public ?string $title,
    ) {
    }
}
