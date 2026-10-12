<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesMessagePeerScope;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBusinessChatLink;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageEntity;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\RunsMigrations;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * THE CROSS-TENANT LEAK, reproduced with TWO tenants and DELIBERATELY COLLIDING
 * KEYS — the only fixture shape that can find it.
 *
 * ## WHY A SINGLE-TENANT FIXTURE IS BLIND TO THIS CLASS OF BUG, STATED PLAINLY
 *
 * Every gate written before this one asserted the RELATION'S SHAPE — which leg
 * names it pins — or the DDL. Both pass with a leaking relation:
 *
 *  - the DDL is CORRECT. `tf_messages_entities_message_fk` really does declare
 *    `(account_id, peer_type, peer_id, id)` → `tf_messages (account_id,
 *    peer_type, peer_id, id)`, with FK enforcement on. The constraint only
 *    governs INSERTS. It says nothing about what a SELECT returns, and this bug
 *    is entirely on the read side;
 *  - the shape assertions compared the pinned legs against the declared legs
 *    and were PASSING on a THREE-leg answer because three is what the code
 *    pinned — the assertion agreed with the defect;
 *  - and with ONE tenant seeded, the missing `account_id` leg is
 *    UNOBSERVABLE: with one tenant, `account_id = X` is implied by
 *    `account_id = X`, so a relation that omits the tenant leg returns exactly
 *    the rows it would return if it constrained it. The bug is a FUNCTION OF
 *    HAVING TWO TENANTS. A fixture that plants one tenant cannot distinguish a
 *    tenant-scoped relation from an unscoped one by any means other than
 *    reading the SQL, which no behavioural gate here was doing.
 *
 * So the collision IS the test. Two tenants are planted holding the SAME
 * `(peer_type, peer_id, id)` — which is not a contrivance, because Telegram
 * numbers messages PER CHAT and two accounts genuinely hold overlapping
 * dialog/id spaces — and tenant A's message is then traversed.
 *
 * ## THE FIXTURE IS NOT FABRICATED TO PASS — IT IS THE OPPOSITE
 *
 * Every row planted here is required by the enforced foreign keys: both
 * `telegram_accounts` parents, and one `tf_messages` parent PER TENANT, because
 * `tf_messages_entities_message_fk` cascades on delete and would refuse an
 * orphan entity. The two tenants' entity rows differ ONLY in `account_id` and
 * in a payload column (`offset`/`length`), which is what makes the leak
 * observable in the returned model rather than merely in the row count.
 *
 * ## WHY THE FIX IS IN THE RELATION AND NOT IN `AccountScoped` ALONE
 *
 * `AccountScoped::forAccount()` scopes the ROOT query only — it is a static
 * helper that builds `static::query()->where('account_id', …)`, and a relation
 * re-queries the CHILD from the parent's key. The tenant leg therefore has to
 * travel ON the relation, derived from the DECLARED foreign key (read from the
 * executed schema), so it cannot drift from the constraint it mirrors.
 *
 * @see ResolvesMessagePeerScope
 */
final class TenantBoundaryOnMessageRelationsTest extends IngestTestCase
{
    use RunsMigrations;

    /** Tenant A — a synthetic TELEGRAM user id (never a real one). */
    private const TENANT_A = 900000001;

    /** Tenant B. */
    private const TENANT_B = 900000002;

    /** The peer both tenants share: a group chat. */
    private const PEER_TYPE = 2;

    private const PEER_ID = -1001234567890;

    /**
     * The message id both tenants share. `id=5` exists in essentially every
     * dialog of a real account, so two accounts holding it is the NORMAL case,
     * not an edge case.
     */
    private const MESSAGE_ID = 5;

    /** @return list<int> */
    protected function ingestTenantIds(): array
    {
        return [self::TENANT_A, self::TENANT_B];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Both tenants get a message at the SAME (peer_type, peer_id, id), and
        // each tenant's message gets an entity row. The peer pair is identical
        // across tenants precisely so that the ONLY leg separating the two
        // entity rows is `account_id`.
        $this->plantMessageFor(self::TENANT_A);
        $this->plantMessageFor(self::TENANT_B);
    }

    private function plantMessageFor(int $tenant): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => $tenant,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => self::MESSAGE_ID,
            'constructor' => 'message',
        ]);

        DB::table('tf_messages_entities')->insert([
            'account_id' => $tenant,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => self::MESSAGE_ID,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            // The tenant's own payload: A bolds chars 0-3, B bolds chars 0-7.
            // Distinct values make the leak legible in the returned model.
            'offset' => $tenant === self::TENANT_A ? 0 : 100,
            'length' => $tenant === self::TENANT_A ? 3 : 7,
        ]);
    }

    /**
     * THE HEADLINE. Tenant A's message must return ONLY tenant A's entity.
     *
     * Before the fix this returned 2 rows, one of them tenant B's — the
     * reported "3 rows belonging to tenant 8" failure mode, reproduced with the
     * smallest fixture that can express it.
     */
    public function test_a_message_returns_only_its_own_tenants_entities(): void
    {
        $message = $this->loadMessageFor(self::TENANT_A);

        $entities = $message->entities()->get();

        self::assertCount(
            1,
            $entities,
            'A message under tenant '.self::TENANT_A.' must reach exactly its OWN entity rows. Returning more than one '
            .'means the relation never constrained `account_id`, and the surplus rows belong to another tenant: '
            .'the declared FK is (account_id, peer_type, peer_id, id), so an unconstrained tenant leg reads '
            .'ACROSS the tenant boundary.',
        );

        self::assertSame(
            self::TENANT_A,
            (int) $entities->first()->account_id,
            'the single entity row must be tenant A\'s own, read back from the relation.',
        );
    }

    /**
     * The payload check, so the assertion above cannot be satisfied by a row
     * count alone: the row returned must be A's row, not merely A-shaped.
     */
    public function test_the_returned_entity_is_the_tenants_own_payload(): void
    {
        $entity = $this->loadMessageFor(self::TENANT_A)->entities()->firstOrFail();

        self::assertSame(3, (int) $entity->length, 'tenant A bolds 3 chars; a row carrying length 7 is tenant B\'s');
        self::assertSame(0, (int) $entity->offset);
    }

    /**
     * The same claim on the 1:1 message children, which are the majority shape:
     * `views()` is a `hasOne`, so the failure is "returns the wrong row"
     * rather than "returns too many" — the more dangerous form, because it
     * returns a model and no exception.
     */
    public function test_a_one_to_one_message_child_resolves_within_the_tenant(): void
    {
        foreach ([self::TENANT_A, self::TENANT_B] as $tenant) {
            $this->plantViewsFor($tenant);
        }

        $views = $this->loadMessageFor(self::TENANT_A)->views;

        self::assertNotNull($views, 'the 1:1 child must resolve at all');
        self::assertSame(
            self::TENANT_A,
            (int) $views->account_id,
            'a hasOne that does not pin `account_id` does not return nothing — it returns WHICHEVER TENANT WROTE '
            .'LAST, which is a real model carrying another tenant\'s data.',
        );
    }

    /**
     * `TfBusinessChatLink::entities()` is the SAME structural class of defect on
     * a DIFFERENT table and a DIFFERENT key: the child is keyed
     * `(account_id, link)`, the relation carries only `link`, and `link` is a
     * Telegram-supplied invite string that two accounts can both hold.
     *
     * Asserted here because it is the proof that the fix is not a one-relation
     * patch: it is the same missing-tenant-leg shape in a model that does not
     * use `pinPeerScope` at all.
     */
    public function test_a_business_chat_link_reaches_only_its_own_tenants_entities(): void
    {
        $link = 'https://t.me/+LEAKCANARY0001';

        foreach ([self::TENANT_A, self::TENANT_B] as $tenant) {
            DB::table('tf_business_chat_links')->insert([
                'account_id' => $tenant,
                'link' => $link,
                'message' => 'tenant '.$tenant,
                'views' => 0,
            ]);
            DB::table('tf_business_chat_links_entities')->insert([
                'account_id' => $tenant,
                'link' => $link,
                'position' => 0,
                'constructor' => 'messageEntityBold',
                'offset' => $tenant === self::TENANT_A ? 0 : 100,
                'length' => $tenant === self::TENANT_A ? 3 : 7,
            ]);
        }

        $rows = TfBusinessChatLink::query()
            ->where('account_id', self::TENANT_A)
            ->where('link', $link)
            ->firstOrFail()
            ->entities()
            ->get();

        self::assertCount(1, $rows, 'a business chat link under one tenant must not reach another tenant\'s entities for the same link string');
        self::assertSame(self::TENANT_A, (int) $rows->first()->account_id);
        self::assertSame(3, (int) $rows->first()->length, 'and it must be this tenant\'s row, not the other one');
    }

    /**
     * The DEFAULT state must be safe, and that is the whole point: this repo's
     * `AccountScope` is a NO-OP when `AccountContext` has no current value, so
     * the unguarded read below is the shape a caller gets by default.
     */
    public function test_the_leak_needs_no_account_context_to_be_reachable(): void
    {
        AccountContext::reset();

        $message = TfMessage::query()
            ->where('account_id', self::TENANT_A)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->where('id', self::MESSAGE_ID)
            ->firstOrFail();

        $tenants = $message->entities()->get()->map(static fn (TfMessageEntity $e): int => (int) $e->account_id)->all();

        self::assertSame(
            [self::TENANT_A],
            $tenants,
            'With no AccountContext set the tenant filter comes ONLY from the relation. If this assertion ever '
            .'starts failing because a context was set elsewhere in the suite, that is the leak being masked rather '
            .'than fixed — the relation itself must carry the leg.',
        );
    }

    private function loadMessageFor(int $tenant): TfMessage
    {
        return TfMessage::query()
            ->where('account_id', $tenant)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->where('id', self::MESSAGE_ID)
            ->firstOrFail();
    }

    private function plantViewsFor(int $tenant): void
    {
        DB::table('tf_messages_views')->insert([
            'account_id' => $tenant,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => self::MESSAGE_ID,
            'views' => $tenant === self::TENANT_A ? 11 : 99,
        ]);
    }
}
