<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Telegram\MultiSession;

use MeRezaRezaei\Teleframe\Handler\Update as HandlerUpdate;
use MeRezaRezaei\Teleframe\Telegram\MultiSession\Session as MultiSession;
use MeRezaRezaei\Telegram\MultiSession\LoopDetector\LoopDetector;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class SessionRouter
{
    private LoggerInterface $logger;

    /** @var mixed */
    private $sessionStore;

    /** @var array<string, mixed> */
    private array $sessions = [];

    private LoopDetector $loopDetector;

    public function __construct(mixed $sessionStoreOrLogger = null, mixed $logger = null, mixed ...$rest)
    {
        $resolvedLogger = null;
        $resolvedStore = null;

        foreach ([$sessionStoreOrLogger, $logger, ...$rest] as $candidate) {
            if ($candidate instanceof LoggerInterface) {
                $resolvedLogger = $candidate;
            } elseif (is_array($candidate) || is_object($candidate)) {
                if ($resolvedStore === null) {
                    $resolvedStore = $candidate;
                }
            }
        }

        $this->logger = $resolvedLogger ?? new NullLogger;
        $this->sessionStore = $resolvedStore ?? new SessionStore;
        $this->loopDetector = new LoopDetector;
    }

    public function getLoopDetector(): LoopDetector
    {
        return $this->loopDetector;
    }

    public function setLoopDetector(LoopDetector $loopDetector): void
    {
        $this->loopDetector = $loopDetector;
    }

    public function getSessionStore(): mixed
    {
        return $this->sessionStore;
    }

    public function getSessions(): array
    {
        if (is_array($this->sessionStore) && array_is_list($this->sessionStore) === false) {
            // best-effort
        }
        if ($this->sessionStore instanceof SessionStore) {
            return $this->sessionStore->all();
        }
        if (is_array($this->sessionStore)) {
            return $this->sessionStore;
        }
        if (is_object($this->sessionStore) && method_exists($this->sessionStore, 'all')) {
            try {
                $v = $this->sessionStore->all();
                if (is_array($v)) {
                    return $v;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return $this->sessions;
    }

    public function getSessionKey(mixed $update): string
    {
        $arr = $this->toArray($update);

        $botToken = $this->first($arr, ['bot_token', 'botToken', 'token', 'bot_token_id']);
        $botToken = $botToken === null ? '' : (string) $botToken;

        if ($this->isBusinessUpdate($arr, $update)) {
            $connectionId = $this->extractBusinessConnectionId($arr);
            $userId = $this->extractBusinessUserId($arr);
            if ($connectionId === '') {
                $connectionId = (string) ($this->first($arr, ['connection_id', 'connectionId']) ?? '');
            }
            if ($userId === '') {
                $userId = (string) ($this->first($arr, ['user_id', 'userId', 'from_id']) ?? '0');
            }

            return $botToken.':business:'.$connectionId.':'.$userId;
        }

        $userId = $this->extractUserId($arr);
        $chatId = $this->extractChatId($arr);

        return $botToken.':'.$userId.':'.$chatId;
    }

    public function route(mixed $update): void
    {
        $key = $this->getSessionKey($update);
        $session = $this->getOrCreateSession($key, $update);

        $actionId = $this->getActionId($update);

        // Record execution in loop detector
        $this->loopDetector->recordExecution($actionId, $this->getTriggeredBy($update));

        // Check if should execute
        if (! $this->loopDetector->shouldExecute($actionId, $this->getTriggeredBy($update))) {
            $this->logger->warning('SessionRouter: Action blocked by LoopDetector', [
                'sessionKey' => $key,
                'actionId' => $actionId,
                'cooldownRemaining' => $this->loopDetector->getCooldown($actionId),
                'isDeduped' => $this->loopDetector->isDeduped($actionId),
            ]);

            return;
        }

        $this->logger->debug('SessionRouter route', [
            'sessionKey' => $key,
            'hasSession' => $session !== null,
            'actionId' => $actionId,
        ]);
    }

    private function getActionId(mixed $update): string
    {
        $arr = $this->toArray($update);
        $type = $this->first($arr, ['type']);
        if (is_string($type)) {
            return $type;
        }
        $ctor = '';
        if ($update instanceof HandlerUpdate) {
            $ctor = $update->constructor();
        } elseif (isset($arr['_']) && is_string($arr['_'])) {
            $ctor = $arr['_'];
        } elseif (is_object($update) && method_exists($update, 'constructor')) {
            try {
                $ctor = (string) $update->constructor();
            } catch (\Throwable $e) {
                $ctor = '';
            }
        }

        return $ctor ?: 'unknown';
    }

    private function getTriggeredBy(mixed $update): ?string
    {
        $arr = $this->toArray($update);
        // Check if this update was triggered by another action (e.g., via callback_query)
        if (isset($arr['callback_query']) && is_array($arr['callback_query'])) {
            if (isset($arr['callback_query']['data']) && is_string($arr['callback_query']['data'])) {
                return 'callback_query:'.$arr['callback_query']['data'];
            }
        }
        // Check for business connection triggers
        if ($this->isBusinessUpdate($arr, $update)) {
            return 'business_connection';
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Session store interaction
    // ------------------------------------------------------------------

    private function getOrCreateSession(string $key, mixed $update): mixed
    {
        $store = $this->sessionStore;

        if ($store instanceof SessionStore) {
            $context = $this->toArray($update);

            return $store->getOrCreate($key, fn () => $this->newSession($key, $context));
        }

        if (is_array($store)) {
            if (array_key_exists($key, $store)) {
                return $store[$key];
            }
            $session = $this->newSession($key, $this->toArray($update));
            $store[$key] = $session;
            $this->sessionStore = $store;
            $this->sessions = $store;

            return $session;
        }

        if (is_object($store)) {
            // getOrCreate with factory
            if (method_exists($store, 'getOrCreate')) {
                try {
                    $rm = new \ReflectionMethod($store, 'getOrCreate');
                    $params = $rm->getParameters();
                    if (count($params) >= 2) {
                        return $store->getOrCreate($key, fn () => $this->newSession($key, $this->toArray($update)));
                    }
                    // single-arg getOrCreate(key)
                    $existing = method_exists($store, 'get') ? $store->get($key) : null;
                    if ($existing !== null) {
                        return $existing;
                    }
                    $session = $this->newSession($key, $this->toArray($update));
                    if (method_exists($store, 'set')) {
                        $store->set($key, $session);
                    } elseif (method_exists($store, 'put')) {
                        $store->put($key, $session);
                    }

                    return $session;
                } catch (\Throwable $e) {
                    // fall through
                }
            }

            // getSession / createSession pair
            if (method_exists($store, 'getSession') && method_exists($store, 'createSession')) {
                $existing = $store->getSession($key);
                if ($existing !== null) {
                    return $existing;
                }
                $session = $store->createSession($key, $this->toArray($update));

                return $session;
            }

            // get + (set|put|save|store)
            if (method_exists($store, 'get')) {
                try {
                    $existing = $store->get($key);
                } catch (\Throwable $e) {
                    $existing = null;
                }
                if ($existing !== null) {
                    return $existing;
                }
                $session = $this->newSession($key, $this->toArray($update));
                foreach (['set', 'put', 'save', 'store', 'add'] as $m) {
                    if (method_exists($store, $m)) {
                        try {
                            $store->{$m}($key, $session);
                        } catch (\Throwable $e) {
                            // try next
                        }
                        break;
                    }
                }

                return $session;
            }

            // create-only fallback
            if (method_exists($store, 'create')) {
                $session = $store->create($key, $this->toArray($update));

                return $session;
            }
        }

        // fallback internal
        if (array_key_exists($key, $this->sessions)) {
            return $this->sessions[$key];
        }
        $session = $this->newSession($key, $this->toArray($update));
        $this->sessions[$key] = $session;

        return $session;
    }

    private function newSession(string $key, array $context): mixed
    {
        // Try to infer components
        $botToken = (string) ($this->first($context, ['bot_token', 'botToken', 'token']) ?? '');
        $userId = $this->first($context, ['user_id', 'userId', 'from_id']) ?? 0;
        $chatId = $this->first($context, ['chat_id', 'chatId']) ?? 0;

        if (class_exists(MultiSession::class)) {
            return new MultiSession($key, $botToken, $userId, $chatId, $context);
        }

        return (object) ['key' => $key, 'botToken' => $botToken, 'userId' => $userId, 'chatId' => $chatId];
    }

    // ------------------------------------------------------------------
    // Update normalization
    // ------------------------------------------------------------------

    private function toArray(mixed $update): array
    {
        if ($update instanceof HandlerUpdate) {
            return $update->array;
        }
        if (is_array($update)) {
            return $update;
        }
        if (is_object($update)) {
            // HandlerUpdate-like
            if (property_exists($update, 'array') && is_array($update->array)) {
                return $update->array;
            }
            if (method_exists($update, 'toArray')) {
                try {
                    $v = $update->toArray();
                    if (is_array($v)) {
                        return $v;
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $arr = [];
            foreach ((array) $update as $k => $v) {
                // strip null byte prefixes from private props
                $key = $k;
                // strip PHP private-property null-byte prefix without regex
                $pos = strpos($key, "\0");
                if ($pos !== false) {
                    $end = strpos($key, "\0", $pos + 1);
                    if ($end !== false) {
                        $key = substr($key, $end + 1);
                    }
                }
                $arr[$key] = $v;
            }

            return $arr;
        }

        return [];
    }

    private function first(array $arr, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $arr) && $arr[$key] !== null) {
                return $arr[$key];
            }
        }
        // nested search one level
        foreach ($arr as $v) {
            if (is_array($v)) {
                foreach ($keys as $key) {
                    if (array_key_exists($key, $v) && $v[$key] !== null) {
                        return $v[$key];
                    }
                }
            }
        }

        return null;
    }

    private function isBusinessUpdate(array $arr, mixed $update): bool
    {
        // explicit type field
        $type = $this->first($arr, ['type']);
        if (is_string($type)) {
            $t = strtolower($type);
            if (str_contains($t, 'business')) {
                return true;
            }
        }

        // constructor name
        $ctor = '';
        if ($update instanceof HandlerUpdate) {
            $ctor = $update->constructor();
        } elseif (isset($arr['_']) && is_string($arr['_'])) {
            $ctor = $arr['_'];
        } elseif (is_object($update) && method_exists($update, 'constructor')) {
            try {
                $ctor = (string) $update->constructor();
            } catch (\Throwable $e) {
                $ctor = '';
            }
        }
        $lower = strtolower($ctor);
        foreach (['updatebotbusinessconnect', 'updatebotnewbusinessmessage', 'updateboteditbusinessmessage', 'updatebotdeletebusinessmessage', 'business_connection', 'business_message', 'business'] as $needle) {
            if (str_contains(str_replace(['_', '-'], '', $lower), str_replace(['_', '-'], '', $needle))) {
                // careful: 'business' alone could match; require more specific for generic
                if ($needle === 'business') {
                    continue;
                }

                return true;
            }
        }

        // keys in array
        foreach (['business_connection', 'business_message', 'edited_business_message', 'deleted_business_messages', 'connection_id'] as $key) {
            if (array_key_exists($key, $arr)) {
                // connection_id alone may appear; ensure business context
                if ($key === 'connection_id') {
                    // only treat as business if update type suggests or other business keys
                    continue;
                }

                return true;
            }
        }
        // updateBot* keys
        foreach (array_keys($arr) as $k) {
            if (is_string($k) && str_contains(strtolower($k), 'business')) {
                return true;
            }
        }

        return false;
    }

    private function extractBusinessConnectionId(array $arr): string
    {
        // direct
        foreach (['connection_id', 'connectionId', 'business_connection_id'] as $k) {
            if (isset($arr[$k]) && (is_string($arr[$k]) || is_int($arr[$k]))) {
                return (string) $arr[$k];
            }
        }
        // nested business_connection / connection
        foreach (['business_connection', 'connection'] as $k) {
            if (isset($arr[$k]) && is_array($arr[$k])) {
                foreach (['id', 'connection_id', 'connectionId'] as $kk) {
                    if (isset($arr[$k][$kk])) {
                        return (string) $arr[$k][$kk];
                    }
                }
            }
            if (isset($arr[$k]) && is_object($arr[$k])) {
                foreach (['id', 'connectionId', 'connection_id'] as $kk) {
                    if (isset($arr[$k]->{$kk})) {
                        return (string) $arr[$k]->{$kk};
                    }
                }
            }
        }
        // nested in message
        foreach (['message', 'business_message', 'edited_business_message'] as $k) {
            if (isset($arr[$k]) && is_array($arr[$k])) {
                foreach (['connection_id', 'connectionId', 'business_connection_id'] as $kk) {
                    if (isset($arr[$k][$kk])) {
                        return (string) $arr[$k][$kk];
                    }
                }
            }
        }

        return '';
    }

    private function extractBusinessUserId(array $arr): string
    {
        // business connection user
        foreach (['business_connection', 'connection'] as $k) {
            if (isset($arr[$k]) && is_array($arr[$k])) {
                if (isset($arr[$k]['user']) && is_array($arr[$k]['user']) && isset($arr[$k]['user']['id'])) {
                    return (string) $arr[$k]['user']['id'];
                }
                if (isset($arr[$k]['user_id'])) {
                    return (string) $arr[$k]['user_id'];
                }
                if (isset($arr[$k]['userId'])) {
                    return (string) $arr[$k]['userId'];
                }
            }
            if (isset($arr[$k]) && is_object($arr[$k])) {
                if (isset($arr[$k]->user) && is_object($arr[$k]->user) && isset($arr[$k]->user->id)) {
                    return (string) $arr[$k]->user->id;
                }
            }
        }
        // message from
        foreach (['business_message', 'edited_business_message', 'message', 'edited_message'] as $k) {
            if (isset($arr[$k]) && is_array($arr[$k])) {
                if (isset($arr[$k]['from']) && is_array($arr[$k]['from']) && isset($arr[$k]['from']['id'])) {
                    return (string) $arr[$k]['from']['id'];
                }
                if (isset($arr[$k]['from_id'])) {
                    return (string) $arr[$k]['from_id'];
                }
            }
        }
        foreach (['user_id', 'userId', 'from_id', 'fromId'] as $k) {
            if (isset($arr[$k])) {
                return (string) $arr[$k];
            }
        }

        return '0';
    }

    private function extractUserId(array $arr): string
    {
        // direct
        foreach (['user_id', 'userId', 'from_id'] as $k) {
            if (isset($arr[$k])) {
                return (string) $arr[$k];
            }
        }
        // nested message-like
        foreach (['message', 'edited_message', 'channel_post', 'edited_channel_post', 'callback_query'] as $k) {
            if (isset($arr[$k]) && is_array($arr[$k])) {
                $nested = $arr[$k];
                if (isset($nested['from']) && is_array($nested['from']) && isset($nested['from']['id'])) {
                    return (string) $nested['from']['id'];
                }
                if (isset($nested['from_id'])) {
                    return (string) $nested['from_id'];
                }
                if (isset($nested['sender_chat']) && is_array($nested['sender_chat']) && isset($nested['sender_chat']['id'])) {
                    return (string) $nested['sender_chat']['id'];
                }
            }
            if (isset($arr[$k]) && is_object($arr[$k])) {
                $o = $arr[$k];
                if (isset($o->from) && is_object($o->from) && isset($o->from->id)) {
                    return (string) $o->from->id;
                }
                if (isset($o->from_id)) {
                    return (string) $o->from_id;
                }
            }
        }
        // callback_query from
        if (isset($arr['callback_query']) && is_array($arr['callback_query'])) {
            $cq = $arr['callback_query'];
            if (isset($cq['from']) && is_array($cq['from']) && isset($cq['from']['id'])) {
                return (string) $cq['from']['id'];
            }
        }

        // channel post fallback: chat id
        return '0';
    }

    private function extractChatId(array $arr): string
    {
        // direct
        foreach (['chat_id', 'chatId'] as $k) {
            if (isset($arr[$k])) {
                return (string) $arr[$k];
            }
        }
        foreach (['message', 'edited_message', 'channel_post', 'edited_channel_post'] as $k) {
            if (isset($arr[$k]) && is_array($arr[$k])) {
                $nested = $arr[$k];
                if (isset($nested['chat']) && is_array($nested['chat']) && isset($nested['chat']['id'])) {
                    return (string) $nested['chat']['id'];
                }
                if (isset($nested['chat_id'])) {
                    return (string) $nested['chat_id'];
                }
            }
            if (isset($arr[$k]) && is_object($arr[$k])) {
                $o = $arr[$k];
                if (isset($o->chat) && is_object($o->chat) && isset($o->chat->id)) {
                    return (string) $o->chat->id;
                }
            }
        }
        // callback_query message chat
        if (isset($arr['callback_query']) && is_array($arr['callback_query'])) {
            $cq = $arr['callback_query'];
            if (isset($cq['message']) && is_array($cq['message'])) {
                $m = $cq['message'];
                if (isset($m['chat']) && is_array($m['chat']) && isset($m['chat']['id'])) {
                    return (string) $m['chat']['id'];
                }
            }
        }
        // fallback chat id from direct id?
        if (isset($arr['chat']) && is_array($arr['chat']) && isset($arr['chat']['id'])) {
            return (string) $arr['chat']['id'];
        }

        return '0';
    }
}

// Alias Session class import
class_alias('MeRezaRezaei\\Teleframe\\Telegram\\MultiSession\\SessionRouter', 'MeRezaRezaei\\Telegram\\MultiSession\\SessionRouter');
