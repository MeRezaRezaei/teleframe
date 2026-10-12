<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Exceptions\Rpc;

/**
 * PHONE_CODE_* errors from auth.signIn — descriptions verbatim from
 * https://core.telegram.org/method/auth.signIn#possible-errors
 */
class PhoneCodeException extends RpcErrorException
{
    public function __construct(string $rpcErrorMessage, int $rpcErrorCode, ?string $method = null)
    {
        $hint = match ($rpcErrorMessage) {
            'PHONE_CODE_EXPIRED' => 'The phone code you provided has expired. → Ask the operator to consent to one bounded resend (auth.resendCode via TeleframeAuthService::resendCode()); Telegram rejects the hash itself beyond that, so the login restarts from auth.sendCode.',
            'PHONE_CODE_EMPTY' => 'phone_code is missing. → Ask the user for the login code and send it in phone_code.',
            default => 'The provided phone code is invalid. → Re-check the digits the user entered.',
        };
        parent::__construct($rpcErrorMessage, $rpcErrorCode, $hint, $method);
    }

    /**
     * True when only a fresh code can fix this: the hash Telegram handed us is
     * spent, so re-submitting digits is pointless and auth.resendCode is the
     * only sanctioned next step (a resend can itself return
     * PHONE_CODE_EXPIRED / PHONE_CODE_HASH_EMPTY, which means restart).
     */
    public function isExpired(): bool
    {
        return $this->rpcErrorMessage === 'PHONE_CODE_EXPIRED';
    }

    /** PHONE_CODE_EMPTY — nothing was submitted; not a resend problem. */
    public function isEmpty(): bool
    {
        return $this->rpcErrorMessage === 'PHONE_CODE_EMPTY';
    }
}
