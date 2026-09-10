<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMsgsAllInfoMsgsAllInfoMsg_ids;

/** Constructor model for msgs_all_info of MsgsAllInfo (crc32 8cc0d131). */
final class TlMsgsAllInfoMsgsAllInfo extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_msgs_all_info_msgs_all_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'info' => 'string',
    ];

    public function msgIds(): HasMany
    {
        return $this->tlChild(TlMsgsAllInfoMsgsAllInfoMsg_ids::class);
    }
}
