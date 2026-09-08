<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param retry_contacts (table tl_contacts_imported_contacts_imported_contac_7e82948d3852). */
final class TlContactsImportedContactsImportedContactsRetry_contacts extends TlAnchorModel
{
    protected $table = 'tl_contacts_imported_contacts_imported_contac_7e82948d3852';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
