<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannelForbidden;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChatEmpty;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChatForbidden;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUserEmpty;
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;

/**
 * Referenced-entity aggregation (plan Task 5): resolve the anchor for a
 * user/chat/channel by (tenant, telegram id) with its CURRENT instance
 * loaded as the `currentInstance` relation — the anchor's latest
 * constructor (its discriminator follows constructor transitions, e.g.
 * user → userEmpty), skipping instances flagged deleted (e.g. User.deleted,
 * flags.5). Null when the tenant never saw the entity or its current
 * instance is deleted.
 *
 * Identities live on the instance tables (per-constructor), so resolution
 * walks the constructor family of the namespace, then pins the anchor
 * through its account_id — no global lookups by telegram id (tenancy
 * contract).
 */
final class EntityAggregator
{
    /**
     * @var list<class-string<TlInstanceModel>>
     */
    private const USER_INSTANCES = [TlUserUser::class, TlUserUserEmpty::class];

    /**
     * Channels are Chat-namespace anchors in TL truth (channel and
     * channelForbidden construct Chat) — chat() and channel() resolve the
     * same truth, channel() only being the intent-revealing spelling.
     *
     * @var list<class-string<TlInstanceModel>>
     */
    private const CHAT_INSTANCES = [
        TlChatChat::class,
        TlChatChatEmpty::class,
        TlChatChatForbidden::class,
        TlChatChannel::class,
        TlChatChannelForbidden::class,
    ];

    /** @var array<string, int>|null instance table => constructor id (lazy) */
    private static ?array $constructorIds = null;

    public function user(int $accountId, int $tgId): ?TlUser
    {
        return $this->lookupAnchor(TlUser::class, self::USER_INSTANCES, $accountId, $tgId);
    }

    public function chat(int $accountId, int $tgId): ?TlChat
    {
        return $this->lookupAnchor(TlChat::class, self::CHAT_INSTANCES, $accountId, $tgId);
    }

    /**
     * Channel-facing alias of chat(): same Chat-namespace anchors (TL
     * truth — channel/channelForbidden construct Chat).
     */
    public function channel(int $accountId, int $tgId): ?TlChat
    {
        return $this->lookupAnchor(TlChat::class, self::CHAT_INSTANCES, $accountId, $tgId);
    }

    /**
     * Anchor for (tenant, telegram id) with the CURRENT instance attached
     * as `currentInstance` — or null when absent (unknown id, other
     * tenant, or the current instance is deleted).
     *
     * @template TAnchor of TlAnchorModel
     *
     * @param class-string<TAnchor> $anchorClass
     * @param list<class-string<TlInstanceModel>> $instanceClasses
     *
     * @return TAnchor|null
     */
    private function lookupAnchor(string $anchorClass, array $instanceClasses, int $accountId, int $tgId): ?object
    {
        /** @var TAnchor|null $anchor */
        $anchor = null;
        foreach ($instanceClasses as $instanceClass) {
            $ids = $instanceClass::query()->where('tl_id', $tgId)->pluck('id')->all();
            if ($ids === []) {
                continue;
            }

            $anchor ??= $anchorClass::query()
                ->where('account_id', $accountId)
                ->whereIn('id', $ids)
                ->first();
        }

        if ($anchor === null) {
            return null;
        }

        $current = $this->currentInstance(
            $instanceClasses,
            (string) $anchor->getKey(),
            (int) $anchor->getAttribute('constructor_id'),
        );

        // An anchor without a resolvable current instance (deleted) is
        // not an addressable entity.
        return $current === null ? null : $anchor->setRelation('currentInstance', $current);
    }

    /**
     * The CURRENT instance among the anchor's constructor family rows (an
     * anchor can carry one row per constructor table — shared PKs, e.g.
     * user + userEmpty after an upstream deletion): the row matching the
     * anchor's constructor discriminator (the latest constructor ingested)
     * when it is not deleted, else the latest non-deleted row by timestamp.
     *
     * @param list<class-string<TlInstanceModel>> $instanceClasses
     */
    private function currentInstance(array $instanceClasses, string $anchorId, int $anchorConstructorId): ?TlInstanceModel
    {
        $best = null;
        $bestTs = null;
        foreach ($instanceClasses as $instanceClass) {
            /** @var TlInstanceModel|null $candidate */
            $candidate = $instanceClass::query()->find($anchorId);
            if ($candidate === null) {
                continue;
            }
            if ((bool) $candidate->getAttribute('deleted')) {
                continue; // deleted-flagged instances never resolve as current
            }
            if (self::constructorIdFor($instanceClass) === $anchorConstructorId) {
                return $candidate; // the anchor's current constructor wins
            }
            $at = $candidate->getAttribute('updated_at');
            $ts = $at instanceof \DateTimeInterface ? $at->getTimestamp() : 0;
            if ($bestTs === null || $ts > $bestTs) {
                $best = $candidate;
                $bestTs = $ts;
            }
        }

        return $best;
    }

    /**
     * Constructor id behind an instance model, via the shared metamodel
     * (instance tables are per-constructor; Naming::instanceTable is the
     * generator's own mapping, so this cannot drift).
     *
     * @param class-string<TlInstanceModel> $instanceClass
     */
    private static function constructorIdFor(string $instanceClass): ?int
    {
        if (self::$constructorIds === null) {
            $map = [];
            foreach (UpdateIngestor::constructors() as $name => $ctor) {
                $map[Naming::instanceTable($ctor->resultType, $name)] = $ctor->id;
            }
            self::$constructorIds = $map;
        }

        $table = (new $instanceClass())->getTable();

        return self::$constructorIds[$table] ?? null;
    }
}
