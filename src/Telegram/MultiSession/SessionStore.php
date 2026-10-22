<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

final class SessionStore
{
    /** @var array<string, mixed> */
    private array $sessions = [];

    public function get(string $key): mixed
    {
        return $this->sessions[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->sessions);
    }

    public function set(string $key, mixed $session): void
    {
        $this->sessions[$key] = $session;
    }

    public function put(string $key, mixed $session): void
    {
        $this->set($key, $session);
    }

    public function create(string $key, array $context = []): mixed
    {
        $session = new Session($key, (string) ($context['bot_token'] ?? $context['botToken'] ?? ''), $context['user_id'] ?? $context['userId'] ?? 0, $context['chat_id'] ?? $context['chatId'] ?? 0, $context);
        $this->set($key, $session);

        return $session;
    }

    public function getOrCreate(string $key, ?callable $factory = null): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        $session = $factory ? $factory() : new Session($key);
        $this->set($key, $session);

        return $session;
    }

    public function all(): array
    {
        return $this->sessions;
    }
}

class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\SessionStore', 'MeRezaRezaei\\Telegram\\MultiSession\\SessionStore');
