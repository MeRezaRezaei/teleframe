<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * The ingest clue ledger — what the mirror FAILED to store, kept.
 *
 * The mirror computes FOUR clue buckets on every write and, as of 2026-10-02,
 * threw all four away on the way to the console: nothing in the repository
 * recorded that a constructor was not in the catalog, that a keying column was
 * missing from a payload, that an FK constraint failed, or that a value did not
 * fit the column the DDL gives it. `teleframe:ingest:gate` printed them, printed
 * "Integrity OK" beside them, and exited 0. A coverage gap was therefore
 * visible for exactly as long as the terminal scrollback survived it.
 *
 * One row per clue string, in the bucket it belongs to. THE FOUR BUCKETS STAY
 * DISTINCT COLUMNS OF `bucket`, never merged: "the value does not fit the
 * column" (MySQL 1265/1364/1366/1406, Postgres 22001) is a column-shape fault,
 * "a relationship the mirror lacks" is a graph fault, and a ledger that files
 * one under the other sends the next reader to the wrong place.
 *
 * LOCAL/APP STATE, NOT A MIRROR TABLE: this row is not a Telegram fact, so it
 * has no `constructor` discriminator, no FK, and no key at all — it is appended
 * and never upserted. `table` names the tf_ table the clue is about, parsed out
 * of the clue's established `"{$table}: …"` prefix, or `'*'` when the clue
 * names none. `seen_at` is monotonic with the ingest watermark, so a clue can
 * never fall outside the resync window of the run that produced it.
 *
 * @property int $account_id
 * @property string $bucket
 * @property string $table
 * @property string $detail
 * @property CarbonImmutable $seen_at
 */
final class TfIngestClue extends Model
{
    /** The decomposer's own coverage bucket — "this ctor is not in the catalog", "this key cannot be filled". */
    public const BUCKET_CLUES = 'clues';

    /** The FK/NOT NULL bucket — a relationship the mirror cannot satisfy. */
    public const BUCKET_FK = 'fkClues';

    /** The unmodelled-field bucket — shipped DDL does not carry this TL field. */
    public const BUCKET_UNMAPPED = 'unmappedClues';

    /** The value bucket — the value does not fit the column the DDL gives it. */
    public const BUCKET_VALUE = 'valueClues';

    /** Table name recorded when a clue names none. */
    public const TABLE_ANY = '*';

    protected $table = 'tf_ingest_clues';

    /** A ledger row is APPENDED, never replaced, so there is no autoincrement key to speak of. */
    public $incrementing = false;

    protected $primaryKey = null;

    /** @var list<string> */
    protected $fillable = [
        'account_id',
        'bucket',
        'table',
        'detail',
        'seen_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'seen_at' => 'immutable_datetime',
    ];

    /**
     * The four buckets an ingest report carries, in report order.
     *
     * @return list<string>
     */
    public static function buckets(): array
    {
        return [
            self::BUCKET_CLUES,
            self::BUCKET_FK,
            self::BUCKET_UNMAPPED,
            self::BUCKET_VALUE,
        ];
    }

    /**
     * Append every clue of every non-empty bucket of an ingest report.
     *
     * Takes the report WHOLE rather than one bucket at a time so no caller can
     * persist the bucket it happens to know about and silently drop the other
     * three — that omission, repeated at every layer, is the defect this whole
     * ledger exists to make impossible to repeat.
     *
     * The connection is a PARAMETER, not resolved from the container: the bus
     * path must write its ledger on the same connection as the rows it
     * describes, inside the transaction that covers them, so a clue can never
     * survive a mirror write that rolled back.
     *
     * @param  array<string, mixed>  $report  an ingest report carrying the four bucket keys
     * @param  CarbonImmutable|null  $seenAt  monotonic with the watermark; defaults to now
     * @return int the number of rows appended
     */
    public static function recordReport(
        ConnectionInterface $db,
        array $report,
        int $accountId,
        ?CarbonImmutable $seenAt = null,
    ): int {
        $seenAt ??= CarbonImmutable::now();
        $rows = [];

        foreach (self::buckets() as $bucket) {
            foreach (self::clueList($report[$bucket] ?? []) as $detail) {
                $rows[] = [
                    'account_id' => $accountId,
                    'bucket' => $bucket,
                    'table' => self::tableOf($detail),
                    'detail' => $detail,
                    'seen_at' => $seenAt,
                ];
            }
        }

        if ($rows === []) {
            return 0;
        }

        $db->table((new self)->getTable())->insert($rows);

        return count($rows);
    }

    /**
     * The table a clue string is about, read off its `"{$table}: …"` prefix.
     *
     * The prefix form is what every producer in the mirror already emits
     * (MirrorFactDecomposer, MirrorFactWriter). A clue that carries none — a
     * chunk-level "entry without a constructor" — is recorded against `'*'`
     * rather than guessed at.
     */
    public static function tableOf(string $detail): string
    {
        $separator = strpos($detail, ': ');

        if ($separator === false || $separator === 0) {
            return self::TABLE_ANY;
        }

        $head = substr($detail, 0, $separator);

        // A prefix with whitespace in it is a sentence that happened to contain
        // a colon, not a table name. `strpbrk` and not `preg_match('/\s/')`:
        // this class lives in src/Laravel (regex is allowed there), and a
        // character-set membership test has no pattern to compile on a path
        // that runs once per clue row.
        //
        // The set is chosen BY MEASUREMENT, not as a translation of `\s`, and
        // the two are NOT the same set: PCRE `\s` matches `\f` and does NOT
        // match `\0`, while the set below includes `\0` and omits `\f`. Neither
        // difference can misclassify a table name — `\0` terminates the string
        // and `\f` never appears in a table identifier — so this predicate is
        // the better one. Only the old "exactly what the regex said" was false.
        return strpbrk($head, " \t\n\r\0\x0B") !== false ? self::TABLE_ANY : $head;
    }

    /**
     * @param  mixed  $bucket
     * @return list<string>
     */
    private static function clueList($bucket): array
    {
        if (! is_array($bucket)) {
            return [];
        }

        $clues = [];
        foreach ($bucket as $clue) {
            if (is_string($clue)) {
                $clues[] = $clue;
            }
        }

        return $clues;
    }
}
