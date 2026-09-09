<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpTermsOfService;

/** Constructor model for help.termsOfServiceUpdate of help.TermsOfServiceUpdate (crc32 28ecf961). */
final class TlHelpTermsOfServiceUpdateTermsOfServiceUpdate extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_terms_of_service_update_terms_of_service_update';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'expires' => 'int',
    ];

    public function termsOfService(): BelongsTo
    {
        return $this->belongsTo(TlHelpTermsOfService::class, 'terms_of_service');
    }
}
