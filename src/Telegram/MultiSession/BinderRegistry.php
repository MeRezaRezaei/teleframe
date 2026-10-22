<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\BindingId;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\Query;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\QueryContract;
use MeRezaRezaei\Telegram\MultiSession\LoopDetector\CycleReport;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class BinderRegistry
{
    private array $bindings = [];

    private array $cycles = [];

    private LoggerInterface $logger;

    private $lockHandle = null;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger;
    }

    public function bind(QueryContract $contract): BindingId
    {
        $this->acquire();
        try {
            $id = new BindingId;
            $this->bindings[(string) $id] = $contract;
            $this->logger->debug('BinderRegistry bind', [
                'bindingId' => (string) $id,
                'queryType' => $contract->getQuery()->type,
                'correlationId' => $contract->getQuery()->correlationId,
            ]);

            return $id;
        } finally {
            $this->release();
        }
    }

    public function unbind(BindingId $id): void
    {
        $this->acquire();
        try {
            unset($this->bindings[(string) $id]);
            $this->logger->debug('BinderRegistry unbind', ['bindingId' => (string) $id]);
        } finally {
            $this->release();
        }
    }

    public function getBindingsFor(Update $update): iterable
    {
        $this->acquire();
        try {
            $type = $this->updateType($update);
            $context = $this->updateContext($update);
            $result = [];
            foreach ($this->bindings as $contract) {
                $query = $contract->getQuery();
                if (! $this->queryMatches($query, $type, $context)) {
                    continue;
                }
                $this->logger->debug('BinderRegistry match', [
                    'updateType' => $type,
                    'queryType' => $query->type,
                    'correlationId' => $query->correlationId,
                ]);
                $result[] = $contract;
            }

            return $result;
        } finally {
            $this->release();
        }
    }

    public function getAllBindings(): array
    {
        $this->acquire();
        try {
            $result = [];
            foreach ($this->bindings as $key => $contract) {
                $result[$key] = $contract;
            }

            return $result;
        } finally {
            $this->release();
        }
    }

    public function getCycles(): array
    {
        $this->acquire();
        try {
            return array_values($this->cycles);
        } finally {
            $this->release();
        }
    }

    public function recordCycle(CycleReport $report): void
    {
        $this->acquire();
        try {
            $this->cycles[] = $report;
        } finally {
            $this->release();
        }
    }

    private function queryMatches(Query $query, string $type, array $context): bool
    {
        $qt = strtolower($query->type);
        if ($qt !== '*' && $qt !== 'any' && $qt !== 'all') {
            if ($qt !== $type && ! str_starts_with($qt, $type.'_') && ! str_starts_with($type, $qt.'_')) {
                return false;
            }
        }
        foreach ($query->params as $key => $expected) {
            if ($expected === '*') {
                continue;
            }
            if (! array_key_exists($key, $context)) {
                return false;
            }
            if ($context[$key] !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function updateType(Update $update): string
    {
        $arr = $update->array;
        if (isset($arr['type']) && is_string($arr['type'])) {
            return strtolower($arr['type']);
        }
        foreach (['business_message', 'channel_post', 'callback_query', 'edited_message', 'message'] as $key) {
            if (array_key_exists($key, $arr)) {
                return $key;
            }
        }
        $ctor = $update->constructor();
        $lower = strtolower($ctor);
        if (str_contains($lower, 'business') && str_contains($lower, 'message')) {
            return 'business_message';
        }
        if (str_contains($lower, 'channel')) {
            return 'channel_post';
        }
        if (str_contains($lower, 'callback')) {
            return 'callback_query';
        }
        if (str_contains($lower, 'message')) {
            return 'message';
        }
        if ($ctor !== '') {
            return $ctor;
        }

        return 'unknown';
    }

    private function updateContext(Update $update): array
    {
        $arr = $update->array;
        $context = $arr;
        $context['_constructor'] = $update->constructor();
        foreach (['message', 'business_message', 'channel_post', 'callback_query', 'edited_message'] as $key) {
            if (isset($arr[$key]) && is_array($arr[$key])) {
                $nested = $arr[$key];
                foreach ($nested as $k => $v) {
                    if (! array_key_exists($k, $context)) {
                        $context[$k] = $v;
                    }
                }
                if (isset($nested['chat']) && is_array($nested['chat']) && isset($nested['chat']['id'])) {
                    $context['chat_id'] = $nested['chat']['id'];
                }
                if (isset($nested['from']) && is_array($nested['from']) && isset($nested['from']['id'])) {
                    $context['user_id'] = $nested['from']['id'];
                    $context['from_id'] = $nested['from']['id'];
                }
                if (isset($nested['id'])) {
                    $context['message_id'] = $nested['id'];
                }
                if (isset($nested['text'])) {
                    $context['text'] = $nested['text'];
                }
            }
        }
        if (isset($arr['callback_query']) && is_array($arr['callback_query'])) {
            $cq = $arr['callback_query'];
            if (isset($cq['message']) && is_array($cq['message'])) {
                $m = $cq['message'];
                if (isset($m['chat']) && is_array($m['chat']) && isset($m['chat']['id'])) {
                    $context['chat_id'] = $m['chat']['id'];
                }
                if (isset($m['id'])) {
                    $context['message_id'] = $m['id'];
                }
            }
            if (isset($cq['from']) && is_array($cq['from']) && isset($cq['from']['id'])) {
                $context['user_id'] = $cq['from']['id'];
                $context['from_id'] = $cq['from']['id'];
            }
            if (isset($cq['data'])) {
                $context['data'] = $cq['data'];
            }
        }
        $context['account_id'] = $update->accountId;

        return $context;
    }

    private function acquire(): void
    {
        if ($this->lockHandle === null) {
            $path = sys_get_temp_dir().'/teleframe_binder_registry.lock';
            $handle = fopen($path, 'c');
            if ($handle === false) {
                return;
            }
            $this->lockHandle = $handle;
        }
        flock($this->lockHandle, LOCK_EX);
    }

    private function release(): void
    {
        if (is_resource($this->lockHandle)) {
            flock($this->lockHandle, LOCK_UN);
        }
    }
}

namespace MeRezaRezaei\Telegram\MultiSession;

class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\BinderRegistry', 'MeRezaRezaei\\Telegram\\MultiSession\\BinderRegistry');

class_alias('MeRezaRezaei\Telegram\MultiSession\BinderRegistry', 'MeRezaRezaei\Telegram\MultiSession\QueryEngine\QueryEngine');
class_alias('MeRezaRezaei\Telegram\MultiSession\BinderRegistry', 'MeRezaRezaei\Teleframe\Telegram\MultiSession\QueryEngine\QueryEngine');
