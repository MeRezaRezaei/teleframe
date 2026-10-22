<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine;

final class Action
{
    public function __construct(
        public string $handlerClass,
        public array $payload = [],
        public ?string $version = null,
    ) {}
}

class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\QueryEngine\\Action', 'MeRezaRezaei\\Telegram\\MultiSession\\QueryEngine\\Action');
class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\QueryEngine\\Action', 'MeRezaRezaei\\Teleframe\\MultiSession\\QueryEngine\\Action');
