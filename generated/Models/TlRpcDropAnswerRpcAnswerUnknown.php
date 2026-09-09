<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for rpc_answer_unknown of RpcDropAnswer (crc32 5e2ad36e). */
final class TlRpcDropAnswerRpcAnswerUnknown extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_rpc_drop_answer_rpc_answer_unknown';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
