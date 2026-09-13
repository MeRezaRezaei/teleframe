<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for starGiftAttributeOriginalDetails of StarGiftAttribute.
 */
final class StarGiftAttributeOriginalDetailsData extends TlStarGiftAttributeAbstractData
{
    public function __construct(
    public int $flags,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $senderId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $recipientId,
    public int $date,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlTextWithEntitiesAbstractData $message,
    ) {
    }
}
