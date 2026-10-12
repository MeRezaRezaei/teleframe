<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;

/**
 * ReferencePolicy (Cycle 16 W2, verbatim 2026-09-24 "this kind is very
 * important it helps keeping storage of laravel free … since we delete
 * something in main laravel storage but its hash in telegram is still in
 * database and the file is in the channel we can store big data and keep our
 * hot data and cold data intact").
 *
 * W1 built the ADDRESSING half — MediaReference + publishByReference make a
 * stored Telegram file_reference sufficient to re-publish a document with zero
 * bytes re-uploaded. This is the FREEING half: once the tf_store_media row is
 * durable, the local Laravel file is no longer the source of truth and can be
 * deleted. Until the row exists, the local file is the ONLY copy, and losing
 * it is data loss.
 *
 * The policy therefore has three states, and the default is the safe one:
 *
 *   retain-local             (DEFAULT) never touches the filesystem. Byte-for-byte
 *                            the pre-Cycle-16 behaviour, so adopting this class
 *                            changes nothing until an operator opts in.
 *   drop-local-after-index   frees the file ONLY after a LOCAL index row is proven
 *                            durable. Never before — that ordering is the whole
 *                            point of the class.
 *   cold-remote-authoritative  the COLD posture (verbatim 2026-09-20: "a mechanism
 *                            of removing those that are no longer is requesting but
 *                            the api is intact"). The local index is a rebuildable
 *                            CACHE, never a precondition, and durability is proven
 *                            from the remote tier (Telegram) itself — so a local file
 *                            may be freed when the REMOTE document is proven
 *                            addressable, with no local row involved at all. Opt-in,
 *                            named, and loud: see DEFAULT below.
 *
 * DEFAULT — WHY IT STAYS `retain-local` (recorded 2026-09-27, the V1.2 cold-copy
 * pass, scored 84/100): the deduction on that goal was never "the reclaim half is
 * missing" — it ships — it was "the DEFAULT posture frees nothing, so 'delete it
 * locally, it's still in Telegram' is not true out of the box". The tempting fix
 * is to flip the default. That would be a package that silently deletes a host's
 * local files on install, with no operator ever asking: `reclaimLocal()` is
 * reachable from a facade, a scheduled command and a reclaim loop, and a
 * mis-scoped path there becomes silent data loss on a machine whose owner never
 * chose bucket-storage semantics. So the default stays inert and the cold
 * behaviour is a NAMED, opt-in posture instead: an operator turns it on with
 * `ReferencePolicy::fromString('cold-remote-authoritative')` — WHICH IS WIRED:
 * `ab1961bc` reads `teleframe.tstore.reference_policy` /
 * `TELEFRAME_REFERENCE_POLICY` in the Laravel provider and hands it to
 * `StoreService`'s constructor, so a host selects this posture from config with
 * no code. (This paragraph used to call that the remaining wiring gap. It was
 * true when written and false within the hour, which is the usual way a comment
 * in the class that DEFINES a capability becomes the reason a reader distrusts
 * it.) Everything about the new behaviour is reachable under a name
 * that says what it does. The cost of this decision is stated plainly: out of the
 * box the store still retains local files, which is why the V1.2 row is not yet
 * 100. That is the honest price of not deleting a stranger's disk.
 *
 * The policy is a dumb value object: it decides, it never acts. Deletion is
 * performed by an INJECTED callable, because src/Teleframe/ must stay
 * plain-PHP-constructible (bin/standalone-smoke.php forbids Illuminate there)
 * and because a policy that can reach a disk on its own is a policy nobody can
 * dry-run.
 *
 * Every refusal is an InvalidArgumentException (the caller asked for something
 * unsafe) or a loud RuntimeException at the call site — never a silent skip,
 * which is the failure mode that would turn "we keep hot data and cold data
 * intact" into silent data loss.
 *
 * Zero-regex: string/type functions only.
 */
final class ReferencePolicy
{
    public const RETAIN_LOCAL = 'retain-local';

    public const DROP_LOCAL_AFTER_INDEX = 'drop-local-after-index';

    public const COLD_REMOTE_AUTHORITATIVE = 'cold-remote-authoritative';

    /** Which proof a reclaim needs: a durable local row, or a durable remote document. */
    public const PROOF_LOCAL_ROW = 'local-index-row';

    public const PROOF_REMOTE_DOCUMENT = 'remote-document';

    private const VALID = [
        self::RETAIN_LOCAL,
        self::DROP_LOCAL_AFTER_INDEX,
        self::COLD_REMOTE_AUTHORITATIVE,
    ];

    private function __construct(private readonly string $mode) {}

    /**
     * The default policy: never touches the filesystem.
     */
    public static function retainLocal(): self
    {
        return new self(self::RETAIN_LOCAL);
    }

    /**
     * Frees the local file once a LOCAL index row is proven durable.
     *
     * ── READ THIS BEFORE CONFIGURING IT (2026-09-27) ────────────────────
     * "Durable" now means CONTENT-durable, and that has a consequence an
     * operator needs stated rather than discovered: this posture REFUSES with
     * `no-content-proof` unless the resolved row carries a `content_sha256`
     * that matches the bytes on disk, or the caller supplies one. It never
     * consults the wire — that is the posture's whole definition, and it is why
     * it cannot go and ask Telegram what the document holds.
     *
     * So the practical rule: this posture frees a file only for a row some
     * writer that HAD THE BYTES produced. Every writer that takes its row from
     * a chat message (StoreService::archiveMessageMedia, indexDataChannel)
     * writes content_sha256 = null and cannot honestly do otherwise, because
     * it has never seen the local file. A message-authored row is therefore
     * never enough, and that is the provenance asymmetry enforced rather than
     * described.
     *
     * HONEST CAVEAT, recorded because nobody should have to reverse-engineer
     * it: no writer in this package currently populates a local
     * content_sha256, so out of the box this posture refuses everything. That
     * is the fail-closed direction and it is the right one, but it does mean
     * `drop-local-after-index` is currently a posture a host must feed a
     * content hash to be useful at all. The COLD posture needs nothing from the
     * caller and derives the hash from Telegram itself; if you are choosing a
     * posture today, that is the one that works without extra wiring.
     */
    public static function dropLocalAfterIndex(): self
    {
        return new self(self::DROP_LOCAL_AFTER_INDEX);
    }

    /**
     * The COLD posture: the remote tier is the authority, the local index is a
     * rebuildable cache, and a local file may be freed once the REMOTE document
     * is proven addressable — with no local row involved.
     *
     * Named for what it does, because it frees bytes: adopting it IS the
     * operator's decision to let this module unlink local files.
     */
    public static function coldRemoteAuthoritative(): self
    {
        return new self(self::COLD_REMOTE_AUTHORITATIVE);
    }

    /**
     * Build from a config/env string. Anything unrecognised is a LOUD failure:
     * silently falling back to `retain-local` would hide a typo that an
     * operator believes is reclaiming disk.
     */
    public static function fromString(?string $mode): self
    {
        // Unset OR blank (a whitespace-only env var) both mean "not configured",
        // which is the safe policy. An operator whose TELEFRAME_REFERENCE_POLICY
        // is accidentally a space must not get a loud failure at boot, and must
        // certainly not get deletion.
        if ($mode === null || trim($mode) === '') {
            return self::retainLocal();
        }

        return self::of($mode);
    }

    /**
     * @throws InvalidArgumentException on an unknown mode.
     */
    public static function of(string $mode): self
    {
        $normalised = strtolower(trim($mode));
        if (! in_array($normalised, self::VALID, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown reference policy "%s"; expected one of: %s',
                $mode,
                implode(', ', self::VALID),
            ));
        }

        return new self($normalised);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    /**
     * True when this policy is allowed to free a local file. A false here must
     * short-circuit BEFORE any filesystem call is attempted.
     */
    public function reclaims(): bool
    {
        return $this->mode === self::DROP_LOCAL_AFTER_INDEX
            || $this->mode === self::COLD_REMOTE_AUTHORITATIVE;
    }

    /**
     * True only in the COLD posture: the remote tier is the authority, so a
     * missing local index row is a cache miss and NOT evidence that the bytes
     * are gone. Every read/publish/delete path checks this before it is
     * allowed to conclude "absent" from a local row lookup.
     *
     * Separate from reclaims() on purpose: the two facts are different
     * decisions, and collapsing them is how a posture quietly becomes "trust
     * remote AND delete" without a name saying so.
     */
    public function trustsRemote(): bool
    {
        return $this->mode === self::COLD_REMOTE_AUTHORITATIVE;
    }

    /**
     * The reclaim proof this posture requires. `drop-local-after-index` is
     * satisfied by a durable LOCAL row; the cold posture is satisfied by a
     * durable REMOTE document (which it then re-writes into the local row as a
     * cache, so the next call is local again). Returns the token to use in the
     * refusal messages so an operator can see WHICH proof was missing.
     */
    public function durabilityProof(): string
    {
        return $this->trustsRemote() ? self::PROOF_REMOTE_DOCUMENT : self::PROOF_LOCAL_ROW;
    }

    /**
     * Whether a proven-durable index row permits deletion.
     *
     * The two arguments are deliberately separate: `reclaims()` is the operator's
     * standing choice, and `indexIsDurable()` is the per-call proof. Passing
     * both through the same method is how "delete then index" bugs happen.
     */
    public function permitsReclaim(bool $indexIsDurable): bool
    {
        return $this->reclaims() && $indexIsDurable;
    }

    /**
     * The outcome of one reclaimLocal() decision. Returned instead of a bare
     * bool so a caller (or a test) can tell "kept on purpose" from "refused,
     * and here is why" — the difference is exactly the one an operator needs
     * when a disk keeps growing.
     */
    public function result(string $state, string $reason = ''): ReclaimOutcome
    {
        return new ReclaimOutcome($state, $reason);
    }
}
