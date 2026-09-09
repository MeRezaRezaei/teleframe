<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for attachMenuBotIconColor of AttachMenuBotIconColor (crc32 4576f3f0). */
final class TlAttachMenuBotIconColorAttachMenuBotIconColor extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_attach_menu_bot_icon_color_attach_menu_bot_icon_color';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'name' => 'string',
        'color' => 'int',
    ];
}
