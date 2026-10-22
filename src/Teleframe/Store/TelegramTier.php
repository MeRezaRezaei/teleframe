<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use RuntimeException;

/**
 * The REMOTE TIER, addressed and nothing else (V1.2 cold-copy pass,
 * 2026-09-27; verbatim 2026-09-20: "i need each channel and deal with it as a
 * bucket storage of min io … uses a mechnism of removing those that are no
 * longer is requesting but the api is intact").
 *
 * Everything a cold copy needs lives behind this one class: a manifest for an
 * object whose local index row is gone, a media reference for a message whose
 * tf_store_media row is gone, and the list of what the bucket still holds. The
 * local truth tables are a CACHE of these answers, not a precondition for
 * asking the question — that inversion is the whole difference between "the
 * store needs a database" and "the store needs Telegram".
 *
 * It is a separate class, and an INJECTED one, for three reasons that are
 * load-bearing rather than cosmetic:
 *
 *  1. TESTABILITY OVER THE MACHINE. Every answer here comes from an injected
 *     TelegramStoreChannel, whose api map is plain closures. A test binds a
 *     fake channel and/or a fake tier and the branch is decided by the
 *     injection — never by whether this host happens to have ext-grpc, a
 *     network, or a Telegram account. That is the shape commits ee21e334 and
 *     97413884 were written about.
 *  2. ONE trust gate. Manifest identity, chunk-order and MAC rules live in
 *     MetadataMessage/ObjectManifest/ManifestMac; this class decides only
 *     WHICH message is the candidate and hands the parsed value on. It never
 *     re-implements verification, so there is one place to audit, not two.
 *  3. Zero reach. It holds no scope, no disk and no state: the same class runs
 *     under a live UserAccountScope, under the standalone smoke, and offline.
 *
 * WHAT IS DELIBERATELY NOT HERE — the honest limit, and the reason the cold
 * posture does not widen the attack surface:
 *
 *  - It does NOT override a manifest that is already in the local truth table.
 *    For tf_store_objects a local row IS trustworthy: those rows are only ever
 *    written by this module, whereas a TFSTORE1 message in a channel anyone can
 *    post in is not. Preferring the editable copy over the trusted one would let
 *    anyone who can post in a bucket decide what a raw object reads as. So: a
 *    present object row stays authoritative for reads, and this class is
 *    consulted only when there is nothing local — which is also the only
 *    situation in which a cold copy is in trouble.
 *
 *    THAT PARAGRAPH WAS WRONG FOR tf_store_media, AND THE ERROR WAS A HOLE.
 *    A media row is NOT only ever written by this module: archiveMessageMedia()
 *    writes one straight from a live update, so a stranger who can post in a
 *    declared DATA channel authors rows naming THEIR document. Reads may
 *    tolerate that (a bad row makes a read wrong, not a host's disk smaller),
 *    but a RECLAIM may not: freeing a local file against a stranger-authored row
 *    deletes a host's data on a proof a stranger wrote. The two tables are not
 *    symmetric and the docblock that claimed they were is the reason the
 *    reclaim path used to trust a message. The asymmetry is now enforced where
 *    it bites — StoreService::reclaimLocal() never treats a media row as a
 *    proof on its own, and under the cold posture it re-derives both the
 *    reference and the content hash from this tier on every call.
 *  - It does NOT skip past an unparseable candidate. The newest message
 *    carrying the marker is THE manifest, and if it does not parse (or parses
 *    to a different name) the read is REFUSED loudly. Silently falling through
 *    to an older candidate would be a "try every plausible answer until one
 *    verifies" oracle, which is exactly how a planted message wins. The
 *    consequence is stated rather than hidden: a foreign message can make a
 *    name unreadable until it is removed. That is a denial of service by the
 *    channel's own writers, who can already delete the object outright — it
 *    grants strictly less than the access it refuses.
 *
 * Zero-regex: string/type functions only.
 */
final class TelegramTier implements RemoteTierContract
{
    /**
     * The manifest for ($bucket channel, $name), located in the channel's
     * ordered read peers. null when NO message carries the marker; a loud
     * RuntimeException when one does but is not a valid manifest for that
     * name (see the class docblock — that is a refusal, not a fallback).
     *
     * @return array{manifest: ObjectManifest, peer: array<string, mixed>, msg_id: int}|null
     */
    public function manifestFor(TelegramStoreChannel $channel, string $name): ?array
    {
        $found = $channel->locate(MetadataMessage::MARKER.' '.$name, 1);
        if ($found === null) {
            return null;
        }

        $text = self::entryText($found['entries'][0] ?? null);
        // MetadataMessage::parse() is where the name agrees with the marker
        // line and where the chunk order is checked; both throw rather than
        // return, so there is no second name check here to drift out of sync.
        $manifest = MetadataMessage::parse($text);
        if ($manifest === null) {
            throw new RuntimeException("first candidate for {$channel->bucket()}/{$name} is not a TFSTORE1 message");
        }

        return [
            'manifest' => $manifest,
            'peer' => $found['peer'],
            'msg_id' => (int) ($found['entries'][0]['id'] ?? 0),
        ];
    }

    /**
     * The media reference for one message id, resolved from the remote tier
     * with NO local row involved. This is the hash-addressed half: the returned
     * value is enough to re-publish the document with zero bytes re-uploaded
     * and zero local bytes read.
     *
     * null — documented, never guessed past — when the id addresses no
     * message, when that message carries no document, or when the document
     * cannot ADDRESS Telegram: a zero access_hash is the MTProto "unknown
     * peer" sentinel, a zero doc_id or an empty file_reference cannot name a
     * file at all, and a reference built from any of them would be rejected on
     * every publish. That last case is a tightening over the local-row path,
     * where an operator may legitimately have written a partial row.
     *
     * @return array{reference: MediaReference, peer: array<string, mixed>, row: array<string, mixed>}|null
     */
    public function mediaFor(TelegramStoreChannel $channel, int $msgId): ?array
    {
        $found = $channel->findMessageById($msgId);
        if ($found === null) {
            return null;
        }

        $document = $found['message']['media']['document'] ?? null;
        if (! is_array($document) || ! MediaType::isWireType((string) ($document['_'] ?? ''))) {
            return null;
        }
        if ((int) ($document['id'] ?? 0) <= 0
            || (int) ($document['access_hash'] ?? 0) <= 0
            || trim((string) ($document['file_reference'] ?? '')) === ''
        ) {
            return null;
        }

        return [
            'reference' => MediaReference::fromDocument($document),
            'peer' => $found['peer'],
            // tf_store_media row shape (StoreIndexContract::findMedia), so the
            // caller can cache it locally with one call and the next read is a
            // local hit again.
            'row' => self::mediaRowFromDocument($document),
        ];
    }

    /**
     * The media at $msgId AND the sha256 of the bytes Telegram holds for it
     * right now, in one history walk and one download.
     *
     * WHY THIS IS A SEPARATE METHOD AND NOT A FLAG ON mediaFor(): the two
     * answers are bought with different money. mediaFor() costs a walk and
     * answers "is a document addressable here" — which is all a read or a
     * publish needs, and both of those are on hot paths. This one costs a walk
     * AND a full download, and it answers the only question that binds a local
     * path to a remote document. Folding the download into mediaFor() would tax
     * every cold read and every hash-addressed publish with a full file
     * transfer; folding it out of the reclaim path would leave that path
     * deleting a file on a proof that never mentioned the file.
     *
     * null for every refusal mediaFor() makes, plus one: the bytes cannot be
     * read. A document that cannot be downloaded is not a weaker proof, it is
     * no proof, and the caller fails closed on it.
     *
     * MEMORY, stated plainly: fetchDocument() buffers the whole document, so
     * this is a full-object allocation. That is accepted here and nowhere else
     * — see RemoteTierContract::durableMediaFor()'s docblock for the cost
     * statement, which this class's docblock used to omit while charging it.
     *
     * @return array{row: array<string, mixed>, content_sha256: string}|null
     */
    public function durableMediaFor(TelegramStoreChannel $channel, int $msgId): ?array
    {
        $found = $channel->findMessageById($msgId);
        if ($found === null) {
            return null;
        }

        $document = $found['message']['media']['document'] ?? null;
        if (! is_array($document) || ($document['_'] ?? '') !== 'document') {
            return null;
        }
        if ((int) ($document['id'] ?? 0) <= 0
            || (int) ($document['access_hash'] ?? 0) <= 0
            || trim((string) ($document['file_reference'] ?? '')) === ''
        ) {
            return null;
        }

        // A wire fault mid-download is a failed PROOF, not a failed read: the
        // half-fetched bytes are dropped and null is returned, so the caller
        // refuses instead of comparing a truncated hash against the local file.
        try {
            $bytes = $channel->fetchDocument($found['message']);
        } catch (RuntimeException) {
            return null;
        }

        if ($bytes === '') {
            return null;
        }

        return [
            'row' => self::mediaRowFromDocument($document),
            'content_sha256' => hash('sha256', $bytes),
        ];
    }

    /**
     * What the bucket still holds, per the REMOTE tier: one row per TFSTORE1
     * manifest message, newest message id last. The cold twin of
     * StoreService::objects(), for the host whose local index was rebuilt on a
     * different machine and has no rows yet.
     *
     * @return list<array{name: string, size: int, sha256: string, mode: string, mime: string, created: string, msg_id: int, peer: array<string, mixed>}>
     */
    public function listObjects(TelegramStoreChannel $channel, int $limit = 100): array
    {
        $rows = [];
        $seen = [];

        foreach ($channel->readPeers() as $peer) {
            foreach ($channel->findMessagesByNameIn($peer, MetadataMessage::MARKER, $limit) as $entry) {
                $text = self::entryText($entry);
                $name = MetadataMessage::nameFromText($text);
                if ($name === null || isset($seen[$name])) {
                    continue;
                }
                // A manifest body that does not parse is SKIPPED here, not
                // refused: this is an inventory over a whole channel, and one
                // unparseable message must not hide every other object in it.
                // Nothing is served from this list — reads go through
                // manifestFor(), which refuses loudly — so skipping here cannot
                // authenticate anything.
                $manifest = self::parseQuietly($text);
                if ($manifest === null) {
                    continue;
                }
                $seen[$name] = true;
                $rows[] = [
                    'name' => $manifest->name(),
                    'size' => $manifest->size(),
                    'sha256' => $manifest->sha256(),
                    'mode' => $manifest->mode(),
                    'mime' => $manifest->mime(),
                    'created' => $manifest->created(),
                    'msg_id' => (int) ($entry['id'] ?? 0),
                    'peer' => $peer,
                ];
            }
        }

        return $rows;
    }

    /**
     * The manifest text of a search entry, via its fetch closure. An entry
     * without a usable fetch is a corrupt reply, not an empty object: it is
     * reported rather than silently becoming an empty manifest.
     *
     * @param  array<string, mixed>|null  $entry
     */
    private static function entryText(?array $entry): string
    {
        if ($entry === null || ! isset($entry['fetch']) || ! is_callable($entry['fetch'])) {
            throw new RuntimeException('a located store message carried no readable body');
        }

        return (string) ($entry['fetch'])();
    }

    /**
     * Parse a manifest without letting a corrupt message abort the walk.
     * Null for "not a manifest / unparseable" — never a partial value.
     */
    private static function parseQuietly(string $text): ?ObjectManifest
    {
        try {
            return MetadataMessage::parse($text);
        } catch (RuntimeException) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    /**
     * The MediaType a wire media object declares. Only a document or a photo
     * is addressable by reference, so every caller of the row builder must
     * have screened with MediaType::isWireType() first; a type that slips
     * through is refused by name here rather than stored as a document.
     *
     * @param  array<string, mixed>  $document
     */
    private static function mediaTypeOf(array $document): MediaType
    {
        return MediaType::fromWireType((string) ($document['_'] ?? ''));
    }

    /** @param array<string, mixed> $document */
    private static function mediaRowFromDocument(array $document): array
    {
        return [
            'media_type' => self::mediaTypeOf($document)->value,
            'doc_id' => (int) ($document['id'] ?? 0),
            'access_hash' => (int) ($document['access_hash'] ?? 0),
            'file_reference' => (string) ($document['file_reference'] ?? ''),
            'size' => (int) ($document['size'] ?? 0),
            'mime' => (string) ($document['mime_type'] ?? MediaReference::DEFAULT_MIME),
        ];
    }
}
