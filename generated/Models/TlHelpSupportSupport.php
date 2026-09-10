<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/** Constructor model for help.support of help.Support (crc32 17c6b5f6). */
final class TlHelpSupportSupport extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_support_support';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'phone_number' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(TlUser::class, 'tl_user');
    }
}
