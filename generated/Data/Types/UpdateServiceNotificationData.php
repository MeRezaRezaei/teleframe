<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateServiceNotification of Update.
 */
final class UpdateServiceNotificationData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'popup' => ['flags', 0],
        'invertMedia' => ['flags', 2],
    ];

    public function __construct(
    public int $flags,
    public ?bool $popup,
    public ?bool $invertMedia,
    public ?int $inboxDate,
    public string $type,
    public string $message,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageMediaAbstractData $media,
    public array $entities,
    ) {
    }
}
