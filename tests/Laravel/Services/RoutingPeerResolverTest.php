<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Services\RoutingPeerResolver;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * Behaviour→core link — the verbatim's "link their data to the nf 5 core"
 * seam, tested against the migrated mirror.
 *
 * Owner verbatim 2026-09-14: "the core is the telegram nf5 databae the
 * rest is going to only make new tables and link their data to the nf 5
 * core then we can identify the exact set of data and how we should treat
 * them just becuase they are connectd to  the facts that telegram is
 * giving us".
 *
 * A routing rule (behaviour table, 'tg' prefix) stores a peer as
 * (peer_type, peer_id); the resolver links it back to the NF5 core fact —
 * tf_users for peer_type 1, tf_chats for 2/3 — so the app can identify
 * the exact set of data behind a routing decision. A rule whose peer has
 * no fact yet resolves to null (settings may exist before the fact; the
 * mirror creates the row on first update).
 */
final class RoutingPeerResolverTest extends TestbenchTestCase
{
    /**
     * The tenant these fixtures write under: [42].
     *
     * MEASURED, not assumed: the only account_id literal in this file.
     *
     * This class opts into foreign-key enforcement itself
     * ('foreign_key_constraints' => true) and every mirror table carries
     * account_id -> telegram_accounts, so each insert was refused AT THE
     * DATABASE:
     *
     *   SQLSTATE[23000]: 19 FOREIGN KEY constraint failed
     *   insert into "tf_users" ("account_id", "id", "constructor") values (42, 101, user)
     *
     * It cannot extend IngestTestCase (Orchestra TestbenchTestCase, and the
     * mirror surface is migrated per-test here), so it plants its own tenant
     * in setUp(). Synthetic values only — no session string, no access hash.
     */
    protected function seedTenantRow(): void
    {
        foreach ([42] as $id) {
            if (DB::table('telegram_accounts')->where('user_id', $id)->exists()) {
                continue;
            }

            DB::table('telegram_accounts')->insert([
                'user_id' => $id,
                'label' => 'laravel-fixture-'.$id,
                'type' => 'user',
                'dc_id' => 2,
            ]);
        }
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 3);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function migrateMirror(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);
    }

    /**
     * Migrate, THEN plant the tenant.
     *
     * Order matters and is not cosmetic: `telegram_accounts` is itself one of the
     * migrated tables, so seeding before the migration would insert into nothing —
     * the same constraint `IngestTestCase::setUp()` states and obeys.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateMirror();
        $this->seedTenantRow();
    }

    public function test_user_rule_resolves_to_tf_users_core_fact(): void
    {
        $this->migrateMirror();

        DB::table('tf_users')->insert([
            'account_id' => 42, 'id' => 101, 'constructor' => 'user', 'verified' => true,
        ]);
        $rule = new UpdateRoutingRule([
            'account_id' => 42, 'peer_type' => 1, 'peer_id' => 101, 'mode' => 'store_only',
        ]);
        $resolver = new RoutingPeerResolver(DB::connection());

        $fact = $resolver->resolve($rule);

        self::assertNotNull($fact, 'user rule links to the tf_users core fact');
        self::assertSame(101, (int) $fact['id']);
    }

    public function test_channel_rule_resolves_to_tf_chats_core_fact(): void
    {
        $this->migrateMirror();

        DB::table('tf_chats')->insert([
            'account_id' => 42, 'id' => 900, 'constructor' => 'channel', 'title' => 'notify log',
            'participants_count' => 3, 'date' => 1726000000, 'version' => 1,
        ]);
        $rule = new UpdateRoutingRule([
            'account_id' => 42, 'peer_type' => 3, 'peer_id' => 900, 'mode' => 'store_only',
        ]);
        $resolver = new RoutingPeerResolver(DB::connection());

        $fact = $resolver->resolve($rule);

        self::assertNotNull($fact, 'channel rule links to the tf_chats core fact');
        self::assertSame('notify log', (string) $fact['title']);
    }

    public function test_rule_without_core_fact_yet_resolves_to_null(): void
    {
        $this->migrateMirror();

        $rule = new UpdateRoutingRule([
            'account_id' => 42, 'peer_type' => 3, 'peer_id' => 555, 'mode' => 'act_on',
        ]);
        $resolver = new RoutingPeerResolver(DB::connection());

        self::assertNull(
            $resolver->resolve($rule),
            'settings may precede the fact — resolve null, never throw',
        );
    }

    public function test_unknown_peer_type_resolves_to_null(): void
    {
        $this->migrateMirror();

        $rule = new UpdateRoutingRule([
            'account_id' => 42, 'peer_type' => 4, 'peer_id' => 1, 'mode' => 'act_on',
        ]);
        $resolver = new RoutingPeerResolver(DB::connection());

        self::assertNull($resolver->resolve($rule), 'no core table for an unknown peer type');
    }
}
