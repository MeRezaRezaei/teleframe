<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for botInfo of BotInfo.
 */
final class BotInfoData extends TlBotInfoAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'hasPreviewMedias' => ['flags', 6],
    ];

    public function __construct(
    public int $flags,
    public ?bool $hasPreviewMedias,
    public ?int $userId,
    public ?string $description,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPhotoAbstractData $descriptionPhoto,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlDocumentAbstractData $descriptionDocument,
    public ?array $commands,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotMenuButtonAbstractData $menuButton,
    public ?string $privacyPolicyUrl,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotAppSettingsAbstractData $appSettings,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBotVerifierSettingsAbstractData $verifierSettings,
    ) {
    }
}
