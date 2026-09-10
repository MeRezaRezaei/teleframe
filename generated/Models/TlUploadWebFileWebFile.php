<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStorageFileType;

/** Constructor model for upload.webFile of upload.WebFile (crc32 21e753bc). */
final class TlUploadWebFileWebFile extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_upload_web_file_web_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_size' => 'int',
        'mime_type' => 'string',
        'mtime' => 'int',
        'bytes' => 'string',
    ];

    public function fileType(): BelongsTo
    {
        return $this->belongsTo(TlStorageFileType::class, 'file_type');
    }
}
