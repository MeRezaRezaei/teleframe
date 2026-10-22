<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;

/**
 * Nested-source walker — finds the mirror facts a payload carries NESTED.
 *
 * The ingest path routes on a payload's TOP-LEVEL constructor, so an object
 * that only ever appears as a nested field of another constructor is never
 * routed to its table. This walker closes that gap for the tables the catalog
 * has explicitly declared a `sources` entry for, and ONLY those: it never
 * guesses an edge, and it never descends into a field the catalog did not
 * declare. Everything it returns has been proven against the TL scheme at
 * catalog-load time (`MirrorCatalog::assertNestedSources`), so a returned fact
 * is a real constructor of the declared type, reached through a field the wire
 * genuinely carries.
 *
 * Wrapper descent: a declared source's parent is often itself nested (a
 * `UserFull` only ever arrives inside the RPC result `users.userFull`). So the
 * walk may pass through a node that has no mirror table of its own, descending
 * into any of its params whose TL type is a declared source parent. That keeps
 * the walk bounded by what the catalog declares while still reaching a
 * declaration at any depth.
 */
final class MirrorNestedSourceWalker
{
    /**
     * Hard depth bound on the descent. A declared source's parent sits one or
     * two wrapper levels down in every shipped declaration; the bound exists so
     * a malformed or hostile payload cannot spin the shared ingest path.
     */
    private const MAX_DEPTH = 8;

    public function __construct(private readonly MirrorCatalog $catalog) {}

    /**
     * Collect the nested facts a payload really carries.
     *
     * @param  array<string, mixed>  $payload  decoded TL payload
     * @return list<array{table: string, payload: array<string, mixed>}> one entry per nested fact
     */
    public function collect(array $payload): array
    {
        $found = [];
        $this->walk($payload, $found, []);

        return $found;
    }

    /**
     * Depth-bounded walk; the scheme is acyclic in practice but a hostile or
     * malformed payload must not be able to spin the ingest path.
     *
     * @param  array<string, mixed>  $node
     * @param  list<array{table: string, payload: array<string, mixed>}>  $found
     * @param  array<string, mixed>  $context  enclosing values to overlay on a nested fact
     */
    private function walk(array $node, array &$found, array $context, int $depth = 0): void
    {
        if ($depth > self::MAX_DEPTH) {
            return;
        }

        $ctor = (string) ($node['_'] ?? '');
        if ($ctor === '') {
            return; // not a constructor object — nothing to route
        }

        // 1. Declared sources carried by THIS constructor: the fact itself.
        foreach ($this->catalog->nestedSourcesFor($ctor) as $source) {
            $value = $node[$source->field] ?? null;
            if ($value === null) {
                continue; // absent fact → no row (row existence = fact existence)
            }

            $overlay = $context;
            foreach ($source->context as $contextField) {
                if (array_key_exists($contextField, $node)) {
                    $overlay[$contextField] = $node[$contextField];
                }
            }

            if ($source->isVector()) {
                foreach ($this->items($value) as $item) {
                    if (is_array($item) && isset($item['_'])) {
                        $found[] = ['table' => $this->tableFor($item['_'], $source), 'payload' => $this->overlay($item, $overlay)];
                    }
                }

                continue;
            }

            if (is_array($value) && isset($value['_'])) {
                $found[] = ['table' => $this->tableFor($value['_'], $source), 'payload' => $this->overlay($value, $overlay)];
            }
        }

        // 2. Wrapper descent: one of this node's params may CARRY a declared
        //    source parent. Only types that are DECLARED source parents are
        //    followed, so the walk stays bounded by the catalog.
        foreach ($this->declaredParentTypesFor($ctor) as $param) {
            $value = $node[$param->name] ?? null;
            if ($value === null) {
                continue;
            }
            if ($param->kind() === 'vector') {
                foreach ($this->items($value) as $item) {
                    if (is_array($item) && isset($item['_'])) {
                        $this->walk($item, $found, $context, $depth + 1);
                    }
                }

                continue;
            }
            if (is_array($value) && isset($value['_'])) {
                $this->walk($value, $found, $context, $depth + 1);
            }
        }
    }

    /**
     * The table a collected item belongs to.
     *
     * ## WHY THE ITEM'S OWN CONSTRUCTOR DECIDES, NOT THE DECLARED EDGE
     *
     * A `sources` entry names an EDGE — "the object carried by
     * `messages.Dialogs.chats` is a mirror fact" — and until 2026-10-11 one
     * declared edge always meant one declared table, because every catalog
     * entry that claimed a TL type claimed ALL of its constructors.
     *
     * That stopped being true when the shipped `Chat` union was split across
     * two tables, which the DDL requires: `tf_chats` holds
     * `chatEmpty`/`chat`/`chatForbidden` and `tf_channels` holds
     * `channel`/`channelForbidden`, because `channel` ids are NEGATIVE and
     * `tf_channels.id` is the peer FK target for `peer_type 3`
     * (`2026_09_14_200002_create_tf_chats_tables.php:13-15`,
     * `2026_09_14_200003_create_tf_channels_tables.php:12-14`, and
     * `Naming::CTOR_DOMAIN_OVERRIDES` in `src/Schema/Generator/Naming.php:131-133`).
     * One `chats` VECTOR now carries constructors belonging to two different
     * tables, and a per-edge table name routes every one of them to whichever
     * entry happened to declare the edge.
     *
     * MEASURED, before this method existed: a `messages.dialogs` page carrying
     * one `chat` and one `channel` produced the `chat` row correctly and then
     * emitted, for the channel,
     *
     *     tf_chats: constructor 'channel' is not in catalog ctors — cannot place the fact
     *
     * — the channel fact DROPPED, silently except for a clue, on the first page
     * of every dialogs fetch. The ctor→table index already existed and is what
     * `MirrorIngesterFactory` routes the TOP-LEVEL payload by
     * (`src/Laravel/MirrorIngesterFactory.php:121`); this applies the same
     * answer one level down.
     *
     * The declared table remains the fallback, and it is the fallback for the
     * overwhelming majority: a nested source whose constructors no catalog entry
     * claims is exactly the case `MirrorNestedSource` exists for (a union store
     * with no table of its own, like `tf_channel_participants`), and those must
     * keep resolving to the edge's table.
     *
     * @param  string  $ctor  the collected item's own constructor name
     */
    private function tableFor(string $ctor, MirrorNestedSource $source): string
    {
        if ($ctor === '') {
            return $source->tfName;
        }

        $owner = $this->catalog->tableForCtor($ctor);

        return $owner->tfName ?? $source->tfName;
    }

    /**
     * Merge the enclosing context UNDER the child's own fields, so a nested
     * fact's own data always wins a name collision with its context.
     *
     * @param  array<string, mixed>  $child
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function overlay(array $child, array $context): array
    {
        if ($context === []) {
            return $child;
        }

        return $context + $child;
    }

    /**
     * Params of this constructor that can LEAD to a declared source parent.
     *
     * ## Why the gate used to require the current node to BE a parent
     *
     * This method originally opened with
     *
     *     foreach ($this->catalog->nestedSources() as $source) {
     *         if (! isset($source->parentCtorMap[$ctor])) { continue; }
     *
     * — i.e. it only ever descended when the node it was standing on was
     * ITSELF already a declared parent. That is true of every edge the
     * catalog shipped, and it is exactly why the missing edges looked
     * unreachable rather than merely undeclared: each of those twenty-one
     * parents (`updateWebPage`, `payments.starsStatus`, `messages.allStickers`,
     * …) is a TOP-LEVEL constructor that the ingest path hands to
     * `collect()` directly, so step 1 above found its facts on the very first
     * node and the descent was never exercised for them.
     *
     * A `message` edge has no such luck. `newMessage` is top-level, but the
     * backfill and the live `messages.messages` / `messages.chats` envelopes
     * are NOT — the ingest path hands the walker the ENVELOPE, and the message
     * is one element of its `messages:Vector<Message>` param. Measured on the
     * real artifacts: with a VALID, load-validated `{"ctor":"message",
     * "field":"media"}` edge installed, `collect(messages.messages)` returned
     * **zero** facts, while `collect(message)` returned the media fact. An edge
     * into a node the walk cannot enter is worse than no edge, because it
     * reads as fixed while placing nothing.
     *
     * The fix is to descend on the TYPE rather than on the node's identity: any
     * param whose baseType is a declared source's parentTlType is followed,
     * whether or not the current node is itself one of its constructors. The
     * walk stays bounded exactly as before — only catalog-declared types are
     * followed, and `MAX_DEPTH` still caps the descent — and step 1 still runs
     * first, so a node that IS a parent keeps finding its own facts before any
     * descent.
     *
     * @return list<TlParam>
     */
    private function declaredParentTypesFor(string $ctor): array
    {
        $out = [];
        foreach ($this->catalog->nestedSources() as $source) {
            foreach ($this->catalog->paramsOfCtor($ctor) as $param) {
                // The field the source reads is on THIS ctor; the param that
                // must be descended is a different field of the same TL type.
                if ($param->name === $source->field) {
                    continue;
                }
                if ($param->baseType() === $source->parentTlType) {
                    $out[$param->name] = $param;
                }
            }
        }

        return array_values($out);
    }

    /**
     * A structurally-valid vector payload: a list, or an associative array
     * whose keys are all numeric (a decoded TL vector can arrive either way).
     *
     * @return list<mixed>
     */
    private function items(array $value): array
    {
        foreach (array_keys($value) as $key) {
            if (! is_int($key)) {
                return []; // not a vector — a scalar or a nested object
            }
        }

        return array_values($value);
    }
}
