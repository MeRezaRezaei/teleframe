<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Hand-authored NF5 mirror of the EncryptedChat union (table tf_encrypted_chats).
 * Constructor discriminates the five encryptedChat* ctors; keyed (account_id, id).
 *
 * @property int $account_id
 * @property string $constructor
 * @property int $id
 * @property int $access_hash
 * @property int $date
 * @property int $admin_id
 * @property int $participant_id
 */
final class TfEncryptedChat extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_encrypted_chats';

    protected $guarded = [];

    protected $casts = [
        'id' => 'int',
        'access_hash' => 'int',
        'date' => 'int',
        'admin_id' => 'int',
        'participant_id' => 'int',
    ];
}
