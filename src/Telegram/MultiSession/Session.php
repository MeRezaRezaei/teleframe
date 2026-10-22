<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

final class Session
{
    public function __construct(
        public string $key,
        public string $botToken = '',
        public mixed $userId = 0,
        public mixed $chatId = 0,
        public array $context = [],
    ) {}
}

class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\Session', 'MeRezaRezaei\\Telegram\\MultiSession\\Session');
