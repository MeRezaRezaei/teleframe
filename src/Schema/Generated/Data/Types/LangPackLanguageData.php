<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for langPackLanguage of LangPackLanguage.
 */
final class LangPackLanguageData extends TlLangPackLanguageAbstractData
{
    /** @var array<string, array{0:string,1:int}> camelCase param name => [flag word, bit] for flags.N?true params */
    public const TL_FLAG_BITS = [
        'official' => ['flags', 0],
        'rtl' => ['flags', 2],
        'beta' => ['flags', 3],
    ];

    public function __construct(
    public int $flags,
    public ?bool $official,
    public ?bool $rtl,
    public ?bool $beta,
    public string $name,
    public string $nativeName,
    public string $langCode,
    public ?string $baseLangCode,
    public string $pluralCode,
    public int $stringsCount,
    public int $translatedCount,
    public string $translationsUrl,
    ) {
    }
}
