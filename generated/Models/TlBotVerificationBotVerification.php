<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for botVerification of BotVerification (crc32 f93cd45c). */
final class TlBotVerificationBotVerification extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_bot_verification_bot_verification';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'bot_id' => 'int',
        'icon' => 'int',
        'description' => 'string',
    ];
}
