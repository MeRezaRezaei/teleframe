<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\QueryEngine;

interface QueryContract
{
    public function getQuery(): Query;

    public function getAction(): Action;

    public function getPolicy(): Policy;

    public function getVersion(): string;
}
