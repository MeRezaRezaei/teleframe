<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

/**
 * WHICH RPC results get mirrored — the opt-in half of the RPC-result route.
 *
 * The route itself (`MirrorIngesterFactory::buildResultRoute()`) is a
 * callable, not a policy: it will write whatever it is handed. Something has
 * to say which method's result is worth handing it, and that is the decision
 * this class makes explicit rather than leaving to every caller's discretion.
 *
 * THE RULE, stated so a future author can apply it without reading further:
 *
 *   An RPC result is mirrored IFF the operator has ADMITTED the method that
 *   produced it. Admission is a list of exact method names; anything not on
 *   the list is not mirrored, and admitting a method buys nothing on its own —
 *   the catalog's `sources` edge is the second, independent gate, so an
 *   admitted method still writes only the facts the catalog declares.
 *
 * Two gates, deliberately. The catalog gate alone is not enough: the relay and
 * the notify probe both dial `users.getFullUser` for their own reasons, and an
 * always-on route would write a mirror row on every one of those calls —
 * writes nobody asked for, for accounts and users the operator never named.
 * The admission gate alone is not enough either: without a declared `sources`
 * edge the walker is blind, so admitting a method would promise rows that
 * cannot land (the lie the catalog already refuses to tell for
 * `tf_message_medias` / `tf_message_actions`).
 *
 * THE DEFAULT LIST is derived from the catalog, not guessed: every name is a
 * method that `src/Schema/schema/methods-mtproto.json` actually declares AND
 * whose result carries a constructor the catalog names as a `sources` parent.
 * Adding a method here without such a `sources` edge is legal but inert, and
 * adding a name the method catalog does not declare is a lie of the same
 * family the project has already refused once — `users.userFull` is NOT a
 * method, only a constructor, and listing it would admit a call that can
 * never be made.
 *
 * This follows the same convention as `ReadOnlyVaultDispatch`: an explicit
 * `private const` of exact names, `in_array(..., true)`, no regex, and a
 * docblock that justifies the classification instead of hiding it behind a
 * prefix. The relay's list answers "may this call run"; this one answers "is
 * this call's RESULT worth mirroring". Both are allow-lists, never
 * block-lists, so a method absent from the list is refused by construction.
 *
 * Plain PHP, no container, no config, no database: the Laravel side
 * (`MirrorIngesterFactory::resultPolicy()`) reads the operator's override and
 * hands it here, so this class stays constructible outside a host and the
 * decision is unit-testable on its own.
 *
 * Provenance: the admitted defaults are exactly the methods declared in
 * src/Schema/schema/methods-mtproto.json whose result carries a constructor
 * the mirror catalog declares as a `sources` parent — no name reaches this list
 * on assertion alone, and no declared method is admitted without that edge.
 * The profile half is `users.getFullUser` (userFull), `channels.getFullChannel`
 * (channelFull) and `messages.getFullChat` (chatFull), the three parents the
 * shipped `tf_bot_infos` entry names. The account-scoped half follows the same
 * rule over the same catalog: `payments.getSavedStarGifts` and
 * `payments.getStarsStatus`, `account.getBusinessChatLinks`,
 * `messages.getSavedDialogs` and `messages.getAllStickers`, each admitted for
 * the nested parent its result carries, plus `messages.getDialogs` — the
 * ACCOUNT's dialog page, not the saved one — admitted for the three nested
 * parents its result carries (`messages.dialogs.dialogs` → `tf_dialogs`,
 * `.users` → `tf_users`, `.chats` → `tf_chats`), the page that is the only
 * ordinary source of a peer `access_hash`. The allow-list shape follows
 * src/Laravel/Services/ReadOnlyVaultDispatch::READ_ONLY_METHODS.
 */
final class MirrorResultPolicy
{
    /**
     * Methods whose results are mirrored when the operator has not narrowed
     * the list. Each is verified present in the method catalog; each returns a
     * payload carrying a constructor the mirror catalog declares as a nested
     * source parent.
     *
     * ## `channels.getAdminLog` IS DELIBERATELY ABSENT — AND THAT IS NOT ONLY A CATALOG EDGE
     *
     * The general rule above would refuse it anyway, because no shipped catalog
     * entry declares `channels.adminLogResults` as a `sources` parent. But that
     * is the WEAK reason, and admitting it on the strength of a missing edge
     * would be wrong: the moment somebody adds that edge, this list goes from
     * safe to actively wrong with no other signal firing.
     *
     * The STRONG reason is structural. `channelAdminLogEvent#1fad68cd`
     * (`api_full.tl:1116`) is `id:long date:int user_id:long
     * action:ChannelAdminLogEventAction` — no chat id, no channel id, no peer.
     * Its container `channels.adminLogResults#ed8af74d` does not name a chat
     * either; the channel lives on the METHOD's `channel:InputChannel`
     * parameter. So `tf_admin_log_events`, which ships `primary(['account_id',
     * 'id'])`, has its SCOPE ABSENT rather than misplaced, and every event in
     * every channel this account can administer would land on the same key.
     *
     * This is not hypothetical bookkeeping: NF5 plan priority 4 exists for
     * exactly this table. The fix is not a key change — it is a resolver that
     * threads an RPC's chat parameter into the fact key, which no shipped
     * resolver does. Until that exists, admitting the method would convert a
     * latent defect into a live one, and `id:long` being globally unique per
     * channel is not a defence: it is unique only WITHIN a channel, and the
     * channel is precisely what is missing.
     *
     * Pinned by `MirrorScopedKeyCorrectnessTest::test_the_admin_log_method_is_not_admitted`.
     *
     * @var list<string>
     */
    public const DEFAULT_METHODS = [
        'users.getFullUser',     // → users.userFull      → userFull.bot_info
        'channels.getFullChannel', // → messages.chatFull  → channelFull.bot_info
        'messages.getFullChat',  // → messages.chatFull    → chatFull.bot_info
        'payments.getSavedStarGifts',   // → payments.savedStarGifts → gifts → tf_saved_star_gifts
        'payments.getStarsStatus',       // → payments.starsStatus → history/subscriptions → tf_stars_transactions / tf_stars_subscriptions
        'account.getBusinessChatLinks',  // → account.businessChatLinks → links → tf_business_chat_links
        'messages.getSavedDialogs',      // → messages.savedDialogs → dialogs → tf_saved_dialogs
        'messages.getDialogs',           // → messages.dialogs → dialogs/users/chats → tf_dialogs / tf_users (access_hash) / tf_chats
    ];

    /**
     * @param  list<string>  $admitted  exact method names; an empty list mirrors nothing
     */
    private function __construct(private readonly array $admitted) {}

    /**
     * A policy admitting exactly the given methods. Duplicates are collapsed
     * and order is preserved so `--list` output and error messages are stable.
     *
     * @param  list<string>  $methods
     */
    public static function admitting(array $methods): self
    {
        $admitted = [];
        foreach ($methods as $method) {
            $name = trim((string) $method);
            if ($name !== '' && ! in_array($name, $admitted, true)) {
                $admitted[] = $name;
            }
        }

        return new self($admitted);
    }

    /** The policy the mirror ships with. */
    public static function default(): self
    {
        return self::admitting(self::DEFAULT_METHODS);
    }

    /**
     * Whether this method's result is mirrored. Exact match only — a prefix
     * never admits, so a host cannot widen the list by naming a namespace.
     */
    public function mirrors(string $method): bool
    {
        return in_array(trim($method), $this->admitted, true);
    }

    /**
     * The admitted method names, in declaration order.
     *
     * @return list<string>
     */
    public function methods(): array
    {
        return $this->admitted;
    }
}
