<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;

/**
 * ReclaimOutcome (Cycle 16 W2): the result of ONE reclaimLocal() decision.
 *
 * Three states, and the distinction between them is the whole point of
 * returning an object rather than a bool:
 *
 *   retained  the policy chose not to free the file (the default,
 *             `retain-local`). Nothing was touched. This is a decision,
 *             not a failure.
 *   reclaimed the index row was proven durable and the local file is gone.
 *   refused   the caller asked for something unsafe — no index row, an
 *             empty file_reference, a non-positive doc_id, a row whose bytes
 *             are not the bytes the proof is about, a remote tier that no
 *             longer holds the document, or a dry run. Nothing was touched.
 *             `reason` says which.
 *
 * A caller that treats "refused" as "reclaimed" is the bug this type exists to
 * make impossible to write by accident, so the reason is a stable machine
 * token, not prose.
 *
 * Zero-regex: string/type functions only.
 */
final class ReclaimOutcome
{
    public const RETAINED = 'retained';

    public const RECLAIMED = 'reclaimed';

    public const REFUSED = 'refused';

    // Stable reason tokens — callers may switch on these.
    public const REASON_POLICY_RETAINS = 'policy-retains-local';

    public const REASON_NO_INDEX_ROW = 'no-index-row';

    public const REASON_EMPTY_FILE_REFERENCE = 'empty-file-reference';

    public const REASON_INVALID_DOC_ID = 'invalid-doc-id';

    /** A zero access_hash is the MTProto "unknown peer" sentinel: nothing can be re-published with it. */
    public const REASON_INVALID_ACCESS_HASH = 'invalid-access-hash';

    /** $path held no file at all: nothing to free, and the deleter was never asked. */
    public const REASON_ALREADY_GONE = 'already-gone';

    /**
     * The deleter WAS asked and declined — bytes still present, or it refused on
     * the identity it was handed. Deliberately NOT the same token as
     * already-gone: "there was nothing there" and "I tried and could not" are
     * the same fact for a disk and opposite facts for whoever reads the log.
     */
    public const REASON_DELTER_DECLINED = 'deleter-declined';

    public const REASON_DRY_RUN = 'dry-run';

    public const REASON_DELETED = 'deleted';

    // ── the CONTENT-SHAPED refusals (2026-09-27, reclaim proof hardening) ──
    //
    // A proof that only says "document 777 is addressable" is REFERENCE-shaped:
    // it says nothing about the bytes at $path. These four tokens are the
    // refusals a content-shaped proof produces, and every one of them means the
    // same thing to an operator — the local file is still the only copy, so it
    // stays. They are separate tokens because they are separate DIAGNOSES, and
    // "delete your disk" without "here is which check stopped it" is how a
    // storage problem becomes an outage nobody diagnosed.

    /** No sha256 anywhere binds the local bytes to the document. */
    public const REASON_NO_CONTENT_PROOF = 'no-content-proof';

    /** The bytes at $path are not the bytes the proof is about. */
    public const REASON_CONTENT_MISMATCH = 'content-mismatch';

    /** $path could not be read to be hashed — nothing may be freed on an unreadable file. */
    public const REASON_CONTENT_UNREADABLE = 'content-unreadable';

    /** $path held different bytes at the instant before the deleter was called. */
    public const REASON_CONTENT_CHANGED = 'content-changed-before-delete';

    // ── the FRESHNESS refusals ──────────────────────────────────────────
    //
    // A cached row is a PERFORMANCE fact, never a durability proof. Under the
    // cold posture the remote tier is the authority, so it is asked about the
    // exact object about to be deleted, on this call, and these two tokens are
    // what it says when the answer is "not any more" or "not that one".

    /** The remote tier no longer holds any addressable document at this message id. */
    public const REASON_REMOTE_DOCUMENT_GONE = 'remote-document-gone';

    /** The live document at this message id is not the one the local row names. */
    public const REASON_REFERENCE_DRIFT = 'reference-drift';

    /** The caller's own content binding and the local row's disagree. */
    public const REASON_BINDING_CONFLICT = 'binding-conflict';

    private const VALID_STATES = [self::RETAINED, self::RECLAIMED, self::REFUSED];

    public function __construct(
        private readonly string $state,
        private readonly string $reason = '',
    ) {
        if (! in_array($state, self::VALID_STATES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown reclaim state "%s"; expected one of: %s',
                $state,
                implode(', ', self::VALID_STATES),
            ));
        }
    }

    public function state(): string
    {
        return $this->state;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function isRetained(): bool
    {
        return $this->state === self::RETAINED;
    }

    public function isReclaimed(): bool
    {
        return $this->state === self::RECLAIMED;
    }

    public function isRefused(): bool
    {
        return $this->state === self::REFUSED;
    }

    /**
     * True only when bytes were actually freed. `reclaimed` and `dry-run` are
     * distinct on purpose: a dry run proves the decision WITHOUT deleting, and
     * a caller reporting "reclaimed" from a dry run is lying to an operator
     * about their disk.
     */
    public function freedBytes(): bool
    {
        return $this->state === self::RECLAIMED && $this->reason === self::REASON_DELETED;
    }
}
