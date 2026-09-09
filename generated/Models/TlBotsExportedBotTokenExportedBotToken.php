<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for bots.exportedBotToken of bots.ExportedBotToken (crc32 3c60b621). */
final class TlBotsExportedBotTokenExportedBotToken extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bots_exported_bot_token_exported_bot_token';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'token' => 'string',
    ];
}
