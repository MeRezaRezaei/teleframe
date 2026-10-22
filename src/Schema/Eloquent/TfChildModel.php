<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

/**
 * Base for generated NF5 child (1:1 fact / 1:N vector) tables.
 * Real PK is composite (account_id, parent-id[, position]); Eloquent gets a
 * scalar getKey() via 'parent_id' — fine because all queries are account-scoped
 * relations, never ->find() across accounts.
 */
abstract class TfChildModel extends TfMirrorModel
{
    protected $primaryKey = 'parent_id';
}
