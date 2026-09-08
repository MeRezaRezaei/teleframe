<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

/**
 * Base for constructor instance models (spec §4.2): shared PK with its anchor.
 * Composes HasTlChildren here as well — instance models are exactly the models
 * that own vector child rows (generated models re-use the same trait).
 */
abstract class TlInstanceModel extends TlAnchorModel
{
    use HasTlChildren;
}
