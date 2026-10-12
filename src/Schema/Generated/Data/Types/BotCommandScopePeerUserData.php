<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botCommandScopePeerUser of BotCommandScope.
 */
final class BotCommandScopePeerUserData extends TlBotCommandScopeAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputPeerAbstractData $peer,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlInputUserAbstractData $userId,
    ) {
    }
}
