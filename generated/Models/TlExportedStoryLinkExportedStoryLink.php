<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for exportedStoryLink of ExportedStoryLink (crc32 3fc9053b). */
final class TlExportedStoryLinkExportedStoryLink extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_exported_story_link_exported_story_link';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'link' => 'string',
    ];
}
