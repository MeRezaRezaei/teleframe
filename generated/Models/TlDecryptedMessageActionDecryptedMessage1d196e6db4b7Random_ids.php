<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param random_ids (table tl_decrypted_message_action_decrypted_message_eddbfc36281f). */
final class TlDecryptedMessageActionDecryptedMessage1d196e6db4b7Random_ids extends TlAnchorModel
{
    protected $table = 'tl_decrypted_message_action_decrypted_message_eddbfc36281f';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
