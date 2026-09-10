<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for fileHash of FileHash (crc32 f39b035c). */
final class TlFileHashFileHash extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_file_hash_file_hash';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'tl_offset' => 'int',
        'tl_limit' => 'int',
        'hash' => 'string',
    ];
}
