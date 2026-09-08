<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storage.fileGif of storage.FileType (crc32 cae1aadf). */
final class TlStorageFileTypeFileGif extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_storage_file_type_file_gif';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
