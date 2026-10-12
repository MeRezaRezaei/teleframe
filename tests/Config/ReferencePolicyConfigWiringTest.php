<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use Illuminate\Contracts\Container\Container;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Store\ReferencePolicy;
use MeRezaRezaei\Teleframe\Store\StoreService;
use Orchestra\Testbench\TestCase;

/**
 * teleframe.tstore.reference_policy / TELEFRAME_REFERENCE_POLICY — the key that
 * was never declared at all.
 *
 * f5628079 shipped ReferencePolicy::coldRemoteAuthoritative(), a genuinely
 * good posture: the remote (Telegram) tier is the authority, the local index is
 * a rebuildable cache, and a local file may be freed once the REMOTE document
 * is proven addressable with no local row involved. But a host could not turn
 * it on. ReferencePolicy's own docblock named the gap — the provider "is
 * outside src/Teleframe/ and is therefore the remaining wiring gap" — and no
 * config file declared the key, so the paragraph describing the cold posture
 * was reachable only from PHP.
 *
 * WHAT IS PINNED HERE, and why each one matters:
 *
 *   - the DEFAULT stays retain-local. Not a preference: a package that starts
 *     unlinking a host's local files on install, on a path reachable from a
 *     facade, a command and a reclaim loop, is a decision no operator made.
 *     The ruling and its rationale are recorded in ReferencePolicy; this test
 *     exists so the ruling cannot be eroded one edit at a time.
 *   - trustsRemote() and reclaims() stay SEPARATE predicates. "Trust the
 *     remote" must never be inferred from "delete the local copy", or a
 *     posture quietly becomes both without a name saying so.
 *   - an UNRECOGNISED value refuses LOUDLY. Silently falling back to
 *     retain-local would hide a typo from an operator who believes it is
 *     reclaiming disk.
 */
final class ReferencePolicyConfigWiringTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [];
    }

    private function bootedApp(): Container
    {
        // NOT isset(): a property initialised to false IS set as far as isset()
        // is concerned, so isset() here skips register() entirely and every
        // make() below then fails with "does not exist".
        if (! $this->booted) {
            (new TeleframeServiceProvider($this->app))->register();
            $this->booted = true;
        }

        return $this->app;
    }

    private bool $booted = false;

    private function storeService(): StoreService
    {
        return $this->bootedApp()->make(TstoreCommand::SERVICE_KEY);
    }

    private function forgetStoreService(): void
    {
        $this->bootedApp()->forgetInstance(TstoreCommand::SERVICE_KEY);
    }

    public function test_the_key_is_declared_in_the_config_file(): void
    {
        // The gap was not only an absent reader — no config file declared the
        // key at all, so the cold posture had no documented name in .env.
        self::assertContains(
            'teleframe.tstore.reference_policy',
            ConfigKeyRegistry::declaredKeys(),
            'teleframe.tstore.reference_policy is not declared in any config file, so'
            ."\nthe cold-copy posture still cannot be reached from configuration.",
        );
    }

    public function test_the_shipped_default_is_retain_local_and_frees_nothing(): void
    {
        $policy = $this->storeService()->referencePolicy();

        self::assertSame(
            ReferencePolicy::RETAIN_LOCAL,
            $policy->mode(),
            'the shipped default must be retain-local. A package that starts deleting a'
            ."\nhost's local files on install is a surprise no operator asked for.",
        );
        self::assertFalse($policy->reclaims(), 'the default must never free a local file');
        self::assertFalse($policy->trustsRemote(), 'the default must not trust the remote either');
    }

    public function test_the_configured_policy_reaches_the_store_service(): void
    {
        $this->app['config']->set('teleframe.tstore.reference_policy', 'cold-remote-authoritative');
        $this->forgetStoreService();

        $policy = $this->storeService()->referencePolicy();

        self::assertSame(
            ReferencePolicy::COLD_REMOTE_AUTHORITATIVE,
            $policy->mode(),
            'the configured policy never reached the StoreService ctor. The key is'
            ."\ndeclared and documented but read by nothing — the dead-config class.",
        );
    }

    public function test_drop_local_after_index_is_reachable_from_config_too(): void
    {
        // All three named postures must be reachable, or the config is a
        // one-option menu.
        $this->app['config']->set('teleframe.tstore.reference_policy', 'drop-local-after-index');
        $this->forgetStoreService();

        self::assertSame(
            ReferencePolicy::DROP_LOCAL_AFTER_INDEX,
            $this->storeService()->referencePolicy()->mode(),
        );
    }

    public function test_trusts_remote_and_reclaims_stay_separate_predicates(): void
    {
        $this->app['config']->set('teleframe.tstore.reference_policy', 'cold-remote-authoritative');
        $this->forgetStoreService();
        $cold = $this->storeService()->referencePolicy();

        self::assertTrue($cold->trustsRemote(), 'the cold posture trusts the remote tier');
        self::assertTrue($cold->reclaims(), 'and it is the posture that frees local files');

        // The separation that must never erode: the two facts are different
        // decisions. drop-local-after-index frees a file but does NOT trust the
        // remote, so a missing local row is still evidence of absence there.
        $this->app['config']->set('teleframe.tstore.reference_policy', 'drop-local-after-index');
        $this->forgetStoreService();
        $indexed = $this->storeService()->referencePolicy();

        self::assertTrue($indexed->reclaims());
        self::assertFalse(
            $indexed->trustsRemote(),
            'drop-local-after-index must NOT claim to trust the remote. If it did, a'
            ."\nmissing local row would be read as a cache miss and bytes would be"
            ."\ndeclared gone on the strength of a policy that never proved the remote"
            ."\ncopy. The two predicates are separate on purpose.",
        );

        // And the cold posture requires the REMOTE proof, not the local row.
        self::assertSame(
            ReferencePolicy::PROOF_REMOTE_DOCUMENT,
            $cold->durabilityProof(),
        );
        self::assertSame(
            ReferencePolicy::PROOF_LOCAL_ROW,
            $indexed->durabilityProof(),
        );
    }

    public function test_an_unknown_policy_refuses_loudly_instead_of_silently_retaining(): void
    {
        $this->app['config']->set('teleframe.tstore.reference_policy', 'delete-everything');
        $this->forgetStoreService();

        // A typo must not read as "reclaiming disk" — and must not silently
        // read as the safe default either, because the operator set something.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown reference policy');

        $this->storeService();
    }

    public function test_a_blank_policy_is_the_safe_default_not_a_failure(): void
    {
        // An accidentally-whitespace env var must not fatal a boot. This is
        // ReferencePolicy::fromString()'s documented behaviour, pinned here
        // because it is the one path where "unset" and "set" deliberately
        // agree.
        $this->app['config']->set('teleframe.tstore.reference_policy', '   ');
        $this->forgetStoreService();

        self::assertSame(
            ReferencePolicy::RETAIN_LOCAL,
            $this->storeService()->referencePolicy()->mode(),
        );
    }
}
