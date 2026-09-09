<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messages.stickerSetInstallResultSuccess of messages.StickerSetInstallResult (crc32 38641628). */
final class TlMessagesStickerSetInstallResultStickerSetInstallResultSuccess extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_sticker_set_install_result_sticke_16d19216a0b6';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
