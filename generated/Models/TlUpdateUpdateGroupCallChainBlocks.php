<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputGroupCall;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateGroupCallChainBlocksBlocks;

/** Constructor model for updateGroupCallChainBlocks of Update (crc32 a477288f). */
final class TlUpdateUpdateGroupCallChainBlocks extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_group_call_chain_blocks';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'sub_chain_id' => 'int',
        'next_offset' => 'int',
    ];

    public function blocks(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateGroupCallChainBlocksBlocks::class);
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(TlInputGroupCall::class, 'call');
    }
}
