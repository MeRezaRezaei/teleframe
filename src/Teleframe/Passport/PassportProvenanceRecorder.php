<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PassportProvenanceRecorder — OPTIONAL companion to `BindingWriter` (the one
 * Eloquent touchpoint of the login chain).
 *
 * WHY A SEPARATE INTERFACE AND NOT A SECOND METHOD ON `BindingWriter`:
 * `BindingWriter` is a published seam — a host may have injected its own
 * implementation. Adding a method to it would be a breaking change for every
 * such host, and the alternative (baking provenance into `bind()`'s
 * signature) would drag a security concern into an unrelated contract. Kept
 * separate, `PassportLoginService` probes with `instanceof`: a writer that
 * does not implement this is simply a writer that does not record, and that
 * is never fatal. Both SHIPPED writers implement it, so the default host
 * always gets provenance.
 *
 * WHAT IS RECORDED — TYPES, NEVER VALUES. The verified SecureValueType NAMES
 * ("email", "phone", "personal_details") and nothing else. A verified email
 * address or phone number written into a column would be copying personal
 * data into a table the host never expected it in, where it would outlive the
 * request, escape the request's privacy boundary, and end up in backups and
 * exports nobody was thinking about when they consented to the login. The
 * type list answers the audit question ("with what proof?") without retaining
 * the proof's contents.
 *
 * Plain data — no container, facade, config() or Eloquent calls in the
 * interface itself (zero-regex, per the module's rules).
 */
interface PassportProvenanceRecorder
{
    /**
     * Record that the (telegram id, tenant) identity just completed a
     * Telegram Passport login, and which field TYPES were verified.
     *
     * @param  int  $tgId  the stable telegram bigint identity (tl_user_id)
     * @param  int|null  $accountId  tenant account id; null = no-tenant host row
     * @param  list<string>  $fieldsVerified  SecureValueType NAMES, never their
     *                                        decrypted values (e.g. ['email'])
     */
    public function recordProvenance(int $tgId, ?int $accountId, array $fieldsVerified): void;
}
