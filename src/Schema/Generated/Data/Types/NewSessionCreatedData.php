<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for new_session_created of NewSession.
 */
final class NewSessionCreatedData extends TlNewSessionAbstractData
{
    public function __construct(
    public int $firstMsgId,
    public int $uniqueId,
    public int $serverSalt,
    ) {
    }
}
