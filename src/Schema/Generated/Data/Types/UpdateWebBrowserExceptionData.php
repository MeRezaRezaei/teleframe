<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateWebBrowserException of Update.
 */
final class UpdateWebBrowserExceptionData extends TlUpdateAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'delete' => ['flags', 1],
    ];

    public function __construct(
    public int $flags,
    public ?bool $delete,
    public ?\MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlBoolAbstractData $openExternalBrowser,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlWebDomainExceptionAbstractData $exception,
    ) {
    }
}
