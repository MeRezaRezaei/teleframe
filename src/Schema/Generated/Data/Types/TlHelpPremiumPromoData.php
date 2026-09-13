<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for help.premiumPromo of help.PremiumPromo.
 */
final class TlHelpPremiumPromoData extends TlHelpPremiumPromoAbstractData
{
    public function __construct(
    public string $statusText,
    public array $statusEntities,
    public array $videoSections,
    public array $videos,
    public array $periodOptions,
    public array $users,
    ) {
    }
}
