<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for decryptedMessage of DecryptedMessage.
 */
final class DecryptedMessageData extends TlDecryptedMessageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'noWebpage' => ['flags', 1],
        'silent' => ['flags', 5],
    ];

    public function __construct(
    public int $flags,
    public ?bool $noWebpage,
    public ?bool $silent,
    public int $randomId,
    public int $ttl,
    public string $message,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDecryptedMessageMediaAbstractData $media,
    public ?array $entities,
    public ?string $viaBotName,
    public ?int $replyToRandomId,
    public ?int $groupedId,
    ) {
    }
}
