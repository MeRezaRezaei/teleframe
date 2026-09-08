<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpDeepLinkInfoDeepLinkInfoEntities;

/** Constructor model for help.deepLinkInfo of help.DeepLinkInfo (crc32 6a4ee832). */
final class TlHelpDeepLinkInfoDeepLinkInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_deep_link_info_deep_link_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'update_app' => 'bool',
        'message' => 'string',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlHelpDeepLinkInfoDeepLinkInfoEntities::class);
    }
}
