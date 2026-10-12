<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use RuntimeException;

/**
 * The remote tier, as a seam (V1.2 cold-copy pass, 2026-09-27).
 *
 * Every question a cold copy must be able to ask of Telegram — "what is the
 * manifest for this name", "what document does this message id address", "what
 * does this bucket still hold" — is one method here, and nothing else in the
 * store reaches for the wire to ask it.
 *
 * It is an INTERFACE, and that is the whole point of the file: the branch a
 * test exercises must be decided by what is INJECTED, never by what this host
 * happens to be able to reach. A store that resolved the cold path through a
 * concrete class would make every cold test depend on the machine's network,
 * its extensions and its credentials — which is precisely the false-green
 * shape commits ee21e334 and 97413884 were written about. Binding a stub here
 * pins the branch in an image with no Telegram, and the same assertions then
 * hold in CI.
 *
 * The shipped implementation is TelegramTier, which talks to an injected
 * TelegramStoreChannel (plain closures) and decides nothing but WHICH message
 * is the candidate — every trust rule stays in MetadataMessage /
 * ObjectManifest / ManifestMac, so there is one place to audit.
 *
 * Every method reports absence as null. Absence is never a reason to fall back
 * to "try the next plausible answer": the loud refusals belong to the callers
 * that know what the absence means.
 *
 * Zero-regex: string/type functions only.
 */
interface RemoteTierContract
{
    /**
     * The manifest message for ($name) in the channel's ordered read peers.
     *
     * @return array{manifest: ObjectManifest, peer: array<string, mixed>, msg_id: int}|null
     *
     * @throws RuntimeException when a message carries the marker but is not a
     *                          valid manifest for that name — a refusal, not a fallback.
     */
    public function manifestFor(TelegramStoreChannel $channel, string $name): ?array;

    /**
     * The media reference one message id addresses, with no local row involved.
     * null when the id names no message, names no document, or names a document
     * that cannot address Telegram (no doc_id, no access_hash, no file_reference).
     *
     * @return array{reference: MediaReference, peer: array<string, mixed>, row: array<string, mixed>}|null
     */
    public function mediaFor(TelegramStoreChannel $channel, int $msgId): ?array;

    /**
     * The media at $msgId together with the sha256 of the bytes Telegram holds
     * for it RIGHT NOW — the CONTENT half of a durability proof, gathered in one
     * history walk and one download.
     *
     * This exists because the two halves of a reclaim proof are not
     * interchangeable, and a proof built from only one of them deletes the wrong
     * file. `mediaFor()` answers "is a durable document addressable at this
     * message id" — that is what re-publishing needs, and it says NOTHING about
     * which bytes are at $path. This one additionally answers "and those bytes
     * hash to X", which is the only question that binds a local path to a
     * remote document.
     *
     * null — the same refusals as mediaFor(), plus one more: the document
     * cannot be READ. An unreadable document is not a smaller proof, it is no
     * proof: the caller must fail closed rather than fall back to the
     * reference-shaped answer.
     *
     * The cost is stated rather than hidden: one bounded history walk plus a
     * FULL download of the document into memory. That is deliberate — it is the
     * price of a content-shaped proof, and it is paid only by the reclaim path,
     * never by a read or a publish.
     *
     * @return array{row: array<string, mixed>, content_sha256: string}|null
     */
    public function durableMediaFor(TelegramStoreChannel $channel, int $msgId): ?array;

    /**
     * An inventory of the bucket's TFSTORE1 manifests, per the remote tier.
     * Unparseable messages are skipped (an inventory must not hide every other
     * object in the channel because one message is corrupt) — and nothing is
     * ever SERVED from this list, so skipping authenticates nothing.
     *
     * @return list<array{name: string, size: int, sha256: string, mode: string, mime: string, created: string, msg_id: int, peer: array<string, mixed>}>
     */
    public function listObjects(TelegramStoreChannel $channel, int $limit = 100): array;
}
