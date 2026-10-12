<?php

declare(strict_types=1);

file_put_contents(__DIR__ . '/executed.txt', '1', LOCK_EX);

return ['text' => 'stamped', 'entities' => []];