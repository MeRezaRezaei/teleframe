<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for help.deepLinkInfoEmpty of help.DeepLinkInfo (crc32 66afa166). */
final class TlHelpDeepLinkInfoDeepLinkInfoEmpty extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_deep_link_info_deep_link_info_empty';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
