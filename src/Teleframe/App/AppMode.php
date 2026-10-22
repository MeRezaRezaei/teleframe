<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

/**
 * AppMode (design §1/§2, gap G1) — the one roof for both app kinds a
 * declarative program can run as: `bot` (HTTP Bot API BotClient) or
 * `user` (MTProto UserAccountScope), resolved at call time by the
 * transport-resolver seam (design §6). The backed word IS the transport
 * name the resolver maps to a scope; it is a mode label only — never a
 * credential, never a session reference.
 */
enum AppMode: string
{
    case Bot = 'bot';
    case User = 'user';

    /**
     * The transport name the resolver consumes (design §6): 'bot' →
     * TeleframeClient::bot(), 'user' → TeleframeClient::user(). Today the
     * mode word and the transport word coincide; transportName() is the
     * semantic accessor so a future resolver-key change stays local to
     * this enum instead of leaking across the module.
     */
    public function transportName(): string
    {
        return $this->value;
    }
}
