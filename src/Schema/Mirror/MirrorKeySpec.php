<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumn;
use MeRezaRezaei\Teleframe\Schema\Mirror\Ddl\MirrorColumnType;

/**
 * A parent table's RESOLVED key: the key column names AND the SQL type each of
 * them resolved to, carried as ONE list of the parent's own `MirrorColumn`s.
 *
 * ## WHY THIS EXISTS — THE TWO HALVES USED TO BE TWO ANSWERS
 *
 * `MirrorTableResolver` derived a parent's key column TYPE from the entry's base
 * shape (`keyType()`) and then gave every child a hardcoded `BigInt` instead.
 * So a child of a string-keyed parent came out BIGINT while the parent it
 * belongs to came out TEXT — one key, two types, in the same resolver run:
 *
 *     tf_stars_transactions          [ROOT]   id:string (TEXT)     <- starsTransaction#13659eb0 … id:string
 *     tf_stars_transactions_amount   [child]  id:bigint (BIGINT)   <- contradicts its own parent
 *
 * The wire is unambiguous about which side is wrong:
 * `starsTransaction#13659eb0 … id:string` (`api_full.tl:1936`),
 * `starsSubscription#2e6eab1a … id:string` (`api_full.tl:1966`),
 * `botInlineResult#11965f3a … id:string` (`api_full.tl:816`) and
 * `businessChatLink#b4ae666f … link:string` (`api_full.tl:1897`) are all strings.
 *
 * ## WHY THE ASYMMETRY IS NOT COSMETIC
 *
 * A BIGINT written into a varchar column is a CAST that round-trips exactly
 * (`5633995061` → `'5633995061'` → `5633995061`). The REVERSE destroys the key:
 * a real string key read back through an integer cast yields `0`, so every
 * distinct key collapses onto one row. The narrow direction hides; the
 * destructive direction is the one the hardcoded type produced.
 *
 * ## THE SIZE OF IT, MEASURED
 *
 * MEASURED 2026-10-04 on the shipped catalog, walking the resolver's WHOLE
 * reachable graph (698 tables) and comparing every child key column's emitted
 * type against the type its own parent resolved for the same column name:
 * **81 of 927 child key columns disagreed**, across 9 parents —
 * `tf_messages` (29), `tf_stars_transactions` (21), `tf_messages_service` (10),
 * `tf_bot_inline_results` (6), `tf_dialog_filters` (6), `tf_stars_subscriptions`
 * (5), `tf_business_chat_links` (2), `tf_folders` (1), `tf_todo_items` (1).
 *
 * 34 of those 81 are the destructive TEXT-against-int direction this class
 * removes. The other 47 are int-width disagreements inside the `int` family
 * (`integer` parent against a `bigint` child) and cost nothing that sqlite or
 * MySQL can see; inheriting the parent's type settles them too, as a side
 * effect of there being ONE resolution rather than two.
 *
 * NOT all 34 are the child's fault, and the ones that are not are deliberately
 * left red. `tf_saved_star_gifts` resolves `id` to BIGINT on BOTH sides — no
 * self-contradiction — while its migration declares `string`, so its 16
 * children stay BIGINT here and
 * `ResolvedColumnsMatchShippedDdlTest::test_no_write_reachable_column_declares_a_sql_type_family_the_resolver_does_not`
 * keeps reporting them. That is the honest outcome: fixing them means changing
 * the PARENT's resolution, which is a different defect with a different owner,
 * not a child's disagreement with its own parent.
 *
 * ## WHY ONE OBJECT AND NOT A SECOND ARRAY
 *
 * Threading `list<string> $parentKeyCols` beside `array<string,
 * MirrorColumnType> $parentKeyTypes` would have re-created the defect's shape
 * one level up: two parallel collections that must agree, with nothing making
 * them agree. Here both answers are read off the SAME list, so they cannot
 * disagree — there is no second place to forget.
 *
 * ## WHY `childColumns()` DROPS THE `peer` FLAG
 *
 * A child's `peer_type`/`peer_id` are COPIED FROM THE PARENT ROW, not
 * re-derived from the child item's payload, so they must stay unflagged: a
 * child item (`MessageEntity`, `KeyboardButton`, …) carries no peer of its own,
 * and flagging them makes `MirrorFactDecomposer::fillPeerHalf()` report
 * "peer column 'peer' missing from payload" for every child of every message.
 * The types are inherited; the provenance is not. That asymmetry is deliberate
 * and lives here, beside the code that applies it, rather than being re-derived
 * at each of the four call sites.
 *
 * @see MirrorTableResolver::peerKeyColumns() for why the PARENT's key halves
 *      are flagged at all.
 */
final class MirrorKeySpec
{
    /**
     * @param  list<MirrorColumn>  $columns  the parent's resolved key columns, in key order
     */
    public function __construct(private readonly array $columns) {}

    /**
     * The key column NAMES, in key order — the `list<string>` every downstream
     * consumer (`MirrorTable::$keyColumns`, `MirrorFactDecomposer`, the DDL
     * `primary()`) has always read.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(static fn (MirrorColumn $c): string => $c->name, $this->columns);
    }

    /**
     * The parent's own key columns, flags included — the parent's row declares
     * them itself, so it keeps the `peer` marking `peerKeyColumns()` gave it.
     *
     * @return list<MirrorColumn>
     */
    public function parentColumns(): array
    {
        return $this->columns;
    }

    /**
     * A child's copy of the parent's key: `account_id` (always BIGINT — the
     * tenant column every `tf_*` table carries, never part of the derived key),
     * then each key column under its PARENT'S RESOLVED TYPE.
     *
     * The `_type` suffix rule the old `makeChildPkCols()` spelled inline is not
     * repeated here: a parent that declares `peer`/`peer_id` already resolves
     * `*_type` to TINYINT through `keyType()`'s expansion, and a key column the
     * parent never declares falls to the same suffix rule there — so the type
     * inherited from the parent IS the type the old rule produced, and it is
     * now produced once instead of twice.
     *
     * @return list<MirrorColumn>
     */
    public function childColumns(): array
    {
        $columns = [new MirrorColumn('account_id', MirrorColumnType::BigInt)];
        foreach ($this->columns as $column) {
            $columns[] = new MirrorColumn($column->name, $column->type);
        }

        return $columns;
    }
}
