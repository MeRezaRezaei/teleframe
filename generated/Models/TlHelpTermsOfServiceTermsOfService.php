<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpTermsOfServiceTermsOfServiceEntities;

/** Constructor model for help.termsOfService of help.TermsOfService (crc32 780a0310). */
final class TlHelpTermsOfServiceTermsOfService extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_terms_of_service_terms_of_service';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'popup' => 'bool',
        'tl_id' => 'string',
        'text' => 'string',
        'min_age_confirm' => 'int',
    ];

    public function entities(): HasMany
    {
        return $this->tlChild(TlHelpTermsOfServiceTermsOfServiceEntities::class);
    }
}
