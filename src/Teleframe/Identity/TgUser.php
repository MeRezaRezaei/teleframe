<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Identity;

use Illuminate\Database\Eloquent\Model;

/**
 * Optional ready-made base for the telegram-bound Laravel User (the mini-app
 * user is exactly the "contactable AND logged-in-as" union HasUserTelegram
 * composes). Extend it, or `use` the traits on your own model directly.
 *
 * Deliberately does NOT pull in Authenticatable/Notifiable — the host decides
 * that surface (the guards accept any `Illuminate\Contracts\Auth\Guard`
 * compliant object). This class only carries the binding identity layer.
 *
 * Ships because the module's traits are always analysed against a real in-repo
 * consumer (repo precedent: `Schema\Eloquent\HasTlChildren` -> `TlAnchorModel`),
 * so phpstan types their bodies on every gate run instead of skipping orphans.
 */
abstract class TgUser extends Model
{
    use HasUserTelegram;
}