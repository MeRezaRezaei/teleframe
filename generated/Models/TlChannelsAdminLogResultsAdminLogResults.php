<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsAdminLogResultsAdminLogResultsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsAdminLogResultsAdminLogResultsEvents;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsAdminLogResultsAdminLogResultsUsers;

/** Constructor model for channels.adminLogResults of channels.AdminLogResults (crc32 ed8af74d). */
final class TlChannelsAdminLogResultsAdminLogResults extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channels_admin_log_results_admin_log_results';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function events(): HasMany
    {
        return $this->tlChild(TlChannelsAdminLogResultsAdminLogResultsEvents::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChannelsAdminLogResultsAdminLogResultsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChannelsAdminLogResultsAdminLogResultsUsers::class);
    }
}
