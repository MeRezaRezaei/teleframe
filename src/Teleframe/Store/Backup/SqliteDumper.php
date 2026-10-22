<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Backup;

use PDO;
use PDOException;
use Throwable;

/**
 * SqliteDumper — the sqlite half of the dump surface, and the reason the
 * operator's DEFAULT BEHAVIOUR works at all.
 *
 * The verbatim asked for this: "the default behaviour is it will be
 * installed in a laravel so it can simply dump the laravel database and make
 * a backup from it in schedules to a channel"
 * (.brain/verbatim/2026-09-24-notification-logger-backup-and-bot-framework.md).
 * A stock Laravel host's database IS sqlite, and before this class the
 * package shipped MysqlDumper + PostgresDumper and nothing else — so the
 * operator's default behaviour refused on the framework's own default
 * database, and a host had to hand-write a DatabaseDumper on the shipped
 * seam to get the dump the default already promised.
 *
 * NO DUMP BINARY, and that is the whole design difference, not an omission.
 * mysqldump/pg_dump are separate programs with their own format, their own
 * flags and their own PATH; sqlite has no such program on a stock host
 * (`sqlite3` the CLI is frequently absent even where pdo_sqlite — which
 * Laravel's sqlite connection REQUIRES — is present). So this dumper reads
 * the database through PDO, which is guaranteed to be there on the one
 * engine the default install can be, and emits the same SQL text the sqlite
 * CLI's `.dump` emits: `PRAGMA foreign_keys=OFF; BEGIN TRANSACTION;`, then
 * every user object's own CREATE statement, then every row as an INSERT,
 * then `COMMIT;`. A dump is a seekable spool file either way (DumpWorkspace
 * owns it), and it is uploaded through the very same existing chunk/seal
 * path as the other two.
 *
 * OUTPUT IS THE EXISTING OUTPUT CONTRACT, unchanged: DumpResult, so the
 * object name is still `{database}-{Y-m-d-His}.sql` (DumpResult::objectName)
 * and the bytes are still spooled, sealed and uploaded by
 * DatabaseBackupService exactly as a mysqldump dump is. Nothing downstream
 * learned about sqlite.
 *
 * FIDELITY — THE FOUR THINGS THAT WOULD MAKE THIS A LOSSY DUMPER, AND WHAT IS
 * DONE ABOUT EACH. An honest limitation is worth more here than a green gate:
 *
 *  1. A blob and a text value arrive from PDO as the SAME PHP string, so a
 *     dumper that quotes values would store every blob back as TEXT and the
 *     restored database would no longer equal the dumped one. So every value
 *     is read beside its own `typeof()` — SQLite's own storage-class verdict,
 *     not a guess — and blobs are emitted as `X'…'` with SQLite's `hex()`
 *     doing the conversion, so the bytes are never re-copied in PHP.
 *  2. A GENERATED column cannot be named in an INSERT (sqlite refuses it), so
 *     `PRAGMA table_xinfo`'s `hidden` flag (2 or 3) drops those columns from
 *     both the SELECT and the INSERT column list, while a virtual table's
 *     `hidden = 1` storage column — which IS insertable — is kept. A SQLite
 *     too old for table_xinfo falls back to `table_info`, which reports no
 *     generated column at all and therefore behaves exactly like an ordinary
 *     pre-3.31 table.
 *  3. Auto-created indexes have no CREATE statement (`sql IS NULL` in
 *     sqlite_master) and are rebuilt by their table's own DDL, and the
 *     `sqlite_%` bookkeeping (sqlite_sequence, sqlite_stat1, …) is derived
 *     state. Both are skipped on purpose: emitting them would be wrong, not
 *     merely noisy.
 *  4. A float is rendered with 17 significant digits — what a double
 *     round-trips through — instead of `var_export`, which silently depends
 *     on the host's `serialize_precision`. The `.0` guard keeps 9.0 a REAL
 *     rather than letting SQLite store the INTEGER 9 in an affinity-less
 *     column.
 *
 * ONE CONSISTENT SNAPSHOT, with the cost stated rather than hidden: the whole
 * read runs inside a single transaction, which is what makes the dump a
 * snapshot rather than a torn read across a moving database — the same
 * single-transaction posture `mysqldump --single-transaction` takes. SQLite
 * takes its lock on the FILE, not the connection, so a dump in flight does
 * block the host's WRITES for as long as it runs. Readers are never blocked,
 * and there is no way to take a consistent snapshot without this.
 *
 * WHAT IT CANNOT DO, plainly: it does not drive a restore. The shipped
 * DatabaseRestorer feeds bytes to the `mysql` / `psql` CLIENT binaries and
 * accepts no other driver; a sqlite dump is restored by feeding its SQL to a
 * sqlite connection (PDO, or the `sqlite3` CLI if the host has one), which
 * is what the round-trip test under tests/Store/Backup proves by executing
 * the emitted SQL against a fresh database and comparing row for row.
 *
 * Resolution of the database itself, in two rules and no guessing: the
 * target's `database` is the path when it names a readable file, and
 * otherwise the injected $resolveConnection answers — which is how the
 * Laravel provider hands this dumper the host's OWN default connection's
 * sqlite file with no extra configuration. A target that is neither throws
 * by name, loudly, because a backup that silently dumped nothing is the
 * failure the no-show watchdog exists to catch.
 *
 * Provenance: the verbatim above + the shipped siblings
 * src/Teleframe/Store/Backup/{DatabaseDumper,MysqlDumper,PostgresDumper}.php
 * (same contract, same spool, same DumpResult, same loud-failure line).
 */
final class SqliteDumper implements DatabaseDumper
{
    /** Largest single fwrite into the spool file (S8: one bounded write at a time). */
    private const WRITE_CHUNK = 262144;

    /**
     * @param  (?\Closure(DatabaseTarget): ?PDO)|null  $resolveConnection  the
     *                                                                     host's own sqlite connection, handed in by the Laravel provider.
     *                                                                     Null = this dumper opens the target's own file path itself, so
     *                                                                     the class stays plain-PHP constructible with no container
     *                                                                     (standalone-smoke gate) and offline tests need no host at all.
     */
    public function __construct(
        private readonly ?\Closure $resolveConnection = null,
    ) {}

    /**
     * Dump the target sqlite database into a fresh workspace spool file.
     *
     * @throws BackupException when the database cannot be resolved or read, or
     *                         the spool cannot be written
     */
    public function dump(DatabaseTarget $target, DumpWorkspace $workspace): DumpResult
    {
        $started = time();
        $spool = $workspace->spool();
        $handle = $spool['handle'];

        [$pdo, $owns] = $this->connectionFor($target);

        try {
            // ERRMODE_EXCEPTION so a bad read THROWS into the catch below
            // rather than returning a false statement that this class would
            // treat as an empty result — an empty dump is a failure here, not
            // a success. STRINGIFY_FETCHES=false keeps sqlite's own types
            // (int/float) instead of stringifying everything, which is what
            // makes the typeof()-paired literals faithful.
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);

            $tables = $this->snapshot($handle, $pdo, $target);
        } catch (PDOException $e) {
            throw new BackupException('the sqlite dump failed: '.$e->getMessage(), previous: $e);
        } finally {
            fflush($handle);

            if ($owns) {
                $pdo = null; // close the connection this dumper opened
            }
        }

        $bytes = fstat($handle);
        $size = is_array($bytes) ? (int) $bytes['size'] : 0;

        // EXIT 0 IS NOT SUCCESS, and its sqlite shape is an EMPTY SCHEMA
        // rather than a zero-byte file: this dumper always writes a header,
        // so a dump of a database with no tables in it comes out as a
        // perfectly readable transaction containing nothing. Carrying that
        // into DatabaseBackupService would store it, write an 'ok' ledger row
        // and — the part that matters — TOUCH THE WATCHDOG HEARTBEAT, so a
        // backup that captured nothing would silence the one check whose
        // whole job is to notice that backups stopped happening. Throwing
        // HERE, before the DumpResult exists, makes the heartbeat
        // unreachable rather than merely un-touched: the service calls dump()
        // as the FIRST statement of its try block, so this jumps straight to
        // its catch, which releases the spool and rethrows.
        if ($tables === 0) {
            throw new BackupException(sprintf(
                'the sqlite dump wrote NO SCHEMA OBJECTS for database "%s" — nothing reached the spool. '
                .'An empty database file is the signature of an unmigrated application or a `tables` whitelist that matches '
                .'no table, not of a working backup. Nothing was stored, no run was recorded and no watchdog heartbeat was '
                .'taken, so the no-show check stays armed. Migrate the host (or fix the dump scope) and re-run.',
                $target->database,
            ));
        }

        if ($size === 0) {
            throw new BackupException(sprintf(
                'the sqlite dump reported a clean read but wrote a ZERO-BYTE dump for database "%s" — nothing reached the spool. '
                .'Nothing was stored, no run was recorded and no watchdog heartbeat was taken, so the no-show check stays '
                .'armed. Fix the dump scope (or the schema) and re-run.',
                $target->database,
            ));
        }

        return new DumpResult(
            $spool['path'],
            $size,
            $target->driver,
            $target->database,
            $started,
            time(),
            0,
            '',
        );
    }

    /**
     * The sqlite version line this host's engine reports, or null when
     * pdo_sqlite is not available at all — the sibling dumpers' `probe()`
     * contract, where the missing thing is a binary rather than a driver.
     */
    public function probe(): ?string
    {
        if (! in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            return null;
        }

        try {
            $pdo = new PDO('sqlite::memory:');
            $version = $pdo->query('SELECT sqlite_version()');
            if ($version === false) {
                return null;
            }
            $text = trim((string) $version->fetchColumn());
        } catch (PDOException) {
            return null;
        }

        if ($text === '') {
            return null;
        }

        return 'sqlite '.$text.' via pdo_sqlite';
    }

    /**
     * THE WHOLE DUMP, inside one read transaction: the header, every table's
     * DDL followed by its rows, then the remaining objects (indexes,
     * triggers, views) and the commit.
     *
     * @return int the number of TABLES dumped — the zero-table refusal above
     *             keys off this, not off the byte count
     */
    private function snapshot($handle, PDO $pdo, DatabaseTarget $target): int
    {
        // Foreign keys are switched OFF in the DUMP ITSELF, as the first
        // statement, so the restore can insert rows in any order the tables
        // happen to come back in. It has to be that first statement: sqlite
        // ignores a foreign_keys change made inside a transaction, which is
        // exactly where the BEGIN below puts us.
        $this->write($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n");

        // ONE consistent snapshot of the source, for the whole read — the
        // posture `mysqldump --single-transaction` takes. Without it a
        // concurrent write can change the schema or the rows between two of
        // these statements and the dump restores into something that was
        // never a database. The cost is stated in the class docblock: sqlite
        // locks the FILE, so writers block for the length of the dump.
        $pdo->beginTransaction();

        try {
            $objects = $this->readSchema($pdo);
            $tables = $this->selectedTables($objects, $target->tables);

            $dumped = 0;

            foreach ($objects as $object) {
                if ($object['type'] !== 'table' || ! in_array($object['name'], $tables, true)) {
                    continue;
                }

                $this->write($handle, $this->statement($object['sql']));
                $this->writeRows($handle, $pdo, $object['name']);
                $dumped++;
            }

            // Indexes, triggers and views come AFTER the data, deliberately:
            // an index built before its rows exist is slower to populate, and
            // a trigger is far cheaper to exist while the rows are inserted.
            foreach ($objects as $object) {
                if ($object['type'] === 'table' || ! $this->belongsTo($object, $tables, $target->tables)) {
                    continue;
                }

                $this->write($handle, $this->statement($object['sql']));
            }

            $this->write($handle, "COMMIT;\n");
            $pdo->commit();
        } catch (Throwable $e) {
            // Never hand a half-read transaction back to the host: a caller
            // that supplied this connection (the provider's resolver does)
            // would otherwise find its own next query inside a transaction
            // this dump opened.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        return $dumped;
    }

    /**
     * Every user object in the database, in sqlite_master's own creation
     * order.
     *
     * `sql IS NOT NULL` drops the auto-indexes sqlite creates for UNIQUE and
     * PRIMARY KEY constraints — they carry no statement of their own and are
     * rebuilt by their table's own DDL. `name NOT LIKE 'sqlite_%'` drops the
     * internal bookkeeping (sqlite_sequence, sqlite_stat1, …), which is
     * derived state a restore recreates by itself. The LIKE is SQL's, in the
     * statement, not a pattern in PHP: this module is zero-regex.
     *
     * @return list<array{type: string, name: string, tbl_name: string, sql: string}>
     */
    private function readSchema(PDO $pdo): array
    {
        $rows = $pdo->query(
            'SELECT type, name, tbl_name, sql FROM sqlite_master'
            .' WHERE sql IS NOT NULL AND name NOT LIKE \'sqlite_%\' ORDER BY rowid',
        );

        if ($rows === false) {
            throw new BackupException('cannot read the sqlite schema (sqlite_master) — the database is not readable as sqlite.');
        }

        $objects = [];
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $objects[] = [
                'type' => (string) ($row['type'] ?? ''),
                'name' => $name,
                'tbl_name' => (string) ($row['tbl_name'] ?? ''),
                'sql' => (string) ($row['sql'] ?? ''),
            ];
        }

        return $objects;
    }

    /**
     * The tables this dump covers: everything when no whitelist is
     * configured, else the named tables PLUS any table whose `tbl_name` is a
     * named one — which is how a virtual table's shadow tables arrive. Their
     * rows are the virtual table's content, so a whitelist naming `fts` and
     * leaving out `fts_data` would otherwise restore an empty index.
     *
     * @param  list<array{type: string, name: string, tbl_name: string, sql: string}>  $objects
     * @param  list<string>|null  $whitelist
     * @return list<string>
     */
    private function selectedTables(array $objects, ?array $whitelist): array
    {
        $tables = [];

        foreach ($objects as $object) {
            if ($object['type'] !== 'table') {
                continue;
            }

            if ($whitelist === null || $whitelist === []) {
                $tables[] = $object['name'];

                continue;
            }

            if (in_array($object['name'], $whitelist, true) || in_array($object['tbl_name'], $whitelist, true)) {
                $tables[] = $object['name'];
            }
        }

        return $tables;
    }

    /**
     * Whether a non-table object belongs in this dump: it belongs to one of
     * the dumped tables (every index and trigger does), or it was named in a
     * whitelist itself (a view names itself in tbl_name, so it would
     * otherwise never be picked up).
     *
     * @param  array{type: string, name: string, tbl_name: string, sql: string}  $object
     * @param  list<string>  $tables
     * @param  list<string>|null  $whitelist
     */
    private function belongsTo(array $object, array $tables, ?array $whitelist): bool
    {
        if ($whitelist !== null && $whitelist !== [] && in_array($object['name'], $whitelist, true)) {
            return true;
        }

        return in_array($object['tbl_name'], $tables, true);
    }

    /**
     * One table's rows, as INSERT statements.
     *
     * `typeof()` beside every value is the whole trick: PDO hands back a blob
     * and a text value as the same PHP string, so without sqlite's own
     * storage-class verdict every blob in the database would be restored as
     * TEXT. `hex()` converts a blob to its literal form inside sqlite, so the
     * bytes are never re-copied through PHP, and the CASE leaves every other
     * storage class untouched.
     *
     * @param  resource  $handle
     */
    private function writeRows($handle, PDO $pdo, string $table): void
    {
        $quoted = self::quote($table);
        $columns = $this->insertableColumns($pdo, $table);

        if ($columns === []) {
            return; // every column is generated: the DDL is the whole truth
        }

        $select = [];
        $names = [];
        foreach ($columns as $column) {
            $column = self::quote($column);
            $select[] = 'typeof('.$column.')';
            $select[] = "(CASE typeof({$column}) WHEN 'blob' THEN hex({$column}) ELSE {$column} END)";
            $names[] = $column;
        }

        $rows = $pdo->query('SELECT '.implode(', ', $select).' FROM '.$quoted);
        if ($rows === false) {
            throw new BackupException('cannot read the rows of table "'.$table.'" for the sqlite dump.');
        }

        $prefix = 'INSERT INTO '.$quoted.' ('.implode(', ', $names).') VALUES (';
        $count = count($columns);

        while (($row = $rows->fetch(PDO::FETCH_NUM)) !== false) {
            $literals = [];
            for ($i = 0; $i < $count; $i++) {
                $literals[] = self::literal((string) $row[$i * 2], $row[($i * 2) + 1], $pdo);
            }

            $this->write($handle, $prefix.implode(', ', $literals).");\n");
        }
    }

    /**
     * The columns a row of $table can be given values for, in table order.
     *
     * `PRAGMA table_xinfo` is the only regex-free way to see a GENERATED
     * column: its `hidden` flag is 2 (virtual) or 3 (stored), and sqlite
     * refuses an INSERT that names one. `hidden = 1` is a virtual table's
     * own storage column — insertable, so kept.
     *
     * A sqlite too old for table_xinfo returns no rows for the pragma, and
     * `table_info` is then the fallback: it has no `hidden` column and never
     * reports a generated column, which is exactly right for the sqlite
     * versions that cannot have one.
     *
     * @return list<string>
     */
    private function insertableColumns(PDO $pdo, string $table): array
    {
        $rows = $this->pragma($pdo, 'table_xinfo', $table);
        $hiddenAware = true;

        if ($rows === []) {
            $rows = $this->pragma($pdo, 'table_info', $table);
            $hiddenAware = false;
        }

        $columns = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '') {
                continue;
            }

            if ($hiddenAware && in_array((int) ($row['hidden'] ?? 0), [2, 3], true)) {
                continue;
            }

            $columns[] = $name;
        }

        return $columns;
    }

    /**
     * @return list<array<string, mixed>> the pragma's rows, or [] when the
     *                                    pragma is unsupported or the table
     *                                    does not exist
     */
    private function pragma(PDO $pdo, string $name, string $table): array
    {
        $rows = $pdo->query('PRAGMA '.$name.'('.self::quote($table).')');

        return $rows === false ? [] : $rows->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * One value as a sqlite literal, keyed on the storage class sqlite
     * itself reported for it — never on the PHP type, which cannot tell a
     * blob from text.
     */
    private static function literal(string $storageClass, mixed $value, PDO $pdo): string
    {
        if ($storageClass === 'null') {
            return 'NULL';
        }

        if ($storageClass === 'integer') {
            return (string) $value;
        }

        if ($storageClass === 'real') {
            return self::real($value);
        }

        if ($storageClass === 'blob') {
            return "X'".$value."'";
        }

        return $pdo->quote((string) $value);
    }

    /**
     * A REAL literal that round-trips: 17 significant digits is what a double
     * needs, chosen here rather than inherited from the host's
     * `serialize_precision` (which `var_export` would silently obey and which
     * a host may well have set to something lossy). The `.0` guard keeps 9.0
     * a REAL — without a decimal point sqlite would store the INTEGER 9 in a
     * column with no affinity, and the restored value would not equal the
     * dumped one.
     */
    private static function real(mixed $value): string
    {
        $float = (float) $value;

        if (is_nan($float)) {
            return 'NULL'; // sqlite stores NaN as NULL itself; nothing else is faithful
        }

        if (is_infinite($float)) {
            return $float > 0 ? '9.0e999' : '-9.0e999'; // sqlite's own infinity spelling
        }

        $text = sprintf('%.17G', $float);
        if (! str_contains($text, '.') && ! str_contains($text, 'E')) {
            $text .= '.0';
        }

        return $text;
    }

    /** A CREATE statement, terminated exactly once. */
    private function statement(string $sql): string
    {
        return rtrim(rtrim($sql), ';').";\n";
    }

    /**
     * Write to the spool in bounded slices, so one enormous INSERT (a row
     * carrying a large blob) never asks fwrite for the whole string at once.
     *
     * @param  resource  $handle
     * @return int the bytes written
     */
    private function write($handle, string $text): int
    {
        $written = 0;
        $total = strlen($text);

        while ($written < $total) {
            $slice = substr($text, $written, self::WRITE_CHUNK);
            $bytes = fwrite($handle, $slice);

            if ($bytes === false || $bytes === 0) {
                throw new BackupException('could not write the sqlite dump to the spool file.');
            }

            $written += $bytes;
        }

        return $written;
    }

    /**
     * The connection this dump reads, and whether we opened it.
     *
     * Two rules, in this order, and no guessing between them: a target whose
     * `database` names a readable file is opened directly (so the class works
     * standalone, with no host at all), and otherwise the injected resolver
     * answers — which is how the Laravel provider hands over the host's own
     * default connection's sqlite file. A host-supplied connection is NOT
     * closed here: this dumper opened nothing, and the host still owns it.
     *
     * @return array{0: PDO, 1: bool}
     */
    private function connectionFor(DatabaseTarget $target): array
    {
        $path = $target->database;

        if ($path !== '' && is_file($path)) {
            try {
                return [new PDO('sqlite:'.$path), true];
            } catch (PDOException $e) {
                throw new BackupException(
                    'cannot open the sqlite database file "'.$path.'": '.$e->getMessage(),
                    previous: $e,
                );
            }
        }

        if ($this->resolveConnection !== null) {
            $pdo = ($this->resolveConnection)($target);
            if ($pdo instanceof PDO) {
                return [$pdo, false];
            }
        }

        throw new BackupException(
            'cannot resolve a sqlite database to dump: teleframe.store.backup.database.database is "'
            .($path === '' ? '(empty)' : $path).'", which is not a readable file on this host, and no host '
            .'connection answered for it. Point that key at the sqlite file, or rebind '
            .BackupSupport::DUMPER_KEY.' with a dumper that can resolve it.',
        );
    }

    /** A double-quoted identifier, "" for a quote inside one. */
    private static function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
