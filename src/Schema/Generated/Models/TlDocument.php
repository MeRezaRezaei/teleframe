<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for documents (TL types: Document). */
final class TlDocument extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tf_documents';

    protected $guarded = [];
}
