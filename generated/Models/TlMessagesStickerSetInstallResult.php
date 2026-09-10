<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.StickerSetInstallResult (spec §4.1). */
final class TlMessagesStickerSetInstallResult extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_sticker_set_install_result_sticke_41df7d9fd353';

    protected $guarded = [];
}
