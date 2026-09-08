<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for upload.cdnFile of upload.CdnFile (crc32 a99fca4f). */
final class TlUploadCdnFileCdnFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_upload_cdn_file_cdn_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'bytes' => 'string',
    ];
}
