<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateShortMessage of Updates.
 */
final class UpdateShortMessageData extends TlUpdatesAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'out' => ['flags', 1],
        'mentioned' => ['flags', 4],
        'mediaUnread' => ['flags', 5],
        'silent' => ['flags', 13],
    ];

    public function __construct(
    public int $flags,
    public ?bool $out,
    public ?bool $mentioned,
    public ?bool $mediaUnread,
    public ?bool $silent,
    public int $id,
    public int $userId,
    public string $message,
    public int $pts,
    public int $ptsCount,
    public int $date,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageFwdHeaderAbstractData $fwdFrom,
    public ?int $viaBotId,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlMessageReplyHeaderAbstractData $replyTo,
    public ?array $entities,
    public ?int $ttlPeriod,
    ) {
    }
}
