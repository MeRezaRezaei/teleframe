<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\DataCore\Obligation;

/**
 * Derived when the declaration carries entity-level limits (Unique, Relation,
 * Indexed). Entity limits force index/constraint shapes on the stored truth —
 * this obligation is the migration generator's data source.
 */
final class PersistenceObligation extends Obligation {}
