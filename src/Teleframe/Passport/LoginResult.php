<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Illuminate\Database\Eloquent\Model;

/**
 * LoginResult — ONE passport login outcome (design §2/§4 G4): the durable
 * artifact of a completed chain. Decrypt → validate → nonce verify →
 * identity resolve → bind all succeeded; the host owns what happens next
 * (the onLogin hook / Auth::login ride — Cycle-8b: the package never stops
 * users, it rides the host auth).
 *
 * Plain data — zero container/facade calls. `user` is the resolved Laravel
 * Model (the service refuses non-Model resolutions before this exists);
 * `binding` is whatever the injected BindingWriter returned (a
 * `TlUserBinding` from the Eloquent writer, the recorded row from the memory
 * writer). `fieldsVerified` lists the decrypted secure_data types that passed
 * validation (personal_details/email/phone) — evidence for the login, never
 * stored anywhere.
 *
 * `documents` is the DOCUMENT TABLE, and it is here rather than behind an opt-in
 * for the reason the table exists at all: `allowed_types` accepted eleven
 * §5 document types and nothing ever opened one, so a passport response
 * produced a "successful" login with no document and no error. `fieldsVerified`
 * could not surface that — it lists what Telegram sent, not what this package
 * managed to read. `documents` lists the second thing, and every row in it is
 * either a decrypted `PassportDocument` or a NAMED
 * `PassportDocumentRefusal`; there is no third possibility. Defaulted to a
 * coverage of an empty allow-list so no existing construction site breaks.
 */
final class LoginResult
{
    /**
     * @param  int  $tgUserId  the stable telegram bigint (message.from.id)
     * @param  int|null  $accountId  tenant account id (null = no-tenant host)
     * @param  Model  $user  the resolved Laravel user
     * @param  mixed  $binding  the binding artifact (TlUserBinding | memory row)
     * @param  list<string>  $fieldsVerified  the verified secure_data types
     * @param  PassportDocumentCoverage|null  $documents  the document table —
     *                                                    a document or a NAMED refusal per `allowed_types` value and §7 slot
     */
    public function __construct(
        public readonly int $tgUserId,
        public readonly ?int $accountId,
        public readonly Model $user,
        public readonly mixed $binding,
        public readonly array $fieldsVerified = [],
        public readonly ?PassportDocumentCoverage $documents = null,
    ) {}

    /**
     * @return array{tg_user_id: int, account_id: ?int, user: Model, binding: mixed, fields_verified: list<string>, documents: array<string, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'tg_user_id' => $this->tgUserId,
            'account_id' => $this->accountId,
            'user' => $this->user,
            'binding' => $this->binding,
            'fields_verified' => $this->fieldsVerified,
            // The coverage table renders NAMES and CODES only — never a
            // decrypted payload, a verified email or a phone number. A login
            // result is exactly the kind of value that gets serialised into a
            // log or a response body, and personal data must not ride along in
            // one by default.
            'documents' => $this->documents?->toArray(),
        ];
    }
}
