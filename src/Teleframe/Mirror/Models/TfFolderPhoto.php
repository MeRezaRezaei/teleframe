<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * Hand-authored NF5 child of tf_folders — the FK→ChatPhoto photo fact.
 * Flattened ChatPhoto union (chatPhotoEmpty / chatPhoto): constructor
 * discriminator plus the scalar payload fields.
 *
 * @property int $account_id
 * @property int $id
 * @property string $constructor
 * @property bool $has_video
 * @property int $photo_id
 * @property string $stripped_thumb
 * @property int $dc_id
 */
final class TfFolderPhoto extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_folders_photo';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'has_video' => 'bool',
        'photo_id' => 'int',
        'dc_id' => 'int',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(TfFolder::class, 'id', 'id');
    }
}
