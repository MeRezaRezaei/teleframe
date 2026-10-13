<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\MessageSearchIndexer;

/**
 * artisan teleframe:reindex-search — rebuild tf_message_search for one account.
 *
 * WHY THIS COMMAND EXISTS AT ALL
 *
 * `MessageSearchIndexer::reindexAccount()` was written for exactly this job and
 * is resumable and keyset-paginated — but until this command, it had NO CALLER
 * anywhere in the repository: no console command, no service, no test, only a
 * comment in the migration that created the table promising "a separate,
 * resumable, out-of-band step". That comment was a promise the code did not
 * keep. This command is the missing half.
 *
 * It became load-bearing rather than merely convenient when the tokeniser
 * started NFC-normalising (MessageSearchIndexer::toNfc). Tokens already sitting
 * in tf_message_search were written WITHOUT normalisation, and NFC of a
 * decomposed token yields the PRECOMPOSED spelling, which does not equal the
 * decomposed bytes in the column. So a message indexed before this change stops
 * matching its own text after it — a silent false negative, i.e. a message that
 * used to be findable becoming unfindable with no signal to the caller. This
 * command is the remedy, and it must be run per account after deploying.
 *
 * READ-TIME-ONLY NORMALISATION IS NOT A REMEDY, and this is why
 *
 * It is tempting to fix the mismatch by normalising the query alone, since the
 * query is the cheap side. That does not work, and the reason is worth stating
 * because it is the non-obvious part of the decision: normalising a query token
 * produces the PRECOMPOSED form, and the row already stored holds the
 * DECOMPOSED bytes, so the reader would be searching for a token that is
 * guaranteed not to be present. Normalisation has to happen on the side that
 * STORES the bytes, which is this command. Storing BOTH spellings instead would
 * double the row count and would break the reader's
 * `HAVING COUNT(DISTINCT token) = count($tokens)`, because the query would then
 * carry two constraints per term where one was meant. Reindexing is the only
 * strategy that keeps every token single-valued.
 *
 * THE COST, STATED HONESTLY
 *
 * A reindex is O(sum of distinct tokens across the account's messages), not
 * O(messages): each message deletes and re-inserts one row per distinct token,
 * so a message with N distinct tokens costs 2N row operations. Messages of
 * ordinary length carry tens of tokens; the ceiling is
 * MessageSearchIndexer::MAX_TOKENS per message. The walk is batched (--batch)
 * with a keyset watermark over the whole (peer_type, peer_id, id) key, so
 * memory stays bounded on an account with a deep history and an interrupted run
 * resumes with --after-* rather than rescanning from message 1.
 *
 * It is out-of-band and offline BY DESIGN: no migration is issued, no table is
 * locked, and the derived index can be rebuilt at any time from tf_messages,
 * which remains the truth. Running it while a daemon is ingesting is safe —
 * index() is a delete-then-insert per message keyed on the message's own
 * (account_id, peer_type, peer_id, id), so the worst case is that a message
 * being written concurrently is indexed by whichever side wins, and the next
 * run converges it.
 */
final class ReindexSearchCommand extends Command
{
    protected $signature = 'teleframe:reindex-search
        {account? : Telegram account id (tenant). Omit to list the accounts that have messages}
        {--batch=500 : Messages per keyset page}
        {--after-peer-type=0 : Resume watermark: peer_type component}
        {--after-peer-id=0 : Resume watermark: peer_id component}
        {--after-id=0 : Resume watermark: message id component}';

    protected $description = 'Rebuild the tf_message_search token index for one account (NFC-normalised).';

    public function handle(MessageSearchIndexer $indexer): int
    {
        $account = $this->argument('account');

        if ($account === null) {
            $this->listAccounts();

            return self::SUCCESS;
        }

        $accountId = (int) $account;
        $batch = max(1, (int) $this->option('batch'));

        $report = $indexer->reindexAccount(
            $accountId,
            (int) $this->option('after-id'),
            $batch,
            (int) $this->option('after-peer-type'),
            (int) $this->option('after-peer-id'),
        );

        $this->line(sprintf(
            'account %d: reindexed %d message(s), %d token row(s) written.',
            $accountId,
            $report['messages'],
            $report['tokens'],
        ));

        // The watermark is printed rather than stored because a reindex is
        // idempotent: re-running a completed account rewrites the same rows and
        // costs time, not correctness, so the resume flags exist for an
        // INTERRUPTED run, not for an incremental one.
        $this->line(sprintf(
            'resume watermark if this run was interrupted: --after-peer-type=%d --after-peer-id=%d --after-id=%d',
            (int) $this->option('after-peer-type'),
            (int) $this->option('after-peer-id'),
            $report['lastId'],
        ));

        return self::SUCCESS;
    }

    /**
     * Print the accounts that actually have messages to reindex.
     *
     * Listed rather than required, because the account id a host needs is the
     * Telegram user id and an operator should not have to guess which of their
     * rows are worth reindexing. tf_messages is the truth table here, NOT
     * tf_message_search: a message whose index rows were lost is exactly the
     * case this command exists to repair, so counting the index would hide it.
     */
    private function listAccounts(): void
    {
        $rows = DB::table('tf_messages')
            ->select('account_id')
            ->selectRaw('COUNT(*) AS messages')
            ->groupBy('account_id')
            ->orderBy('account_id')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No messages found — nothing to reindex.');

            return;
        }

        $this->line('Accounts with messages (run `teleframe:reindex-search <id>` for each):');

        foreach ($rows as $row) {
            $this->line(sprintf('  account %-12s %d message(s)', $row->account_id, $row->messages));
        }
    }
}
