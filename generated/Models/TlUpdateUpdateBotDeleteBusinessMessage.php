<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotDeleteBusinessMessageMessages;

/** Constructor model for updateBotDeleteBusinessMessage of Update (crc32 a02a982e). */
final class TlUpdateUpdateBotDeleteBusinessMessage extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_bot_delete_business_message';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'connection_id' => 'string',
        'qts' => 'int',
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlUpdateUpdateBotDeleteBusinessMessageMessages::class);
    }
}
