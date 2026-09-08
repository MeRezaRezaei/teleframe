<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updates.channelDifferenceTooLong of updates.ChannelDifference.
 */
final class TlUpdatesChannelDifferenceTooLongData extends TlUpdatesChannelDifferenceAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'final' => ['flags', 0],
    ];

    public function __construct(
    public int $flags,
    public ?bool $final,
    public ?int $timeout,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDialogAbstractData $dialog,
    public array $messages,
    public array $chats,
    public array $users,
    ) {
    }
}
