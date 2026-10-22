# Telegram database-copy verification — Goal 241

Authority of record: `schema/sources/TL_telegram_v227.tl` (+ `TL_mtproto_v1.tl`, `TL_secret.tl`).
Teleframe's job is to COPY, not design (D-034 / verbatim 2026-10-05). Breakage is evidence of a
fidelity gap, never a licence to relax the schema.

## Audit (bin/audit-fidelity.php) — MySQL 8.4

Run: `timeout -k 5 600 php bin/audit-fidelity.php --engine=mysql --no-write`

| verdict | count |
|---|---|
| comparisons | 37 |
| COPYING-GAP | 0 |
| STORAGE-TRANSLATION | 27 |
| DELIBERATE | 10 |
| UNEXPLAINED | 0 |

The 27 STORAGE-TRANSLATION rows are relational translations of wire `Vector<…>` (element
index → `position` column) and the 10 DELIBERATE rows are recorded, individually-justified
divergences (column widen, peer-ref edges, local vault, engine-receipt store). Zero unexplained.

## Real-engine verification (MySQL track, TELEFRAME_MYSQL=1)

All 17 files in `tests/MySql/` pass against MySQL 8.4.11:

- CuratedDialMigratesOnMysqlTest — 3 tests / 452 assertions
- ForeignKeysAreEnforcedOnMysqlTest — 2 / 21
- EveryClassifiedEndpointIngestsOnMysqlTest — 12 / 808
- PolymorphicRelationsResolveThroughTfPeersOnMysqlTest — 3 / 231
- RelationConstraintsAreEnforcedTest — 5 / 51
- EveryFkableRelationHasAConstraintTest — 4 / 26
- Tf*FamilyFkOnMysqlTest (bot, channel participants, channels, dialog filters,
  saved star gifts, todo lists, users, update differences misc) — all green
- TfBytesColumnsAreUnboundedOnMysqlTest — 2 / 63
- TfTodoListsTitleTextIsNotNullOnMysqlTest — 5 / 12
- DropFalseSiblingForeignKeyOnMysqlTest — 4 / 36
- BackfillCrawlReachesMessageIdOneOnMysqlTest — 5 / 42
- RelationGraphTraversesOnMysqlTest — 4 / 29
- MysqlTrackIsReachableTest — 1 / 3

G4 traversal proof: an update ingested through the production path reads back through Eloquent
with the chain validating (row → parent → parent's parent → child of row); an orphan is NOT
readable through the graph. Verified on the real engine.

## Remaining (G2 — blind spots, not discrepancies)

- 385 columns with no derivable TL provenance across 145 tables (audit blind spots, not
  type mismatches; many are scalar wire fields the resolver cannot attribute).
- 27 `tf_*` tables with neither a catalog entry nor resolver provenance.
- 217 `tf_*` tables with no catalog entry.
- CI wiring of `bin/audit-fidelity.php` (G3) and the relation map (G9/G10) are open.

These are unchecked surface, not measured divergence: nothing in them has been shown to differ
from Telegram; they simply have not been attributed yet. Closing them is per-column/per-table
justification work, not a schema change.

## Mapping reference

- Telegram TL → teleframe tables/columns: `docs/schema/fidelity-audit.md` (generated, current).
- Relation graph: `docs/schema/mirror-relation-graph.md` (823 lines, 262 tables, 467 FK groups).
- Mirror catalog (hand-maintained, loaded on the live ingest path): `src/Schema/schema/mirror-catalog.json`.
- Authority coverage counts: `authority_coverage` block of `bin/audit-fidelity.php --format=json`.

## Conclusion

The shipped schema is a faithful copy of Telegram's data model on the axes the audit measures
(type family, width, signedness, key shape, nullability, cardinality, FK descent): zero
COPYING-GAP on both SQLite and MySQL 8.4, all real-engine tests green. No discrepancy required
a fix in this pass.
