<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

final class Action
{
    public function __construct(
        public string $handlerClass,
        public array $payload = [],
        public ?string $version = null,
    ) {}
}
