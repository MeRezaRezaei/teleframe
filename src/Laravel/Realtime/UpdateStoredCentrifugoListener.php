<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Realtime;

use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Realtime\CentrifugoBridge;
use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * UpdateStored → Centrifugo bridge (Phase 4): every committed ingest row is
 * published to the per-account updates channel, and (when the root model
 * exposes a chat id) to the per-chat messages channel so browsers receive
 * live updates with zero polling.
 *
 * Failures never throw into the ingest transaction: realtime is a
 * projection, the mirror is the source of truth. A failed publish is
 * logged (if a logger was injected) and dropped.
 */
final class UpdateStoredCentrifugoListener
{
    private const CHAT_ATTRIBUTES = ['chat_id', 'peer_id', 'to_id'];

    public function __construct(
        private readonly CentrifugoBridge $bridge,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(UpdateStored $stored): void
    {
        $accountId = $stored->accountId;
        $model = $stored->model;

        $payload = [
            'account_id' => $accountId,
            'type' => (new \ReflectionClass($model))->getShortName(),
            'id' => $model->getKey(),
            'ts' => $this->modelTimestamp($model),
        ];

        try {
            $this->bridge->publish(ChannelNames::accountUpdates($accountId), $payload);

            $chatId = $this->chatId($model);
            if ($chatId !== null) {
                $this->bridge->publish(
                    ChannelNames::accountMessages($accountId, $chatId),
                    $payload,
                );
            }
        } catch (Throwable $e) {
            $this->logger?->error('teleframe.realtime: publish failed', [
                'account_id' => $accountId,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    private function modelTimestamp(object $model): ?int
    {
        $created = method_exists($model, 'getAttribute') ? $model->getAttribute('created_at') : null;

        return $created instanceof \DateTimeInterface ? $created->getTimestamp() : null;
    }

    private function chatId(object $model): ?int
    {
        if (!method_exists($model, 'getAttribute')) {
            return null;
        }

        foreach (self::CHAT_ATTRIBUTES as $attribute) {
            $value = $model->getAttribute($attribute);
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }
}