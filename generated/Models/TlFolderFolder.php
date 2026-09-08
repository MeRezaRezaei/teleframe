<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for folder of Folder (crc32 ff544e65). */
final class TlFolderFolder extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_folder_folder';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'autofill_new_broadcasts' => 'bool',
        'autofill_public_groups' => 'bool',
        'autofill_new_correspondents' => 'bool',
        'tl_id' => 'int',
        'title' => 'string',
        'photo' => 'string',
    ];
}
