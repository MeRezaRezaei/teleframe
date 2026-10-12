<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use Throwable;

/**
 * AppTransportResolver (design §2/G5, §6) — THE mode → scope seam: one
 * injected callable maps an AppMode to the shipped transport at call
 * time. Default (no resolver injected) derives from TeleframeClient:
 * `bot` → TeleframeClient::bot() (HTTP BotClient), `user` →
 * TeleframeClient::user() (MTProto UserAccountScope) — the design §6
 * matrix for the slice, resolved lazily through a single plain-PHP
 * client instance. Sessions/tokens are never carried by AppDefinition/
 * AppCall; the injected callable lets hosts feed their own vault/dc
 * rules and tests feed fakes.
 *
 * Loud posture (design §5/§9, TStore/Notify: "unconfigured throws on
 * first use, never silent-drop"): a missing injected resolver falls back
 * to the default (which throws RuntimeExceptions from bot()/user() when
 * credentials are unconfigured), and an injected callable that throws is
 * wrapped — every resolution failure surfaces as ONE loud AppException
 * at first use.
 *
 * Plain-PHP constructible: constructing a TeleframeClient is
 * side-effect-free; only a resolve() call dials for credentials.
 * Zero regex, no container calls.
 */
final class AppTransportResolver
{
    /** @var callable(AppMode): mixed */
    private $resolve;

    public function __construct(?callable $resolve = null)
    {
        if ($resolve !== null) {
            $this->resolve = $resolve;

            return;
        }

        $client = new TeleframeClient;

        $this->resolve = static fn (AppMode $mode): mixed => match ($mode) {
            AppMode::Bot => $client->bot(),
            AppMode::User => $client->user(),
        };
    }

    /**
     * Resolve the transport scope for one mode. Loud on first use:
     * unconfigured credentials and failing injected resolvers both
     * surface as AppException — never a silent drop.
     */
    public function resolve(AppMode $mode): mixed
    {
        try {
            return ($this->resolve)($mode);
        } catch (Throwable $e) {
            throw new AppException(
                'app transport: cannot resolve '.$mode->transportName().' scope'
                .' (unconfigured credentials or a failing resolver — never silent-drop, TStore posture): '
                .$e->getMessage(),
                $e,
            );
        }
    }

    /**
     * Convenience: resolve the transport of a declarative app from its
     * own mode (what AppRuntime::runCall uses).
     */
    public function resolveFor(AppDefinition $app): mixed
    {
        return $this->resolve($app->mode);
    }
}
