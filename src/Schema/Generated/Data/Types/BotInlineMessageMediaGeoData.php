<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botInlineMessageMediaGeo of BotInlineMessage.
 */
final class BotInlineMessageMediaGeoData extends TlBotInlineMessageAbstractData
{
    public function __construct(
    public int $flags,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGeoPointAbstractData $geo,
    public ?int $heading,
    public ?int $period,
    public ?int $proximityNotificationRadius,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlReplyMarkupAbstractData $replyMarkup,
    ) {
    }
}
