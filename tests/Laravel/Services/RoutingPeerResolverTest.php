<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Services;

use Illuminate\Support\Facades\DB;
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

    private function migrateMirror(): string
    {
        $out = sys_get_temp_dir().'/tf5r_'.uniqid();
        @mkdir($out.'/migrations/mirror', 0777, true);

        $this->artisan('teleframe:mirror', ['--stage' => '0', '--out' => $out])->assertExitCode(0);
        $this->artisan('migrate', ['--path' => $out.'/migrations/mirror', '--realpath' => true])->assertExitCode(0);

        return $out;
    }

    public function test_user_rule_resolves_to_tf_users_core_fact(): void
    {
        $out = $this->migrateMirror();
        try {
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
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_channel_rule_resolves_to_tf_chats_core_fact(): void
    {
        $out = $this->migrateMirror();
        try {
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
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_rule_without_core_fact_yet_resolves_to_null(): void
    {
        $out = $this->migrateMirror();
        try {
            $rule = new UpdateRoutingRule([
                'account_id' => 42, 'peer_type' => 3, 'peer_id' => 555, 'mode' => 'act_on',
            ]);
            $resolver = new RoutingPeerResolver(DB::connection());

            self::assertNull(
                $resolver->resolve($rule),
                'settings may precede the fact — resolve null, never throw',
            );
        } finally {
            $this->rrmdir($out);
        }
    }

    public function test_unknown_peer_type_resolves_to_null(): void
    {
        $out = $this->migrateMirror();
        try {
            $rule = new UpdateRoutingRule([
                'account_id' => 42, 'peer_type' => 4, 'peer_id' => 1, 'mode' => 'act_on',
            ]);
            $resolver = new RoutingPeerResolver(DB::connection());

            self::assertNull($resolver->resolve($rule), 'no core table for an unknown peer type');
        } finally {
            $this->rrmdir($out);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
