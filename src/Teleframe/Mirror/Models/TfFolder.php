<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the Folder union (table tf_folders).
 * Single ctor (folder) — no constructor discriminator column.
 *
 * @property int $account_id
 * @property int $id
 * @property string $title
 * @property bool $autofill_new_broadcasts
 * @property bool $autofill_public_groups
 * @property bool $autofill_new_correspondents
 */
final class TfFolder extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_folders';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'autofill_new_broadcasts' => 'bool',
        'autofill_public_groups' => 'bool',
        'autofill_new_correspondents' => 'bool',
    ];

    public function photo(): HasOne
    {
        return $this->hasOne(TfFolderPhoto::class, 'id', 'id');
    }
}
