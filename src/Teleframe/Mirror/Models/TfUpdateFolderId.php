<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

/**
 * Hand-authored NF5 mirror — table tf_updates_folder_id. 1:1 folder_id of a dialog-folder update.
 * Keyed by the full composite parent key; account-scoped.
 * @property int $account_id
 * @property int $seq
 * @property int $position
 * @property int $folder_id
 */
final class TfUpdateFolderId extends MirrorChildModel
{
    protected $table = 'tf_updates_folder_id';

    /** @var list<string> */
    protected $fillable = ['account_id', 'seq', 'position', 'folder_id'];

    /** @var array<string, string> */
    protected $casts = ['account_id' => 'int', 'seq' => 'int', 'position' => 'int', 'folder_id' => 'int'];
}
