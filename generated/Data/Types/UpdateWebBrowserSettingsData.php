<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateWebBrowserSettings of Update.
 */
final class UpdateWebBrowserSettingsData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'openExternalBrowser' => ['flags', 0],
        'displayCloseButton' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $openExternalBrowser,
    public ?bool $displayCloseButton,
    ) {
    }
}
