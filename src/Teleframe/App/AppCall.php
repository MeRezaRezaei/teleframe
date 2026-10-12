<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

/**
 * AppCall (design §2/G4) — ONE scheduled telegram call a declarative app
 * owns: a name (call id, unique within the app), an interval in seconds,
 * a catalog method (e.g. `messages.sendMessage`, or a Bot API method for
 * bot mode) and the params to invoke it with. Pure immutable data row;
 * AppCallSpec later composes it into the
 * `app-call-{app}-{call}-{12-hex sha256(app . NUL . call)}` SchedulePublisher
 * payload (design §5) — the key is a function of the (app, call) PAIR, not of
 * this name alone, so two apps may both declare `status-ping`.
 *
 * Credentials ruling (AGENTS.md hard rule, design §5/§6): params carry
 * ids/usernames/peer refs ONLY — never session strings, auth keys or
 * tokens. The mode transport is resolved at call time by the resolver
 * seam, so an AppCall never needs to know who dials it.
 */
final class AppCall
{
    /**
     * @param  string  $name  call id, unique within the app
     *                        (one half of the
     *                        'app-call-{app}-{call}-{digest}' payload key)
     * @param  int  $intervalSeconds  how often the call fires, seconds
     * @param  string  $method  catalog method name, e.g. 'messages.sendMessage'
     * @param  array<string, mixed>  $params  ids/usernames/peer refs ONLY —
     *                                        never sessions, keys or tokens
     */
    private function __construct(
        public readonly string $name,
        public readonly int $intervalSeconds,
        public readonly string $method,
        public readonly array $params,
    ) {}

    /**
     * Named constructor (design §5) — the only way to declare a scheduled
     * call.
     *
     * @param  string  $name  call id, unique within the app
     * @param  int  $intervalSeconds  how often the call fires, seconds
     * @param  string  $method  catalog method name, e.g. 'messages.sendMessage'
     * @param  array<string, mixed>  $params  ids/usernames/peer refs ONLY —
     *                                        never sessions, keys or tokens
     */
    public static function every(string $name, int $intervalSeconds, string $method, array $params = []): self
    {
        return new self($name, $intervalSeconds, $method, $params);
    }

    /**
     * The declarative row (design §5 wire keys: interval_seconds etc.).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'interval_seconds' => $this->intervalSeconds,
            'method' => $this->method,
            'params' => $this->params,
        ];
    }
}
