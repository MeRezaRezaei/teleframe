<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PinsDeclaredForeignKeyLegs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfBusinessChatLinks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfMessagesEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Tests\Ingest\IngestTestCase;

/**
 * THE CROSS-TENANT LEAK IN THE `@generated` ELOQUENT LAYER, reproduced with TWO
 * tenants and DELIBERATELY COLLIDING KEYS — and with a CONTROL half that proves
 * the fixture is capable of exposing it at all.
 *
 * ## WHY THIS LAYER NEEDED ITS OWN PROOF
 *
 * The hand-written mirror layer received the tenant pin and a passing two-tenant
 * fixture in `tests/Mirror/TenantBoundaryOnMessageRelationsTest.php`. That fix
 * does not reach here: every model in `src/Schema/Generated/Models/` extends
 * either `TfMirrorModel` (254 of them) or `TlAnchorModel` (11), and neither of
 * those bases constrained a relation's tenant leg. Measured on this layer before
 * the fix, with this repo's own gate predicate:
 *
 *  - 422 relations exist (211 `hasMany` + 211 `belongsTo`), all single-column;
 *  - **314 of the 422 constrained no `account_id`** at all;
 *  - `account_id` appeared in 264 relation-bearing files only as a `$casts`
 *    entry or a docblock line, and in **zero** relation bodies
 *    (`grep -rh 'account_id' src/Schema/Generated/Models/ | grep -cE 'return \$this->(hasMany|belongsTo|where)'` → 0).
 *
 * The other 108 were the `hasMany` built by the 11 `Tl*` anchors, which carry
 * their own base; they are why the number is 314 and not 422.
 *
 * ## WHY A SINGLE-TENANT FIXTURE IS BLIND TO THIS, STATED PLAINLY
 *
 * With ONE tenant, `account_id = X` is implied by `account_id = X`: a relation
 * that omits the tenant leg returns exactly the rows it would return if it
 * constrained it. Every assertion about a relation's SHAPE — which leg names it
 * pins — passes on a relation that pins the wrong legs, because three is what
 * the code pinned. The bug is a FUNCTION OF HAVING TWO TENANTS, so this fixture
 * plants two and collides their keys deliberately.
 *
 * ## THE CONTROL IS THE POINT OF THE FIRST TEST
 *
 * `test_the_control_half_the_single_leg_key_really_does_collide_across_tenants`
 * queries the child table on EXACTLY the key the generated relation is built on
 * — the bare non-tenant leg, no `account_id` — and requires it to return rows
 * from BOTH tenants. If that test fails, every other test in this file is
 * vacuous: there would be nothing to leak, and the assertions below would pass
 * on a relation that is wrong in the same way the fixture is not.
 *
 * This is not a contrivance. The keys collide in production because Telegram
 * numbers messages PER CHAT, so two accounts genuinely hold overlapping
 * dialog/id spaces, and `link` is a Telegram-supplied invite string two accounts
 * can both hold.
 *
 * ## WHY THE ROWS PLANTED HERE ARE REQUIRED, NOT FABRICATED
 *
 * Foreign key enforcement is ON for this connection (see `RunsMigrations`, and
 * `tests/Ingest/IngestFkEnforcementIsOnTest.php`), so every parent row exists
 * only because the enforced constraints demand it: both `telegram_accounts`
 * tenants, and one `tf_messages` row PER TENANT because the entity FK cascades
 * on delete and would refuse an orphan. The two tenants' rows differ ONLY in
 * `account_id` and in one payload column, which is what makes a leak legible in
 * the returned model rather than merely in a row count.
 *
 * ## THE PIN IS ON THE BASE CLASS, SO REGENERATION CANNOT REMOVE IT
 *
 * `teleframe:regenerate` deletes `src/Schema/Generated/Models` wholesale. The
 * guarantee therefore lives on `TfMirrorModel` / `TlAnchorModel` in
 * `src/Schema/Eloquent/`, which the generator's only obligation is to keep
 * emitting `extends` — see D-028.
 *
 * @see PinsDeclaredForeignKeyLegs
 */
final class TenantBoundaryOnGeneratedModelRelationsTest extends IngestTestCase
{
    /** Tenant A — a synthetic TELEGRAM user id (never a real one). */
    private const TENANT_A = 900000001;

    /** Tenant B. */
    private const TENANT_B = 900000002;

    /** The peer both tenants share: a group chat. */
    private const PEER_TYPE = 2;

    private const PEER_ID = -1001234567890;

    /**
     * The message id both tenants share.
     *
     * `id=5` exists in essentially every dialog of a real account, so two
     * accounts holding it is the NORMAL case, not an edge case.
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
            // Each tenant's own message text, so the FORWARD relation can be
            // checked by payload and not merely by account_id.
            'message' => 'tenant '.$tenant,
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
     * THE CONTROL — the fixture is capable of exposing the defect.
     *
     * This queries on EXACTLY the pair `ModelGenerator` emits into the relation
     * body: `hasMany(TfMessagesEntities::class, 'id', 'id')` — the bare non-tenant
     * leg, with no `account_id` anywhere. It MUST return rows from both tenants.
     *
     * If this assertion were to fail, there would be no collision, every
     * relation in this layer would be accidentally correct, and the assertions
     * below would be measuring nothing.
     */
    public function test_the_control_half_the_single_leg_key_really_does_collide_across_tenants(): void
    {
        // Re-read the same key the generated relation is built on, and nothing
        // else — deliberately reproducing the PRE-FIX query rather than the
        // scoped one, so this asserts the leak is expressible, not that it
        // happens.
        $tenants = DB::table('tf_messages_entities')
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->where('id', self::MESSAGE_ID)
            ->distinct()
            ->pluck('account_id')
            ->map(static fn ($v): int => (int) $v)
            ->sort()
            ->values()
            ->all();

        self::assertSame(
            [self::TENANT_A, self::TENANT_B],
            $tenants,
            'THE CONTROL FAILED: the two tenants\' rows are NOT separated by the bare non-tenant key. If they '
            .'were, this fixture could not distinguish a tenant-scoped relation from an unscoped one, and every '
            .'other test in this file would pass vacuously.',
        );
    }

    /**
     * THE HEADLINE. Tenant A's message must reach ONLY tenant A's entity, through
     * the generated layer's own `hasMany`.
     */
    public function test_a_generated_message_reaches_only_its_own_tenants_entities(): void
    {
        $message = $this->loadMessageFor(self::TENANT_A);

        $entities = $message->messagesEntities()->get();

        self::assertCount(
            1,
            $entities,
            'A message under tenant '.self::TENANT_A.' must reach exactly its OWN entity rows through the '
            .'generated layer. Returning more than one means the relation never constrained `account_id`, and the '
            .'surplus rows belong to another tenant: the declared FK is '
            .'(account_id, peer_type, peer_id, id), so an unconstrained tenant leg reads ACROSS the tenant boundary.',
        );

        self::assertSame(
            self::TENANT_A,
            (int) $entities->first()->account_id,
            'the single entity row must be tenant A\'s own, read back from the relation.',
        );
    }

    /**
     * The payload check, so the count assertion above cannot be satisfied by a
     * row count alone: the row returned must be A's row, not merely A-shaped.
     *
     * ## WHY THIS LOOPS BOTH TENANTS TOO
     *
     * Checked for tenant A alone, this PASSED against the broken code: with the
     * tenant leg dropped the relation returned both rows, and `first()` handed
     * back whichever was inserted first — tenant A's. Checking each tenant's own
     * payload removes the luck, because without the leg both traversals return
     * the SAME first row and the second tenant's payload can never match.
     */
    public function test_the_returned_entity_is_the_tenants_own_payload(): void
    {
        foreach ([self::TENANT_A, self::TENANT_B] as $tenant) {
            $entity = $this->loadMessageFor($tenant)->messagesEntities()->firstOrFail();

            self::assertSame(
                $tenant === self::TENANT_A ? 3 : 7,
                (int) $entity->length,
                'each tenant\'s own row carries its own payload: A bolds 3 chars, B bolds 7. A length belonging to '
                .'the other tenant means the relation read across the boundary.',
            );

            self::assertSame($tenant === self::TENANT_A ? 0 : 100, (int) $entity->offset);
        }
    }

    /**
     * The FORWARD side — `belongsTo` — which pins legs on the PARENT table and
     * reads its values off the child row.
     *
     * ## WHY THIS LOOPS BOTH TENANTS INSTEAD OF CHECKING ONE
     *
     * The first version of this test loaded only tenant A's entity and asserted
     * the parent came back as tenant A. It PASSED against the broken code, and
     * for a reason worth recording: an un-pinned `belongsTo` is
     * `where tf_messages.id = 5`, which matches both tenants' rows, and the
     * property accessor returns the first match — which was tenant A's purely
     * because that row was inserted first. An assertion that survives the defect
     * it exists to detect is not evidence.
     *
     * Traversing BOTH tenants removes the luck. Both must resolve to their OWN
     * row, which is only possible if each lookup carries `account_id`; with the
     * leg dropped, both traversals return the SAME row and the second fails,
     * whichever tenant happens to sort first.
     */
    public function test_the_forward_belongs_to_resolves_within_the_tenant(): void
    {
        foreach ([self::TENANT_A, self::TENANT_B] as $tenant) {
            $entity = TfMessagesEntities::query()
                ->where('account_id', $tenant)
                ->where('peer_type', self::PEER_TYPE)
                ->where('peer_id', self::PEER_ID)
                ->where('id', self::MESSAGE_ID)
                ->firstOrFail();

            $parent = $entity->messages;

            self::assertNotNull($parent, 'the forward relation must resolve at all');

            self::assertSame(
                $tenant,
                (int) $parent->account_id,
                'a belongsTo keyed on `id` alone matches BOTH tenants\' messages, because `id` is total only within '
                .'an account. Without the tenant leg this traversal returns whichever row sorts first — the same '
                .'one for every tenant.',
            );

            // The payload, so "resolved to its own row" cannot be satisfied by a
            // row that merely happens to carry the right account_id.
            self::assertSame(
                'tenant '.$tenant,
                (string) $parent->message,
                'the resolved message must be this tenant\'s own message payload.',
            );
        }
    }

    /**
     * A DIFFERENT TABLE and a DIFFERENT KEY SHAPE — `(account_id, link)`, where
     * the joinable leg is a Telegram-supplied invite STRING two accounts can both
     * hold. Proof the guarantee is not one table's patch.
     */
    public function test_a_second_table_family_also_holds_the_boundary(): void
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

        $rows = TfBusinessChatLinks::query()
            ->where('account_id', self::TENANT_A)
            ->where('link', $link)
            ->firstOrFail()
            ->businessChatLinksEntities()
            ->get();

        self::assertCount(1, $rows, 'a business chat link under one tenant must not reach another tenant\'s entities for the same link string');
        self::assertSame(self::TENANT_A, (int) $rows->first()->account_id);
        self::assertSame(3, (int) $rows->first()->length, 'and it must be this tenant\'s row, not the other one');
    }

    /**
     * The DEFAULT state must be safe, and that is the whole point.
     *
     * `AccountContext` is reset first, so nothing filters the ROOT query but an
     * explicit `where`, and this repo's `AccountScope` is inert by default (it is
     * a no-op unless a context is active, and nothing in `src/` ever sets one —
     * D-027). So the tenant boundary below comes ONLY from the relation. Were it
     * to come from a global scope, this test would pass for the wrong reason and
     * the next caller to omit the context would leak.
     */
    public function test_the_leak_needs_no_account_context_to_be_reachable(): void
    {
        AccountContext::reset();

        $message = TlMessage::query()
            ->where('account_id', self::TENANT_A)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->where('id', self::MESSAGE_ID)
            ->firstOrFail();

        $tenants = $message->messagesEntities()->get()
            ->map(static fn (TfMessagesEntities $e): int => (int) $e->account_id)
            ->all();

        self::assertSame(
            [self::TENANT_A],
            $tenants,
            'With no AccountContext set the tenant filter comes ONLY from the relation. If this assertion ever '
            .'starts failing because a context was set elsewhere in the suite, that is the leak being masked '
            .'rather than fixed — the relation itself must carry the leg.',
        );
    }

    private function loadMessageFor(int $tenant): TlMessage
    {
        return TlMessage::query()
            ->where('account_id', $tenant)
            ->where('peer_type', self::PEER_TYPE)
            ->where('peer_id', self::PEER_ID)
            ->where('id', self::MESSAGE_ID)
            ->firstOrFail();
    }
}
