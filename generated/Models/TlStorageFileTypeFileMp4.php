<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storage.fileMp4 of storage.FileType (crc32 b3cea0e4). */
final class TlStorageFileTypeFileMp4 extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_storage_file_type_file_mp4';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
