<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMsgResendReqMsgResendAnsReqMsg_ids;

/** Constructor model for msg_resend_ans_req of MsgResendReq (crc32 8610baeb). */
final class TlMsgResendReqMsgResendAnsReq extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_msg_resend_req_msg_resend_ans_req';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function msgIds(): HasMany
    {
        return $this->tlChild(TlMsgResendReqMsgResendAnsReqMsg_ids::class);
    }
}
