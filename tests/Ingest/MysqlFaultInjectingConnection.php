<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteConnection;
use PDOException;
use RuntimeException;

/**
 * A REAL sqlite connection that reports ONE chosen insert failure with a MySQL
 * driver code and message — the honest way to test MySQL error classification
 * on a host whose only database is sqlite.
 *
 * ## WHY THIS EXISTS AT ALL, stated plainly and without pretending otherwise
 *
 * MySQL's "your value does not fit its column" family is **1366 / 1265 / 1406 /
 * 1364**, and those codes are MYSQL DRIVER codes: they arrive in
 * `PDOException::$errorInfo[1]`, never in the SQLSTATE slot. SQLite cannot emit
 * any of them — sqlite does not even enforce `VARCHAR(n)` length, so it has no
 * error to give — and its own SQLSTATE is `HY000` for EVERY error it ever
 * produces, which is why `MirrorFactWriter::isUnknownColumn()` has to fall back
 * to a phrase match on that one driver.
 *
 * So a test that wants to prove "the writer degrades on MySQL 1366" cannot get
 * there from sqlite on its own. Pretending otherwise — hand-writing a test that
 * calls the classifier directly, or asserting on a message string — would prove
 * nothing about the LOOP, and the loop is the entire subject: that one bad row
 * must not take the page's transaction down with it.
 *
 * ## WHAT IS REAL HERE AND WHAT IS THE ONE SIMULATED PART
 *
 * REAL:
 *  - the schema is the SHIPPED DDL, executed by `ShippedStoreSchema::capsuleFor()`
 *    (never transcribed), so the column the row violates is a real column;
 *  - the INSERT is really attempted against that real table and really fails —
 *    this class never fabricates a failure, it intercepts a real one;
 *  - Laravel's own `Connection::runQueryCallback()` still wraps the PDO
 *    exception into a `QueryException`, so the object the writer catches is
 *    constructed by the framework, not by hand.
 *
 * SIMULATED, AND ONLY THIS:
 *  - the DRIVER CODE (`errorInfo[1]`) and the driver MESSAGE. This class
 *    rewrites them at the connection boundary — which is precisely the layer
 *    where a real MySQL driver supplies its own code in place of sqlite's.
 *    Everything downstream of that boundary is production code, untouched.
 *
 * The `insert into "tf_channel_participants"` match is what scopes the rewrite
 * to the OFFENDING table only, so every other query on the connection — the
 * schema `PRAGMA`s the writer uses for `getColumnListing()`/`getIndexes()`, and
 * the survivor row's own insert — runs against sqlite completely unmodified.
 *
 * Provenance of the fixture: `tf_channel_participants` ships
 * `$table->string('constructor', 64)` with NO default and NO nullable, outside
 * its `primary(['account_id','channel_id','user_id'])`
 * (src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:76).
 * A row that covers the whole key but omits `constructor` therefore reaches the
 * INSERT and is refused — which is the exact shape of the operator's live 1364
 * ("Field 'constructor' doesn't have a default value").
 */
final class MysqlFaultInjectingConnection extends SQLiteConnection
{
    /** Lowercased `insert into "<table>"` prefix this connection rewrites. */
    private string $faultTarget;

    /** MySQL DRIVER code written into `errorInfo[1]` (e.g. 1366). */
    private int $faultCode;

    /** MySQL driver message written into `errorInfo[0..2]`. */
    private string $faultMessage;

    /**
     * SQLSTATE written into `errorInfo[0]`.
     *
     * Defaults to MySQL's own generic `HY000`, which is what a real MySQL server
     * returns for 1366/1265/1406/1364 — and which is exactly why the writer must
     * read the DRIVER code in slot 1, because slot 0 says nothing at all. Tests
     * that need a specific SQLSTATE (42S22 for ER_BAD_FIELD_ERROR, say) pass it.
     */
    private string $faultSqlState;

    /** Every insert this connection actually intercepted, for assertions. */
    private int $injected = 0;

    public function __construct(
        $pdo,
        string $faultTable,
        int $faultCode,
        string $faultMessage,
        string $faultSqlState = 'HY000',
    ) {
        // ':memory:' and an empty prefix mirror what ShippedStoreSchema's capsule
        // configures; the PDO handed in is that capsule's, so both connection
        // objects see the SAME database and the same shipped tables.
        parent::__construct($pdo, ':memory:', '', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        if ($faultTable === '') {
            throw new RuntimeException('a fault table is required');
        }

        $this->faultTarget = 'insert into "'.$faultTable.'"';
        $this->faultCode = $faultCode;
        $this->faultMessage = $faultMessage;
        $this->faultSqlState = $faultSqlState;
    }

    /**
     * How many inserts this connection rewrote — i.e. how many times the writer
     * really did attempt an insert and really was refused by the database.
     */
    public function injectedFaults(): int
    {
        return $this->injected;
    }

    /**
     * {@inheritdoc}
     *
     * The single overridden seam. The INSERT is genuinely executed first by the
     * parent implementation; only its FAILURE is re-reported, carrying the
     * MySQL driver code the writer must classify.
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        if (! str_contains(strtolower((string) $query), $this->faultTarget)) {
            return parent::runQueryCallback($query, $bindings, $callback);
        }

        // Actually attempt the statement. If it SUCCEEDS there is no MySQL fault
        // to report, and silently inventing one would make the fixture lie in the
        // opposite direction — a green test proving nothing.
        try {
            return parent::runQueryCallback($query, $bindings, $callback);
        } catch (QueryException) {
            $this->injected++;

            // Mirrors Illuminate\Database\QueryException::__construct():71 — a
            // QueryException only carries errorInfo when its previous is a
            // PDOException, so the code MUST arrive that way to exist at all.
            $pdo = new PDOException($this->faultMessage);
            $pdo->errorInfo = [$this->faultSqlState, $this->faultCode, $this->faultMessage];

            throw new QueryException(
                $this->getNameWithReadWriteType(),
                (string) $query,
                $this->prepareBindings($bindings),
                $pdo,
                $this->getConnectionDetails(),
                $this->latestReadWriteTypeUsed(),
            );
        }
    }
}