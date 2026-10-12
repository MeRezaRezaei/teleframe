<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Exceptions;

class DcMigrationException extends TelegramException
{
    /**
     * The device-auth token Telegram handed with the migration (auth.loginToken-
     * MigrateTo). It is a live credential: the message has never carried it and
     * must not, and it stays off the property bag (var_dump/print_r/serialized
     * exceptions, any error page that dumps an exception object) — it is
     * reachable only through the explicit token() accessor, so every read is a
     * deliberate act at a call site that is about to hand it to auth.
     */
    private ?string $token;

    public function __construct(
        public int $dcId,
        string $message = '',
        int $code = 303,
        ?string $token = null,
        ?\Throwable $previous = null
    ) {
        $this->token = $token;

        parent::__construct(
            $message ?: "Telegram requested DC migration to DC_{$dcId}",
            $code,
            $previous
        );
    }

    public function getDcId(): int
    {
        return $this->dcId;
    }

    /** The migration token, or null. Deliberate accessor — see the property. */
    public function token(): ?string
    {
        return $this->token;
    }

    /**
     * Debug rendering: everything but the token. var_dump()/print_r() on an
     * exception is how tokens reach log lines and Ignition/Sentry payloads.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'dcId' => $this->dcId,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'hasToken' => $this->token !== null,
        ];
    }
}
