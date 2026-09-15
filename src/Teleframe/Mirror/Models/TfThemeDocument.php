<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_themes — the FK→Document document fact.
 * Stores the referenced document's id (scalar child per the flat
 * decomposer; the nested Document union is deferred).
 *
 * @property int $account_id
 * @property int $id
 * @property int $document_id
 */
final class TfThemeDocument extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_themes_document';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'document_id' => 'int',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(TfTheme::class, 'id', 'id');
    }
}
