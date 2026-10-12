<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\App;

/**
 * AppRegistry (design §2 / gap G1) — the name ⇒ AppDefinition table for
 * declarative programs. First exact-match: a name is seeded once and is
 * never redefined — a duplicate define() is a loud AppException (the
 * first-match posture, so a provider-boot seed colliding with a
 * programmatic define surfaces instead of silently clobbering). resolve()
 * mirrors BotMap::for() (unknown → AppException, loud), all() exposes the
 * read-live table (hot: registrations added at runtime are visible to every
 * resolve without restart), has() is the cheap membership probe.
 *
 * Plain-PHP constructible, zero container/facade calls — the
 * standalone-smoke discipline.
 */
final class AppRegistry
{
    /** @var array<string, AppDefinition> */
    private array $apps = [];

    /**
     * Seed (or add at runtime) one app under its name. Duplicate names are
     * refused loud — first-exact-match, never last-write-wins.
     *
     * @throws AppException when the name is already defined
     */
    public function define(AppDefinition $app): self
    {
        $name = $app->name;

        if (isset($this->apps[$name])) {
            throw new AppException("App [{$name}] is already defined; a name is first-exact-match and is never redefined.");
        }

        $this->apps[$name] = $app;

        return $this;
    }

    /**
     * Resolve one app to its definition.
     *
     * @throws AppException when the name is not defined (BotMap::for() posture)
     */
    public function resolve(string $name): AppDefinition
    {
        return $this->apps[$name] ?? throw new AppException("Unknown app [{$name}].");
    }

    /**
     * The whole table — name ⇒ definition, read live (hot; a runtime
     * define() shows up here immediately).
     *
     * @return array<string, AppDefinition>
     */
    public function all(): array
    {
        return $this->apps;
    }

    /** Is the name defined? (cheap membership probe — never throws) */
    public function has(string $name): bool
    {
        return isset($this->apps[$name]);
    }
}
