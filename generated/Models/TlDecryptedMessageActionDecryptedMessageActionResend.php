<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for decryptedMessageActionResend of DecryptedMessageAction (crc32 511110b0). */
final class TlDecryptedMessageActionDecryptedMessageActionResend extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_decrypted_message_action_decrypted_message_44851f6ed12e';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'start_seq_no' => 'int',
        'end_seq_no' => 'int',
    ];
}
