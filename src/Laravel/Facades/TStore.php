<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Store\StoreService;

/**
 * TStore facade (plan W7): `TStore::put(...)`, `TStore::get(...)`,
 * `TStore::exists(...)`, … — the StoreService bound under
 * TstoreCommand::SERVICE_KEY (a rebindable container seam, default built
 * from teleframe.tstore config by the provider).
 *
 * Unconfigured (no teleframe.tstore.account_id): the service still
 * resolves (memory index + sync queue + channel factory that throws on
 * first use), so scaffolding commands like `teleframe:tstore buckets`
 * work before any real credentials exist.
 *
 * @method static StoreService service()
 * @method static array put(string $bucket, string $name, $source, string $mode = 'raw', ?string $passphrase = null, string $mime = 'application/octet-stream', bool $queued = false)
 * @method static string get(string $bucket, string $name, ?string $passphrase = null)
 * @method static bool exists(string $bucket, string $name)
 * @method static ?int size(string $bucket, string $name)
 * @method static void delete(string $bucket, string $name)
 * @method static array buckets()
 * @method static array objects(string $bucket)
 * @method static array syncRegistry(array $buckets)
 * @method static int indexDataChannel(string $bucket)
 * @method static \MeRezaRezaei\Teleframe\Store\ReclaimOutcome reclaimLocal(string $disk, string $path, array $expect = [], ?\MeRezaRezaei\Teleframe\Store\ReferencePolicy $policy = null, ?callable $unlink = null, bool $dryRun = false)
 * @method static array verify(string $bucket, string $name, ?string $passphrase = null)
 * @method static int prune()
 *
 * @see TstoreCommand
 */
class TStore extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TstoreCommand::SERVICE_KEY;
    }

    /**
     * The underlying StoreService, for the calls the facade does not name.
     *
     * Advertised in the `@method` list and in an example, but never
     * implemented — and a Laravel Facade is NOT Macroable, so `TStore::service()`
     * raised `Error: Call to undefined method`. Real now, and it fails with a
     * named reason rather than a fatal if the binding is something else.
     */
    public static function service(): StoreService
    {
        $resolved = static::getFacadeRoot();

        if (! $resolved instanceof StoreService) {
            throw new \LogicException(
                'TStore facade is bound to '.(\is_object($resolved) ? $resolved::class : \gettype($resolved))
                .', expected '.StoreService::class,
            );
        }

        return $resolved;
    }

    /**
     * Repost an already-stored file to another peer by its Telegram hash.
     *
     * A REAL forwarding method, not an `@method` docblock. It used to be only
     * the latter, which meant a host who wanted the owner-sentence capability
     * ("for posting them in our public channel we just pass the hash") had no
     * callable entry point and had to write PHP against the container seam.
     * The signature is declared here as a real method so the IDE, the static
     * analyser and `TStore::publishByReference(...)` all agree.
     *
     * Telegram's own `file_reference` reaches the wire as an
     * inputMediaDocument with no download and no re-upload. A message that is
     * not indexed throws \RuntimeException rather than quietly re-uploading —
     * a silent re-upload is exactly the cost this path removes — and the
     * `teleframe:tstore publish` action surfaces that as a non-zero exit.
     *
     * @param  array<string, mixed>  $targetPeer  an inputPeer of the destination channel/group
     *
     * @throws \InvalidArgumentException when $msgId is not positive
     * @throws \RuntimeException when the message has no stored reference for this account
     */
    public static function publishByReference(string $bucket, int $msgId, array $targetPeer, string $caption = ''): int
    {
        return self::getFacadeRoot()->publishByReference($bucket, $msgId, $targetPeer, $caption);
    }
}
