<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for upload.cdnFileReuploadNeeded of upload.CdnFile (crc32 eea8e46e). */
final class TlUploadCdnFileCdnFileReuploadNeeded extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_upload_cdn_file_cdn_file_reupload_needed';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'request_token' => 'string',
    ];
}
