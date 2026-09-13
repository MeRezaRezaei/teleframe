<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateJoinChatWebViewDecision of Update.
 */
final class UpdateJoinChatWebViewDecisionData extends TlUpdateAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public int $queryId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlJoinChatBotResultAbstractData $result,
    ) {
    }
}
