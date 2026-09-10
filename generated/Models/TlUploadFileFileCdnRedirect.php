<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUploadFileFileCdnRedirectFile_hashes;

/** Constructor model for upload.fileCdnRedirect of upload.File (crc32 f18cda44). */
final class TlUploadFileFileCdnRedirect extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_upload_file_file_cdn_redirect';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'dc_id' => 'int',
        'file_token' => 'string',
        'encryption_key' => 'string',
        'encryption_iv' => 'string',
    ];

    public function fileHashes(): HasMany
    {
        return $this->tlChild(TlUploadFileFileCdnRedirectFile_hashes::class);
    }
}
