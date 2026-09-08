<?php

declare(strict_types=1);

use MeRezaRezaei\Teleframe\Message\EntityParser;

$plan = EntityParser::spans('Buy now', [
    ['type' => 'bold', 'offset' => 0, 'length' => 3],
    ['type' => 'text_link', 'offset' => 4, 'length' => 3, 'url' => 'https://example.com/buy'],
]);

$plan['reply_markup'] = [
    'inline_keyboard' => [
        [
            ['text' => 'Go', 'callback_data' => 'confirm'],
        ],
    ],
];

return $plan;