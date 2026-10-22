<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportFieldError (Cycle 18 W3) — ONE `secureValueError` from a
 * `account.AuthorizationForm`, as a value object.
 *
 * Telegram reports per-field failures inside the authorization form's
 * `errors:Vector<SecureValueError>` rather than as an rpc_error, and there are
 * nine constructors in that family (layer 227, verbatim ids):
 *
 *   secureValueError#869d758f                type hash text
 *   secureValueErrorData#e8a40bd9            type data_hash field text
 *   secureValueErrorFrontSide#be3dfa         type file_hash text
 *   secureValueErrorReverseSide#868a2aa5     type file_hash text
 *   secureValueErrorSelfie#e537ced6          type file_hash text
 *   secureValueErrorFile#7a700873            type file_hash text
 *   secureValueErrorFiles#666220e9           type file_hash[] text
 *   secureValueErrorTranslationFile#a1144770 type file_hash text
 *   secureValueErrorTranslationFiles#34636dd8 type file_hash[] text
 *
 * The success criterion is "SecureValueError surfaces per-field errors" — so
 * the differing shapes collapse onto ONE type here: `type` (the
 * SecureValueType, e.g. "phone"), `field` (the element name when the
 * constructor names one, else the constructor name), `text` (Telegram's own
 * human message, kept verbatim), and `constructor` (which of the nine it was),
 * so a caller can render a per-field form and nothing is lost.
 *
 * The hashes are carried as their constructor names + hex, never as values in
 * a log: `hashLabel()` is what an operator sees, and the raw bytes are
 * deliberately not part of the rendered line.
 *
 * Plain data, zero-regex.
 */
final class PassportFieldError
{
    /**
     * @param  string  $constructor  the secureValueError* constructor name
     * @param  string  $type  the SecureValueType the failure belongs to
     * @param  string  $field  the failing element (constructor name when the
     *                         error names no field of its own)
     * @param  string  $text  Telegram's message, verbatim
     */
    public function __construct(
        public readonly string $constructor,
        public readonly string $type,
        public readonly string $field,
        public readonly string $text,
    ) {}

    /**
     * Build from one `errors[]` entry, whatever its constructor.
     *
     * The `field` name is read with a plain key lookup — the only member that
     * carries one is secureValueErrorData (`field:string`) and its siblings
     * have no field, so they fall back to the constructor name (never an empty
     * label the caller would have to special-case).
     *
     * @param  array<string, mixed>  $error
     */
    public static function fromResponse(array $error): self
    {
        $constructor = (string) ($error['_'] ?? 'secureValueError');
        $type = (string) ($error['type'] ?? '');
        $declaredField = $error['field'] ?? null;
        $field = is_string($declaredField) && $declaredField !== '' ? $declaredField : $constructor;

        return new self(
            $constructor,
            $type,
            $field,
            (string) ($error['text'] ?? ''),
        );
    }

    /**
     * The whole list of a form's `errors[]`, in Telegram's order. An absent or
     * empty list yields [] — the caller can tell "no field errors" from "the
     * field was never parsed".
     *
     * @param  array<string, mixed>  $form
     * @return list<self>
     */
    public static function listFromForm(array $form): array
    {
        $errors = $form['errors'] ?? null;
        if (! is_array($errors)) {
            return [];
        }

        $list = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                $list[] = self::fromResponse($error);
            }
        }

        return $list;
    }

    /** A one-line, per-field rendering: "phone.first_name: …" (Telegram's own text). */
    public function label(): string
    {
        return $this->type.($this->type === '' ? '' : '.').$this->field
            .($this->text === '' ? '' : ': '.$this->text);
    }

    /**
     * @return array{constructor: string, type: string, field: string, text: string}
     */
    public function toArray(): array
    {
        return [
            'constructor' => $this->constructor,
            'type' => $this->type,
            'field' => $this->field,
            'text' => $this->text,
        ];
    }
}
