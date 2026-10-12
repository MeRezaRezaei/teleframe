<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Union across tf_messages and tf_messages_service for a single peer.
 *
 * WHY THE PROJECTION IS EXPLICIT
 * ------------------------------
 * The two tables are NOT column-identical, and they never were. The mirror's
 * own generator guarantees the difference: MirrorCtorSplitter (src/Schema/
 * Mirror/MirrorCtorSplitter.php:31-64) builds each table by INTERSECTING the
 * catalog with one constructor's parameter list. An intersection is a subset
 * by construction, so two constructors with different signatures necessarily
 * produce two different column sets.
 *
 * Measured against the shipped DDL
 * (src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php,
 * tf_messages at :84, tf_messages_service at :414):
 *
 *   22 columns / 13 columns, 12 in common:
 *     account_id, id, peer_type, peer_id, constructor, date, out, mentioned,
 *     media_unread, silent, post, legacy
 *   only on tf_messages (10):
 *     message, from_scheduled, edit_hide, pinned, noforwards, invert_media,
 *     offline, video_processing_pending, paid_suggested_post_stars,
 *     paid_suggested_post_ton
 *   only on tf_messages_service (1):
 *     reactions_are_possible
 *
 * A bare `select *` from both sides is therefore a guaranteed SQL error on
 * every driver ("SELECTs to the left and right of UNION ALL do not have the
 * same number of result columns"), for every input. This class shipped
 * exactly that.
 *
 * WHY `NULL` AND NOT A DEFAULT
 * ----------------------------
 * A column a side does not carry is padded with an explicit `NULL AS col`,
 * never with the column's `default(...)` value. The two are different claims:
 * `false`/`0`/`''` asserts the peer message is not pinned, not offline, has no
 * text; `NULL` asserts only that "pinned" is not a question Telegram asks
 * about a service message. Padding with defaults would let a caller read a
 * fabricated fact off a row that has no such fact — the same class of defect
 * as filtering a parent on a child's column, which UserQuery's docblock
 * already calls out. NULL is the honest value, and a caller that wants
 * coalescing can apply it once, in one place, at the edge.
 *
 * Telegram keeps the two id spaces disjoint (a peer message and a service
 * message never share an id), so UNION ALL cannot duplicate and the
 * `orderByDesc('id')` on the outer query is a true merged timeline.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php:174,715;
 * src/Schema/Mirror/MirrorCtorSplitter.php:31-64;
 * tests/Schema/Mirror/MessagesUnionTest.php (executes this query against that DDL).
 */
final class MessagesUnion
{
    /**
     * Columns of tf_messages, in shipped-DDL order.
     *
     * @var list<string>
     */
    private const MESSAGE_COLUMNS = [
        'account_id', 'id', 'peer_type', 'peer_id', 'constructor', 'date',
        'message', 'out', 'mentioned', 'media_unread', 'silent', 'post',
        'from_scheduled', 'legacy', 'edit_hide', 'pinned', 'noforwards',
        'invert_media', 'offline', 'video_processing_pending',
        'paid_suggested_post_stars', 'paid_suggested_post_ton',
    ];

    /**
     * Columns of tf_messages_service, in shipped-DDL order.
     *
     * @var list<string>
     */
    private const SERVICE_COLUMNS = [
        'account_id', 'id', 'peer_type', 'peer_id', 'constructor', 'date',
        'out', 'mentioned', 'media_unread', 'reactions_are_possible',
        'silent', 'post', 'legacy',
    ];

    /**
     * Peer history across both message tables, newest id first.
     *
     * The projection is the union of both column sets, so every returned row
     * carries every column name a caller may ask about; a side that lacks one
     * yields NULL for it (see the class docblock for why NULL, not a
     * default). Ordering is applied to the OUTER query, i.e. after the
     * union, so it is the merged order across both tables.
     *
     * @param  int  $accountId  owning account
     * @param  int  $peerType   peer type discriminator (user/chat/channel)
     * @param  int  $peerId     peer id within that type
     *
     * @return Builder union query, ORDER BY id DESC
     */
    public static function forPeer(int $accountId, int $peerType, int $peerId): Builder
    {
        $base = fn () => DB::table('tf_messages')
            ->select(self::projection(self::MESSAGE_COLUMNS, self::MESSAGE_COLUMNS, self::SERVICE_COLUMNS))
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId);
        $service = fn () => DB::table('tf_messages_service')
            ->select(self::projection(self::SERVICE_COLUMNS, self::MESSAGE_COLUMNS, self::SERVICE_COLUMNS))
            ->where('account_id', $accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId);

        return $base()->unionAll($service())->orderByDesc('id');
    }

    /**
     * Build the union's column list: every column either table has, in the
     * order of the wider table, with `NULL AS col` for a column the querying
     * side does not carry.
     *
     * @param  list<string>  $own  columns the querying table actually has
     * @param  list<string>  $left  columns of the left (messages) table
     * @param  list<string>  $right  columns of the right (service) table
     *
     * @return list<mixed> select-list entries, ready for Builder::select()
     */
    private static function projection(array $own, array $left, array $right): array
    {
        $columns = array_values(array_unique(array_merge($left, $right)));
        $list = [];

        foreach ($columns as $column) {
            $list[] = in_array($column, $own, true)
                ? DB::raw($column)
                : DB::raw('NULL AS '.$column);
        }

        return $list;
    }
}
