<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for rpc_answer_dropped of RpcDropAnswer (crc32 a43ad8b7). */
final class TlRpcDropAnswerRpcAnswerDropped extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_rpc_drop_answer_rpc_answer_dropped';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
        'seq_no' => 'int',
        'bytes' => 'int',
    ];
}
