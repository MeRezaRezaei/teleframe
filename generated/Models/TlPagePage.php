<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPagePageBlocks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPagePagePhotos;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPagePageDocuments;

/** Constructor model for page of Page (crc32 98657f0d). */
final class TlPagePage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_page_page';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'part' => 'bool',
        'rtl' => 'bool',
        'v2' => 'bool',
        'url' => 'string',
        'views' => 'int',
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlPagePageBlocks::class);
    }
    public function photos(): HasMany
    {
        return $this->tlChild(TlPagePagePhotos::class);
    }
    public function documents(): HasMany
    {
        return $this->tlChild(TlPagePageDocuments::class);
    }
}
