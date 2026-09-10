<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUploadFileFile;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUploadWebFileWebFile;

/** Anchor model for TL type storage.FileType (spec §4.1). */
final class TlStorageFileType extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_storage_file_type_file_gif';

    protected $guarded = [];

    public function fileType(): HasMany
    {
        return $this->hasMany(TlUploadWebFileWebFile::class, 'file_type');
    }
    public function type(): HasMany
    {
        return $this->hasMany(TlUploadFileFile::class, 'tl_type');
    }
}
