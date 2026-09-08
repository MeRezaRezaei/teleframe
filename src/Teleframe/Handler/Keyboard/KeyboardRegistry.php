<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Keyboard;

/**
 * The live keyboard table (Q15/Q16) — maps a menu's ``keyId()`` to its Menu,
 * the source of the key→action resolution (a button's ``route`` is the uprate
 * route string registered in the shared HandlerRegistry). Mirrors the
 * HandlerRegistry fluency: ``register()`` returns $this, plus ``count()`` /
 * ``all()`` for introspection; each record is one immutable Menu.
 *
 * Rotation (Q16 version byte): registering a higher version of the SAME menu
 * ``id()`` unseats the previous version's ``keyId()``, so old tokens stop
 * resolving — the MenuRouter then answers "menu expired".
 */
final class KeyboardRegistry
{
    /** @var array<string, Menu> */
    private array $menus = [];

    /** @var array<string, string> */
    private array $current = [];

    public function register(Menu $menu): static
    {
        $prior = $this->current[$menu->id()] ?? null;
        $this->menus[$menu->keyId()] = $menu;
        $this->current[$menu->id()] = $menu->keyId();
        if ($prior !== null && $prior !== $menu->keyId()) {
            unset($this->menus[$prior]);
        }

        return $this;
    }

    public function isLive(string $keyId): bool
    {
        return isset($this->menus[$keyId]);
    }

    /**
     * Resolve a button to its uprate route. Null when the key id is unknown
     * or the version rotated out — the caller answers "menu expired".
     */
    public function routeOf(string $keyId, int $keyIdx): ?string
    {
        $menu = $this->menus[$keyId] ?? null;
        if ($menu === null) {
            return null;
        }

        $button = $menu->buttonAt($keyIdx);
        if ($button === null) {
            return null;
        }

        return $button['route'];
    }

    /** @return list<Menu> */
    public function all(): array
    {
        return array_values($this->menus);
    }

    public function count(): int
    {
        return count($this->menus);
    }
}