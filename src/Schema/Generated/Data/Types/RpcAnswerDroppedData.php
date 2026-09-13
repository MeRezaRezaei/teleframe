<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for rpc_answer_dropped of RpcDropAnswer.
 */
final class RpcAnswerDroppedData extends TlRpcDropAnswerAbstractData
{
    public function __construct(
    public int $msgId,
    public int $seqNo,
    public int $bytes,
    ) {
    }
}
