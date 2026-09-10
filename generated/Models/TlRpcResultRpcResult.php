<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for rpc_result of RpcResult (crc32 f35c6d01). */
final class TlRpcResultRpcResult extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_rpc_result_rpc_result';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'req_msg_id' => 'int',
        'result' => 'int',
    ];
}
