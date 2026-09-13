<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for quickReply of QuickReply.
 */
final class QuickReplyData extends TlQuickReplyAbstractData
{
    public function __construct(
    public int $shortcutId,
    public string $shortcut,
    public int $topMessage,
    public int $count,
    ) {
    }
}
