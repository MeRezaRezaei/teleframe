<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesStickerSetInstallResultSticke41df7d9fd353Sets;

/** Constructor model for messages.stickerSetInstallResultArchive of messages.StickerSetInstallResult (crc32 35e410a8). */
final class TlMessagesStickerSetInstallResultStickerSetInstallResultArchive extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_sticker_set_install_result_sticke_41df7d9fd353';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function sets(): HasMany
    {
        return $this->tlChild(TlMessagesStickerSetInstallResultSticke41df7d9fd353Sets::class);
    }
}
