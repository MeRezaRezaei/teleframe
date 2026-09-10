<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageContainerMsgContainerMessages;

/** Constructor model for msg_container of MessageContainer (crc32 73f1f8dc). */
final class TlMessageContainerMsgContainer extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_container_msg_container';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function messages(): HasMany
    {
        return $this->tlChild(TlMessageContainerMsgContainerMessages::class);
    }
}
