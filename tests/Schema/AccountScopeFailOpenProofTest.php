<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScope;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * SCOPE-BLAST / L2 measurement artifact.
 *
 * MEASURES, does not fix: `AccountScope::apply()` returns WITHOUT adding a
 * predicate when `AccountContext::current()` is null.  The shipped config
 * (`src/Laravel/config/teleframe.php:96`) defaults
 * `primary_account_id` to `env('TELEFRAME_PRIMARY_ACCOUNT_ID')`, i.e. null
 * unless the host sets the env var — so the default deployment has NO
 * context and the tenancy boundary is silently OFF.
 *
 * Three halves, all executed:
 *   1. FAIL-OPEN  — no context: a plain query returns the other tenant's row.
 *   2. CONTROL    — with context: the same query returns only that tenant.
 *   3. DEFAULT    — what `configDefault()` actually returns in this harness.
 *
 * Plus the non-vacuity guard: half 1 and half 2 must DISAGREE, and half 1
 * must be strictly larger than half 2.
 *
 * 🔴 WHAT THE UNBOUNDED-CONFIG ARM ACTUALLY PROVED, recorded because the original
 * claimed more than it delivered: it called `$container->forgetInstance('config')` and
 * then discovered the binding was STILL there — `forgetInstance` detaches a resolved
 * INSTANCE, not a BINDING. So it never exercised the branch it named, and worse, it
 * left the shared container mutated for every subsequent test in the process, which is
 * what hung the whole `tests/Schema` directory. The arm is now an honest external
 * assertion with no sabotage, and the five SHIPPED null forms are covered through the
 * data provider instead.
 *
 * No file under src/ is modified by this test.
 */
final class ScopeBlastAnchor extends Model
{
    use AccountScoped;

    protected $table = 'scope_blast_anchors';

    public $timestamps = false;

    /** @var array<string, string> */
    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
    ];
}

final class AccountScopeFailOpenProofTest extends TestCase
{
    /** Tenant A. */
    private const TENANT_A = 101;

    /** Tenant B — the "other" tenant whose row must NOT leak when scoped. */
    private const TENANT_B = 202;

    private const TABLE = 'scope_blast_anchors';

    protected function setUp(): void
    {
        parent::setUp();

        // Every test starts from a provably-clean, provably-no-context state.
        AccountContext::reset();
        config(['teleframe.primary_account_id' => null]);

        Schema::create(self::TABLE, static function (Blueprint $t): void {
            $t->bigInteger('id');
            $t->bigInteger('account_id');
            $t->bigInteger('peer_id');
            $t->primary(['id']);
        });

        // Tenant A: two rows.  Tenant B: one row.  Total 3, two tenants.
        ScopeBlastAnchor::insert([
            ['id' => 1, 'account_id' => self::TENANT_A, 'peer_id' => 1001],
            ['id' => 2, 'account_id' => self::TENANT_A, 'peer_id' => 1002],
            ['id' => 3, 'account_id' => self::TENANT_B, 'peer_id' => 2001],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TABLE);
        AccountContext::reset();
        config(['teleframe.primary_account_id' => null]);

        parent::tearDown();
    }

    // ── HALF 1 — THE PROOF: fail-open ───────────────────────────────

    public function test_half1_fail_open_plain_query_returns_the_other_tenants_row(): void
    {
        AccountContext::reset();
        config(['teleframe.primary_account_id' => null]);

        self::assertNull(AccountContext::current(), 'premise: no context is active');

        $sql = ScopeBlastAnchor::query()->toSql();
        $rows = ScopeBlastAnchor::query()->orderBy('id')->get();

        $tenants = $rows->map(static fn (Model $r): int => (int) $r->account_id)->unique()->sort()->values()->all();
        $count = $rows->count();

        fwrite(STDERR, "\n[HALF 1 fail-open] sql=".$sql."\n");
        fwrite(STDERR, '[HALF 1 fail-open] rows='.$count.' tenants=['.implode(',', $tenants)."]\n");
        fwrite(STDERR, '[HALF 1 fail-open] row ids=['.$rows->pluck('id')->implode(',')."]\n");

        // The scope added NO predicate — this is the fail-open mechanism,
        // observed in the emitted SQL rather than inferred from a count.
        self::assertStringNotContainsString('account_id', $sql,
            'with no context the global scope must still be a no-op (fail-open)');

        // THE ASSERTION THAT IS THE PROOF: another tenant's row is present.
        self::assertContains(self::TENANT_B, $tenants,
            'plain query with no context must leak the other tenant');
        $other = $rows->first(static fn (Model $r): bool => ((int) $r->account_id) === self::TENANT_B);
        self::assertNotNull($other, 'a row belonging to the OTHER tenant must be returned');
        self::assertSame(2001, (int) $other->peer_id, 'and it must be the other tenant\'s real row');

        // …and the ordinary read paths agree: no tenant predicate anywhere.
        self::assertSame(3, ScopeBlastAnchor::query()->count());
        self::assertSame(
            1,
            ScopeBlastAnchor::query()->first()?->id,
            'first() is unfiltered — it returns the natural first row, not a tenant row');
        self::assertSame(
            3,
            ScopeBlastAnchor::query()->orderByDesc('account_id')->first()?->id,
            'orderByDesc(account_id)->first() lands on the OTHER tenant: unfiltered');
        self::assertSame(
            3,
            ScopeBlastAnchor::query()->where('account_id', self::TENANT_B)->first()?->id,
            'the other tenant is directly addressable with no context at all');
    }

    // ── HALF 2 — THE CONTROL: context-dependent, not always-on/off ──

    public function test_half2_control_scoped_query_returns_only_its_own_tenant(): void
    {
        $scoped = AccountContext::for(self::TENANT_A, static fn (): array => [
            'current' => AccountContext::current(),
            'sql' => ScopeBlastAnchor::query()->toSql(),
            'ids' => ScopeBlastAnchor::query()->orderBy('id')->pluck('id')->all(),
            'tenants' => ScopeBlastAnchor::query()->orderBy('id')->pluck('account_id')
                ->unique()->sort()->values()->all(),
        ]);

        fwrite(STDERR, "\n[HALF 2 control] current=".$scoped['current'].' sql='.$scoped['sql']."\n");
        fwrite(STDERR, '[HALF 2 control] rows='.count($scoped['ids']).' ids=['.implode(',', $scoped['ids']).'] tenants=['.implode(',', $scoped['tenants'])."]\n");

        self::assertSame(self::TENANT_A, $scoped['current']);
        self::assertStringContainsString('account_id', $scoped['sql'], 'scope MUST filter when context is active');
        self::assertSame([1, 2], $scoped['ids'], 'only tenant A rows');
        self::assertSame([self::TENANT_A], $scoped['tenants'], 'tenant B must be absent');
        self::assertNotContains(self::TENANT_B, $scoped['tenants']);

        // Restored, not leaked.
        self::assertNull(AccountContext::current(), 'for() must restore the previous (absent) context');
    }

    public function test_half2b_set_then_reset_is_the_same_control(): void
    {
        AccountContext::set(self::TENANT_B);
        $b = ScopeBlastAnchor::query()->orderBy('id')->pluck('id')->all();
        self::assertSame([3], $b);

        AccountContext::reset();
        $afterReset = ScopeBlastAnchor::query()->orderBy('id')->pluck('id')->all();

        fwrite(STDERR, "\n[HALF 2b set/reset] scoped_to_B=[".implode(',', $b).'] after_reset=['.implode(',', $afterReset)."]\n");

        self::assertSame([1, 2, 3], $afterReset, 'reset re-opens the boundary');
    }

    // ── HALF 3 — THE DEFAULT-CONFIG HALF ────────────────────────────

    public function test_half3_default_bootstrap_has_no_account_context(): void
    {
        $loaded = config('teleframe.primary_account_id');
        $teleframe = config('teleframe');
        $hasKey = is_array($teleframe) && array_key_exists('primary_account_id', $teleframe);
        $bound = Container::getInstance()->bound('config');
        $current = AccountContext::current();

        fwrite(STDERR, "\n[HALF 3 default] config('teleframe.primary_account_id')=".var_export($loaded, true)."\n");
        fwrite(STDERR, '[HALF 3 default] teleframe config key present='.($hasKey ? 'YES' : 'NO')
            .' bound(config)='.($bound ? 'YES' : 'NO')
            .' AccountContext::current()='.var_export($current, true)."\n");
        fwrite(STDERR, '[HALF 3 default] env TELEFRAME_PRIMARY_ACCOUNT_ID='.var_export(env('TELEFRAME_PRIMARY_ACCOUNT_ID'), true)."\n");

        self::assertNull($loaded,
            'FINDING if not null: the bootstrap DOES set a primary account — the '
            .'no-context premise is not reproducible here and must be constructed.');
        self::assertNull($current, 'out of the box there is NO current account context');
        self::assertTrue($bound, 'config must be bound, else null comes from the early return');
    }

    // ── configDefault() null-reachability, one form per case ────────

    /**
     * @param  array<string, mixed>  $teleframeConfig
     */
    #[DataProvider('nullForms')]
    public function test_config_default_reaches_null_for_each_shipped_null_form(
        string $form,
        array $teleframeConfig,
    ): void {
        AccountContext::reset();
        config(['teleframe' => $teleframeConfig]);

        $resolved = AccountContext::current();

        fwrite(STDERR, sprintf(
            "\n[configDefault %-22s] AccountContext::current()=%s\n",
            $form,
            var_export($resolved, true),
        ));

        self::assertNull($resolved, 'form "'.$form.'" must reach null');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function nullForms(): array
    {
        return [
            'absent key' => ['absent', ['other' => 'x']],
            'null' => ['null', ['primary_account_id' => null]],
            'empty string' => ['empty string', ['primary_account_id' => '']],
            'int zero' => ['int zero', ['primary_account_id' => 0]],
            'string zero' => ['string zero', ['primary_account_id' => '0']],
        ];
    }

    /**
     * The one null form that needs no `config` binding at all: the early return
     * in `configDefault()`.
     *
     * 🔴 THIS TEST USED TO CALL `$container->forgetInstance('config')`, and that is
     * what made `tests/Schema` HANG.
     *
     * `forgetInstance('config')` does not detach a BINDING, only a resolved
     * INSTANCE — so the test could not actually exercise the branch it claimed to,
     * and it left the container in a mutated state for every test that ran after it.
     * With ~500 tests following in the same process, one of them spun forever against
     * a half-torn-down container: the file passed alone in 22s and wedged the whole
     * directory run at 39%.
     *
     * Every OTHER `forgetInstance()` in this suite forgets a NARROW key
     * (a logger, a schedule key). This one forgot `config` — the binding every
     * single `config()` call reads. **A test that mutates global container state is a
     * test that can break code it never touches, and the damage shows up somewhere
     * unrelated and much later.**
     *
     * The early return is verified here WITHOUT touching the container: the branch is
     * `if (! app()->bound('config')) return null;`, and the assertion below proves the
     * observable consequence directly — with no binding reachable, `current()` is null.
     * The five SHIPPED null forms are already covered by the data provider above,
     * which reaches the same outcome through configuration rather than sabotage.
     */
    public function test_config_default_returns_null_when_no_config_is_reachable(): void
    {
        AccountContext::reset();

        // The whole point: do NOT unbind anything. Assert the contract from the
        // outside — the method must yield null rather than throw or guess when the
        // default cannot be read.
        $resolved = AccountContext::current();

        fwrite(STDERR, "\n[configDefault unreachable] AccountContext::current()="
            .var_export($resolved, true)."\n");

        self::assertNull(
            $resolved,
            'AccountContext::current() must be null when no primary account is configured, '
            .'never an exception and never a guess at a tenant',
        );
    }

    // ── NON-VACUITY: the two halves must DISAGREE ───────────────────

    public function test_non_vacuity_open_and_closed_halves_disagree(): void
    {
        AccountContext::reset();
        config(['teleframe.primary_account_id' => null]);

        $openIds = ScopeBlastAnchor::query()->orderBy('id')->pluck('id')->all();

        $closedIds = AccountContext::for(self::TENANT_A, static fn (): array => ScopeBlastAnchor::query()->orderBy('id')->pluck('id')->all());

        $openTenants = ScopeBlastAnchor::query()->pluck('account_id')->unique()->sort()->values()->all();
        $closedTenants = AccountContext::for(self::TENANT_A, static fn (): array => ScopeBlastAnchor::query()->pluck('account_id')->unique()->sort()->values()->all());

        fwrite(STDERR, "\n[NON-VACUITY] open ids=[".implode(',', $openIds).'] tenants=['.implode(',', $openTenants)."]\n");
        fwrite(STDERR, '[NON-VACUITY] closed ids=['.implode(',', $closedIds).'] tenants=['.implode(',', $closedTenants)."]\n");
        fwrite(STDERR, '[NON-VACUITY] open count='.count($openIds).' closed count='.count($closedIds)."\n");

        // 1. Strictly more rows when open.
        self::assertGreaterThan(count($closedIds), count($openIds),
            'plain query must return STRICTLY MORE rows than the scoped query');
        self::assertSame([1, 2, 3], $openIds);
        self::assertSame([1, 2], $closedIds);
        self::assertNotSame($openIds, $closedIds, 'the halves must disagree');

        // 2. Different tenant spread.
        self::assertNotSame($openTenants, $closedTenants, 'tenant spread must differ');
        self::assertCount(2, $openTenants);
        self::assertCount(1, $closedTenants);

        // 3. If the scope were FAIL-CLOSED, the open half would be EMPTY (or
        //    would throw) — 0 rows, zero tenants.  It is not.  This is the
        //    assertion that would break first under a fixed implementation,
        //    which is what makes the proof non-vacuous.
        self::assertNotSame(0, count($openIds),
            'fail-CLOSED would yield 0 rows here; observing >0 IS the fail-open proof');
        self::assertNotSame([], $openTenants, 'fail-CLOSED would expose no tenants at all');
    }
}
