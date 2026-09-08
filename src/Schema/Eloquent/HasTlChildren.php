<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lazy vector access (spec §6.2): child rows are separate queries, never
 * auto-loaded attributes.
 */
trait HasTlChildren
{
    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $related
     */
    public function tlChild(string $related, string $foreignKey = 'parent_id'): HasMany
    {
        $children = $this->hasMany($related, $foreignKey, 'id');
        $children->orderBy('idx'); // mutates the underlying query; the relation itself is returned

        return $children;
    }
}
