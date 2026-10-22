<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * AppAiBridge — the wave-3 seam through which the App framework consumes
 * Passport identity data. The LLM wiring itself stays host-owned; this
 * interface only names the hook a host (or an App) implements to receive a
 * successful passport login's identity. Implementation lives in App wave-3.
 *
 * Plain-PHP, zero container.
 */
interface AppAiBridge
{
    /**
     * Consume the identity data produced by a successful passport login.
     *
     * @param  LoginResult  $login  the passport login outcome
     */
    public function consumePassportIdentity(LoginResult $login): void;
}
