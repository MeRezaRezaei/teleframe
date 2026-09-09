<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotsPreviewInfoPreviewInfoLang_codes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotsPreviewInfoPreviewInfoMedia;

/** Constructor model for bots.previewInfo of bots.PreviewInfo (crc32 0ca71d64). */
final class TlBotsPreviewInfoPreviewInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bots_preview_info_preview_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function media(): HasMany
    {
        return $this->tlChild(TlBotsPreviewInfoPreviewInfoMedia::class);
    }
    public function langCodes(): HasMany
    {
        return $this->tlChild(TlBotsPreviewInfoPreviewInfoLang_codes::class);
    }
}
