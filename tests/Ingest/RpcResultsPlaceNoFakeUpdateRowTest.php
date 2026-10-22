<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Mirror\EndpointCoverage;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasEndpointFixtures;

/**
 * A CARRIER places EVERY update it carries — and the control half proves the
 * gate is not vacuous.
 *
 * ## THE DEFECT THIS EXISTS FOR
 *
 * `EndpointCoverage` selected endpoints by the NAME PREFIX `update`, so six RPC
 * result types were classified as update endpoints. Three of them carried real
 * `Update`s inside a `Vector<Update>` field, and MEASURED on this tree before
 * the fix, by ingesting each fixture and reading `tf_updates` back:
 *
 * | ctor                        | row(s) written before the fix                          |
 * |-----------------------------|--------------------------------------------------------|
 * | `updates`                   | `updateChannelTooLong@515/0` — correctly unwrapped       |
 * | `updatesCombined`           | `updatePtsChanged@1/0` — correctly unwrapped            |
 * | `updates.difference`        | `updates.difference@0/0` and NOTHING ELSE               |
 * | `updates.differenceSlice`   | `updates.differenceSlice@0/0` and NOTHING ELSE          |
 * | `updates.channelDifference` | `updates.channelDifference@0/0` and NOTHING ELSE        |
 * | `updatesTooLong`            | no row at all                                          |
 *
 * So the difference family wrote a row naming the CONTAINER and dropped the
 * updates it had fetched. Those three are exactly the replies to
 * `updates.getDifference` and `updates.getChannelDifference` — the operator's
 * "requesting for live missing things" call. The backfill path was fetching
 * updates and losing them, which is a live-traffic integrity gap, not a test
 * detail.
 *
 * The cause was a three-name constant, `UPDATE_CONTAINERS = ['updates',
 * 'updatesCombined', 'updatesTooLong']`, plus a hardcoded read of
 * `$payload['updates']`. `updates` and `updatesCombined` name their vector
 * `updates`; the whole difference family names its vector `other_updates`. The
 * constant named only the first two. See `UpdateIngestor::updateCarriers()`,
 * which is now derived from the parsed scheme instead.
 *
 * ## WHY THE FIX IS NOT A PARALLEL WRITER
 *
 * Each member is placed by `writeUpdate()` — the same method a bare
 * `updateNewMessage` uses, reached through the same `ingest()` entry point. The
 * members land with their OWN constructors on distinct `(seq, position)` keys,
 * so `updateDeleteMessages` in second position is not collapsed onto its
 * neighbour. There is no second code path that could drift from the first.
 *
 * ## THE CONTROL HALF, AND WHY IT IS NOT VACUOUS
 *
 * `test_an_empty_vector_places_nothing_and_says_so` ingests an `updates`
 * payload whose vector is empty and asserts NO row lands — the same carrier,
 * the same code path, zero members. Read alone that assertion would pass even if
 * the unwrap were deleted outright, because an empty vector has nothing to
 * place. So it is paired with
 * `test_a_two_member_payload_places_both_under_their_own_constructors()` on the
 * SAME payload shape with the vector populated: if the unwrap were removed, the
 * populated case goes RED while the empty case stays green, and the pair is a
 * real statement about the unwrap rather than about emptiness.
 *
 * ## THE SIX ARE STILL COUNTED AND REPORTED
 *
 * `test_the_routing_list_and_the_classification_agree()` reads the classified
 * `rpc-result` set out of `EndpointCoverage::classify()` and the routing set out
 * of `UpdateIngestor`, and asserts they are the same set. That is what stops the
 * new kind from being a quiet deletion: an endpoint that owes no row and is not
 * classified would be invisible to every gate, and the coverage document's
 * per-kind table would silently shrink.
 *
 * ## NO CREDENTIALS
 *
 * Every value is synthetic. No session string, no real `access_hash`, no
 * database password.
 */
final class RpcResultsPlaceNoFakeUpdateRowTest extends IngestTestCase
{
    use HasEndpointFixtures;

    /** Tenant for the two-member payload. Distinct per method so rows never mix. */
    private const ACCOUNT_TWO = 7301;

    /** Tenant for the empty-vector control. */
    private const ACCOUNT_EMPTY = 7302;

    /** Tenant for the classification/routing agreement check (no DB use). */
    private const ACCOUNT_SURVEY = 7303;

    /**
     * The gate itself: an `updates` payload carrying TWO distinct updates must
     * place BOTH, each under its own constructor, and must not mint a row for
     * the container.
     *
     * TWO, not one, because a one-member vector cannot distinguish "the unwrap
     * walked the vector" from "the unwrap placed element 0 and stopped" — the
     * exact defect `writeUpdateContainer()` already carries a comment about, where
     * `$first ??= $this->writeUpdate(...)` short-circuited the CALL and a
     * container silently stored only its first member. A second member is what
     * makes that visible.
     */
    public function test_a_two_member_payload_places_both_under_their_own_constructors(): void
    {
        $payload = [
            '_' => 'updates',
            'updates' => [
                [
                    '_' => 'updateUserStatus',
                    'user_id' => 515099001,
                    'status' => ['_' => 'userStatusOnline', 'expires' => 1726000100],
                ],
                [
                    '_' => 'updateReadHistoryInbox',
                    'folder_id' => 1,
                    'peer' => ['_' => 'peerUser', 'user_id' => 515099002],
                    'max_id' => 770002,
                    'still_unread_count' => 3,
                    'pts' => 909,
                    'pts_count' => 1,
                ],
            ],
            'users' => [],
            'chats' => [],
            'date' => 1726000000,
            'seq' => 771,
        ];

        $this->seedTenant(self::ACCOUNT_TWO);

        $root = (new UpdateIngestor)->ingest($payload, self::ACCOUNT_TWO);

        self::assertNotNull(
            $root,
            'a two-member `updates` container returned no stored root, so neither member reached storage',
        );

        foreach (['updateUserStatus', 'updateReadHistoryInbox'] as $member) {
            self::assertTrue(
                $this->hasRow('tf_updates', ['account_id' => self::ACCOUNT_TWO, 'constructor' => $member]),
                "the container carried {$member} and no tf_updates row carries it. A container that "
                .'places only its first member is the defect this test exists to catch.',
            );
        }

        self::assertSame(
            2,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT_TWO)->count(),
            'exactly the two member updates must land. A third row means the carrier minted one for '
            .'itself: `updates` is an RPC result, not an update, so a row naming it records a thing '
            .'that never happened.',
        );

        self::assertFalse(
            $this->hasRow('tf_updates', ['account_id' => self::ACCOUNT_TWO, 'constructor' => 'updates']),
            'a tf_updates row names `updates` itself. The carrier is decomposed into its members; it '
            .'must not be stored as though it were an update.',
        );

        // Distinct keys, not two members collapsed onto one row.
        $keys = DB::table('tf_updates')
            ->where('account_id', self::ACCOUNT_TWO)
            ->orderBy('position')
            ->get(['seq', 'position']);
        self::assertCount(2, $keys);
        self::assertSame(771, (int) $keys[0]->seq, 'the container\'s delivering seq must key its members');
        self::assertSame(0, (int) $keys[0]->position);
        self::assertSame(1, (int) $keys[1]->position, 'the second member must get its own position, not share slot 0');
    }

    /**
     * THE CONTROL HALF. The same carrier with an EMPTY vector places nothing,
     * and does so without raising.
     *
     * Vacuous ALONE — an empty vector has nothing to unwrap, so this would pass
     * with the unwrap deleted. It is the control for the test above, not a
     * gate: if the unwrap is removed, the populated case fails and this one
     * still passes, which is exactly the discrimination that makes the pair
     * informative. So this method also asserts the account holds ZERO rows, not
     * merely "not the populated number".
     */
    public function test_an_empty_vector_places_nothing_and_says_so(): void
    {
        $payload = [
            '_' => 'updates',
            'updates' => [],
            'users' => [],
            'chats' => [],
            'date' => 1726000000,
            'seq' => 772,
        ];

        $this->seedTenant(self::ACCOUNT_EMPTY);

        $root = (new UpdateIngestor)->ingest($payload, self::ACCOUNT_EMPTY);

        self::assertNull(
            $root,
            'an `updates` container with an empty vector has no member to store, so it must return a null '
            .'root rather than inventing one',
        );
        self::assertSame(
            0,
            DB::table('tf_updates')->where('account_id', self::ACCOUNT_EMPTY)->count(),
            'an empty carrier placed rows. An RPC result carrying no update owes no row, and a carrier '
            .'that placed nothing must place nothing at all.',
        );
    }

    /**
     * THE SIX ARE STILL COUNTED, and the routing agrees with the classification.
     *
     * This is the anti-deletion half. Reclassifying ten constructors as
     * `rpc-result` removes a `tf_updates` row obligation from each, and an
     * obligation nothing asserts is an endpoint nothing notices — so the two
     * sides are read independently and compared. `EndpointCoverage` derives its
     * set from the parsed scheme's result types; `UpdateIngestor` holds a
     * routing list. If either drifts, this fails.
     */
    public function test_the_routing_list_and_the_classification_agree(): void
    {
        $classified = [];
        foreach (self::classifiedEndpoints() as $e) {
            if ($e['kind'] === EndpointCoverage::KIND_RPC_RESULT) {
                $classified[] = $e['ctor'];
            }
        }

        $routing = (new \ReflectionClass(UpdateIngestor::class))->getConstant('RPC_RESULT_CTORS');
        self::assertIsArray($routing, 'UpdateIngestor::RPC_RESULT_CTORS must stay a readable list');
        sort($routing);
        sort($classified);

        self::assertSame(
            $classified,
            $routing,
            'the ingest routing list and the `rpc-result` classification name different endpoints. '
            .'One side routes them to the no-row path and the other does not, so an endpoint can owe no '
            .'row on one side and mint one on the other. Both must be derived from the same fact.',
        );

        self::assertNotEmpty(
            $classified,
            'no endpoint is classified rpc-result, so this gate would compare two empty sets and pass '
            .'without measuring anything. Re-derive the classification before trusting a green run.',
        );
    }

    /**
     * Every `rpc-result` endpoint is still COUNTED, and its reason names why it
     * is not an update.
     *
     * The count is the claim; this is the receipt. A kind that merely EXCLUDED
     * these constructors would satisfy every other gate in the suite while
     * quietly deleting ten endpoints from the coverage claim, which is the one
     * outcome this lane exists to prevent.
     */
    public function test_every_rpc_result_is_classified_with_a_reason_naming_its_result_type(): void
    {
        $rows = [];
        foreach (self::classifiedEndpoints() as $e) {
            if ($e['kind'] !== EndpointCoverage::KIND_RPC_RESULT) {
                continue;
            }

            self::assertSame(
                [],
                $e['tables'],
                "{$e['ctor']} is classified rpc-result, so it must name no storage table — a carrier of "
                .'nothing places nothing.',
            );
            self::assertStringContainsString(
                'not an update endpoint',
                $e['reason'],
                "{$e['ctor']} owes no update row, so its reason must say WHY it is not an update "
                .'endpoint; otherwise a reader cannot tell a real classification from a gap.',
            );

            $rows[] = $e['ctor'];
        }

        sort($rows);

        self::assertNotEmpty($rows, 'no endpoint is classified rpc-result; this gate is measuring nothing');

        // MEASURED 2026-10-05 with
        //   php bin/generate-endpoint-coverage.php
        // which walks the same scheme and catalog this gate reads through
        // HasEndpointFixtures::classifiedEndpoints():
        //   update-carrier: 6   rpc-result: 6   root-fact: 0
        //   nested-source: 0    log-only: 0     unmodelled: 163
        //   universe (parsed update* ctors): 175
        //
        // Six rpc-results: updates.state, updates.differenceEmpty,
        // updates.differenceTooLong, updates.channelDifferenceEmpty,
        // updates.channelDifferenceTooLong, updatesTooLong.
        //
        // An EARLIER draft of this file asserted 10 and was WRONG: it counted
        // the four `Updates`-union short-poll forms. `updateShort` carries a
        // singular `update:Update`, so it is a CARRIER (now unwrapped like
        // `updates`), and `updateShortMessage` / `updateShortChatMessage` /
        // `updateShortSentMessage` declare `pts_count` — the update watermark no
        // state or error result carries — so they remain update endpoints and
        // keep the row they place today. Reclassifying either would have removed
        // a working obligation, which is the failure this lane must not cause.
        self::assertCount(6, $rows, 'the rpc-result population changed; re-derive it rather than editing this number');
    }

    /**
     * An `rpc-result` is REACHABLE and does not raise — the classification must
     * not become a way to make an endpoint un-ingestible.
     *
     * Without this, "no row owed" could be satisfied by a route that returns
     * before the transaction, leaving the endpoint neither stored nor reported.
     */
    public function test_an_rpc_result_ingests_without_raising(): void
    {
        $fixtures = self::endpointFixtures();
        $this->seedTenant(self::ACCOUNT_SURVEY);

        $target = null;
        foreach (self::classifiedEndpoints() as $e) {
            if ($e['kind'] === EndpointCoverage::KIND_RPC_RESULT && isset($fixtures[$e['ctor']])) {
                $target = $e['ctor'];

                break;
            }
        }

        self::assertNotNull(
            $target,
            'no rpc-result endpoint has a fixture, so this gate would prove nothing about reachability',
        );

        $root = (new UpdateIngestor)->ingest($fixtures[$target], self::ACCOUNT_SURVEY);

        self::assertNull(
            $root,
            "{$target} is an rpc-result and owes no update row, so it must return a null root rather "
            .'than storing one',
        );
    }

    /**
     * @param  array<string, mixed>  $where
     */
    private function hasRow(string $table, array $where): bool
    {
        return DB::table($table)->where($where)->exists();
    }

    private function seedTenant(int $accountId): void
    {
        DB::table('telegram_accounts')->insert([
            'user_id' => $accountId,
            'label' => 'rpc-unwrap-'.$accountId,
            'type' => 'user',
            'dc_id' => 2,
        ]);
    }
}
