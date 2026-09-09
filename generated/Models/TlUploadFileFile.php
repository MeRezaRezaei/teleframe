<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStorageFileType;

/** Constructor model for upload.file of upload.File (crc32 096a18d5). */
final class TlUploadFileFile extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_upload_file_file';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'mtime' => 'int',
        'bytes' => 'string',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(TlStorageFileType::class, 'tl_type');
    }
}
