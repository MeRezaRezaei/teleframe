<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Base for hand-authored NF5 child (1:1 fact / 1:N vector) tables of the
 * updates domain. Real PK is composite (account_id, parent-id); Eloquent's
 * scalar primaryKey is a best-effort account_id stand-in because composite
 * keys have no Eloquent equivalent — every query is account-scoped.
 *
 * Never instantiated directly; concrete children override $table. Querying
 * is always done through the parents' composite-key accessors, which build
 * account-scoped builders via Model::query() + explicit WHERE clauses.
 */
class MirrorChildModel extends TfChildModel
{
    protected $primaryKey = 'account_id';
}
