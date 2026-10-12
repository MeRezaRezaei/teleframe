<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * The ONE Eloquent touchpoint of the passport module (design §6 ruling).
 *
 * Passport login reuses the durable `tl_user_bindings` row via
 * `Identity\Bindings::bindLaravelUser` — NO new table. This seam exists so
 * that single Eloquent call site is an injected default
 * (`EloquentBindingWriter`), keeping `PassportLoginService` plain-PHP and the
 * tests Eloquent-free (Memory writer).
 *
 * Contract: `bind()` records the binding for (telegram id, tenant account,
 * resolved Laravel user) and returns whatever the backing implementation
 * returns (a `TlUserBinding` from the Eloquent writer, the recorded row from
 * the memory writer). Pure data — zero container/facade calls in the
 * interface itself.
 */
interface BindingWriter
{
    /**
     * Bind a Laravel user to a telegram identity, tenant-scoped.
     *
     * @param  int  $tgId  The stable telegram bigint identity (tl_user_id).
     * @param  int|null  $accountId  Tenant account id; null = no-tenant host row.
     * @param  object  $user  The resolved Laravel user (an Eloquent Model for
     *                        the Eloquent writer; any object for memory/fakes).
     * @return mixed The binding artifact — `TlUserBinding` from the Eloquent
     *               writer, the recorded row `['tg_id'=>..,'account_id'=>..,'user'=>..]`
     *               from the memory writer.
     */
    public function bind(int $tgId, ?int $accountId, object $user): mixed;
}
