<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for documentAttributeAudio of DocumentAttribute.
 *
 * bytes params carried as base64 strings: waveform
 */
final class DocumentAttributeAudioData extends TlDocumentAttributeAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'voice' => ['flags', 10],
    ];

    public function __construct(
    public int $flags,
    public ?bool $voice,
    public int $duration,
    public ?string $title,
    public ?string $performer,
    public ?string $waveform,
    ) {
    }
}
