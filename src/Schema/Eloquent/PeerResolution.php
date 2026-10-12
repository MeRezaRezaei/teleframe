<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for models that hold a peer-long column (bigint, canonical
 * Telegram peer long).  Provides generic col-parameterized helpers
 * for filtering and resolving the peer to its domain model.
 */
trait PeerResolution
{
    /**
     * Filter rows where the stored peer-long value equals exactly $peerLong.
     */
    public function scopeWherePeerLong(Builder $query, string $col, int $peerLong): Builder
    {
        return $query->where($query->getModel()->qualifyColumn($col), $peerLong);
    }

    /**
     * Filter rows whose peer-long column equals PeerIdTool::userLong($userId).
     */
    public function scopeWherePeerIsUser(Builder $query, string $col, int $userId): Builder
    {
        return $query->where($query->getModel()->qualifyColumn($col), PeerIdTool::userLong($userId));
    }

    /**
     * Filter rows whose peer-long column equals PeerIdTool::chatLong($chatId).
     */
    public function scopeWherePeerIsChat(Builder $query, string $col, int $chatId): Builder
    {
        return $query->where($query->getModel()->qualifyColumn($col), PeerIdTool::chatLong($chatId));
    }

    /**
     * Filter rows whose peer-long column equals PeerIdTool::channelLong($channelId).
     */
    public function scopeWherePeerIsChannel(Builder $query, string $col, int $channelId): Builder
    {
        return $query->where($query->getModel()->qualifyColumn($col), PeerIdTool::channelLong($channelId));
    }

    /**
     * Decode the peer-long value in $col and query the matching domain
     * model for the current account scope.
     *
     * When $classMap is provided it overrides the default FQCN mapping,
     * allowing tests to substitute lightweight stubs without importing
     * generated models.
     *
     * @param array{user?: class-string<Model>, chat?: class-string<Model>, channel?: class-string<Model>} $classMap
     *
     * @return Model|null
     */
    public function resolvePeerModel(string $col, ?array $classMap = null): ?Model
    {
        /** @var int|null $long */
        $long = $this->getAttribute($col);

        if ($long === null || $long === 0) {
            return null;
        }

        $decoded = PeerIdTool::decode((int) $long);

        $map = $classMap ?? self::PEER_FQCN_MAP;

        $fqcn = $map[$decoded['kind']] ?? null;

        if ($fqcn === null || ! class_exists($fqcn)) {
            return null;
        }

        /** @var Model $instance */
        $instance = new $fqcn();

        return $instance->newQuery()
            ->where($instance->qualifyColumn('id'), $decoded['id'])
            ->first();
    }

    /**
     * Default FQCN map — points to domain table models (one model per
     * domain, not per constructor). Resolution uses the `id` column
     * which is the Telegram native ID on global-ID domain tables.
     *
     * @var array{user: class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser>, chat: class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat>, channel: class-string<\MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel>}
     */
    private const PEER_FQCN_MAP = [
        'user'    => 'MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser',
        'chat'    => 'MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat',
        'channel' => 'MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel',
    ];
}
