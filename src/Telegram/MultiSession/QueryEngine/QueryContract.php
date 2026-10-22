<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine;

interface QueryContract
{
    public function getQuery(): Query;

    public function getAction(): Action;

    public function getPolicy(): Policy;

    public function getVersion(): string;
}

class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\QueryEngine\\QueryContract', 'MeRezaRezaei\\Telegram\\MultiSession\\QueryEngine\\QueryContract');
class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\QueryEngine\\QueryContract', 'MeRezaRezaei\\Teleframe\\MultiSession\\QueryEngine\\QueryContract');
