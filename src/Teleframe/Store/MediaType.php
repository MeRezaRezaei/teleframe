<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use InvalidArgumentException;

/**
 * The closed set of media types a tf_store_media row may name, and the
 * ONLY place in the package that decides which MTProto constructor each one
 * becomes.
 *
 * The set is two members, and two is not an assumption — it is what the wire
 * can construct from a stored reference. A reference addresses bytes Telegram
 * already holds by (id, access_hash, file_reference), and the two TL
 * constructors that take exactly that triple are:
 *
 *   document → inputMediaDocument#a8763ab5  id:InputDocument  (api.tl:37)
 *   photo    → inputMediaPhoto#e3af4434     id:InputPhoto     (api.tl:38)
 *
 * and their id types `inputDocument#1abfb575` / `inputPhoto#3bb3b94a` declare
 * the identical id/access_hash/file_reference triple. Every OTHER InputMedia
 * constructor in the schema is unaddressable by reference by construction —
 * inputMediaContact/invoice/poll/geoPoint/dice/venue/game/story/paidMedia
 * carry their content inline, and inputMedia*External takes a URL. So a row
 * naming one of those is not "a media type we have not implemented", it is a
 * category error, and it is refused by name.
 *
 * WHY THIS IS AN ENUM AND NOT A STRING (Cycle 25 landmine): a photo row
 * published as inputMediaDocument is a wire type the server rejects, on a
 * production account, at send time — and worse in intent, a document posted
 * where the caller asked for a photo. With a backed enum the value is decided
 * once, here, and every mapping below is a `match` with NO default arm, so
 * phpstan proves it total: adding a case without a constructor is a build
 * error, not a runtime surprise. Nothing anywhere can degrade to document.
 *
 * House style: the package's other closed sets are backed string enums with a
 * `names()` listing and a parsing factory that throws by name
 * (see Teleframe\Notify\Level, Teleframe\App\AppMode). Zero regex: the parse
 * is a loop over cases(), never preg.
 */
enum MediaType: string
{
    case Document = 'document';
    case Photo = 'photo';

    /** @return list<string> every accepted media_type value, for error messages and tests */
    public static function names(): array
    {
        $names = [];
        foreach (self::cases() as $case) {
            $names[] = $case->value;
        }

        return $names;
    }

    /**
     * Parse a stored tf_store_media.media_type value. A value outside the set
     * is REFUSED BY NAME — never coerced to document, because coercing it is
     * the exact failure this type exists to prevent, and an operator staring
     * at "unknown media type \"pic\"" can fix the row while
     * "unknown media type, treated as document" leaves them with a silently
     * wrong publication they will not notice until a peer reports it.
     */
    public static function fromStored(string $value): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'unknown media type "%s"; expected one of: %s',
            $value,
            implode(', ', self::names()),
        ));
    }

    /**
     * The raw TL object type this reference was built from ('document' or
     * 'photo'). Same spelling as the stored value, but a DIFFERENT fact: this
     * is what came off the wire, so an unrecognised constructor is named here
     * rather than being defaulted the way an absent column is.
     */
    public static function fromWireType(string $type): self
    {
        foreach (self::cases() as $case) {
            if ($case->value === $type) {
                return $case;
            }
        }

        throw new InvalidArgumentException(sprintf(
            'media reference needs a %s object, got %s',
            implode(' or ', self::names()),
            $type === '' ? 'nothing' : '"'.$type.'"',
        ));
    }

    public function wireType(): string
    {
        return match ($this) {
            self::Document => 'document',
            self::Photo => 'photo',
        };
    }

    /**
     * Whether a raw TL media object's `_` is one this set can name. The
     * non-throwing sibling of fromWireType(), for the walk that SCANS a
     * channel and must skip what it cannot store rather than stop on it.
     */
    public static function isWireType(string $type): bool
    {
        foreach (self::cases() as $case) {
            if ($case->wireType() === $type) {
                return true;
            }
        }

        return false;
    }

    /** The InputMedia constructor this media type publishes as. */
    public function inputMediaConstructor(): string
    {
        return match ($this) {
            self::Document => 'inputMediaDocument',
            self::Photo => 'inputMediaPhoto',
        };
    }

    /**
     * The constructor of the `id` field, which is a DIFFERENT TL type per
     * media type: InputDocument for a document, InputPhoto for a photo. The
     * two carry the same three fields, so a copy-paste between them encodes
     * cleanly and is still rejected by the server — the false-green shape
     * this split exists to make unrepresentable.
     */
    public function inputIdConstructor(): string
    {
        return match ($this) {
            self::Document => 'inputDocument',
            self::Photo => 'inputPhoto',
        };
    }
}
