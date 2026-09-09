<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for attachMenuBotsNotModified of AttachMenuBots (crc32 f1d88a5c). */
final class TlAttachMenuBotsAttachMenuBotsNotModified extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_attach_menu_bots_attach_menu_bots_not_modified';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
