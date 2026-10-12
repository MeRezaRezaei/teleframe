<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\Log\LoggerInterface;

/**
 * AuthorizationExporter (Cycle 18 W3) — `auth.exportAuthorization#e5bfffcd
 * dc_id:int = auth.ExportedAuthorization`, the OFFICIAL layer-227 constructor
 * (TL_telegram_v227.tl:2242, mirrored in src/Schema/schema/sources/api_full.tl).
 *
 * This is the "share this session's authorization" half of the passport wire
 * family: a user account exports, the other side imports. Thin by design — it
 * dispatches exactly one method, validates the answer into an
 * ExportedAuthorization, and refuses loudly on anything unusable. It does NOT
 * log, wrap, or swallow refusals: a `PASSWORD_REQUIRED` here must reach
 * PassportRefusal intact so the caller can route it to the 2FA confirmer.
 *
 * Zero-regex; no container; plain-PHP constructible.
 */
final class AuthorizationExporter
{
    public function __construct(
        private readonly PassportWireContract $wire,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * @param  int  $dcId  0 = the current DC (Telegram's own default)
     *
     * @throws PassportLoginException when no live scope is bound, or the
     *                                answer is not a usable authorization
     */
    public function export(int $dcId = 0): ExportedAuthorization
    {
        $response = $this->wire->call(PassportWireContract::METHOD_EXPORT_AUTHORIZATION, [
            'dc_id' => $dcId,
        ]);

        $authorization = ExportedAuthorization::fromResponse($response);

        if ($this->logger !== null) {
            // id + byte length only — the bytes are a credential and never logged.
            $this->logger->info(
                'auth.exportAuthorization returned an authorization',
                $authorization->toArray()
            );
        }

        return $authorization;
    }
}
