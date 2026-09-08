<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for decryptedMessageLayer of DecryptedMessageLayer (crc32 1be31789). */
final class TlDecryptedMessageLayerDecryptedMessageLayer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_decrypted_message_layer_decrypted_message_layer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'random_bytes' => 'string',
        'layer' => 'int',
        'in_seq_no' => 'int',
        'out_seq_no' => 'int',
        'message' => 'string',
    ];
}
