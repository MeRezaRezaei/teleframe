<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for codeSettings of CodeSettings.
 */
final class CodeSettingsData extends TlCodeSettingsAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'allowFlashcall' => ['flags', 0],
        'currentNumber' => ['flags', 1],
        'allowAppHash' => ['flags', 4],
        'allowMissedCall' => ['flags', 5],
        'allowFirebase' => ['flags', 7],
        'unknownNumber' => ['flags', 9],
    ];

    public function __construct(
    public int $flags,
    public ?bool $allowFlashcall,
    public ?bool $currentNumber,
    public ?bool $allowAppHash,
    public ?bool $allowMissedCall,
    public ?bool $allowFirebase,
    public ?bool $unknownNumber,
    public ?array $logoutTokens,
    public ?string $token,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $appSandbox,
    ) {
    }
}
