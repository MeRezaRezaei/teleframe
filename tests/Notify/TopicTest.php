<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\Topic;
use PHPUnit\Framework\TestCase;

/**
 * Topic = predefined log level + placement (design §4): predefined rows,
 * defaults() preload, define()/resolve()/topicFor() semantics. resolve() and
 * topicFor() are TOTAL — unknown names synthesize a minimal row and the loud
 * NotifyException is raised at send()/queue() time (covered in
 * PendingNotifyNotifyServiceTest, which exercises the full pipeline).
 */
final class TopicTest extends TestCase
{
    public function test_predefined_ships_the_eight_level_topics_plus_domain_rows(): void
    {
        $rows = Topic::predefined();
        $names = array_map(static fn (Topic $topic): string => $topic->name(), $rows);

        self::assertSame([
            'app.debug',
            'app.info',
            'app.notice',
            'app.warning',
            'app.error',
            'app.critical',
            'app.alert',
            'app.emergency',
            'backup',
            'backup.missing',
            'report',
        ], $names);
        self::assertCount(11, $rows);
        self::assertSame(Level::Error, $rows[4]->level());
        self::assertSame(5, $rows[4]->threadId());
    }

    public function test_defaults_preloads_the_registry_with_the_predefined_rows(): void
    {
        $registry = Topic::defaults();

        self::assertCount(11, $registry->topics());
        self::assertSame('app.error', $registry->resolve('app.error')->name());
        self::assertSame(5, $registry->resolve('app.error')->threadId());
        self::assertSame('backup.missing', $registry->resolve('backup.missing')->name());
    }

    public function test_define_adds_and_overwrites_rows(): void
    {
        $registry = Topic::defaults()
            ->define('app.error', Level::Critical, '', '@ops', 99)
            ->define('custom.topic', Level::Notice, 'host row');

        self::assertSame(Level::Critical, $registry->resolve('app.error')->level());
        self::assertSame(99, $registry->resolve('app.error')->threadId());
        self::assertSame('@ops', $registry->resolve('app.error')->chat());
        self::assertSame(Level::Notice, $registry->resolve('custom.topic')->level());
        self::assertSame('host row', $registry->resolve('custom.topic')->description());
    }

    public function test_resolve_is_total_for_unknown_names(): void
    {
        $row = (new Topic)->resolve('does.not.exist');

        self::assertSame('does.not.exist', $row->name());
        self::assertNull($row->chat());
        self::assertNull($row->threadId());
        self::assertSame(Level::Info, $row->level());
    }

    public function test_topic_for_level_maps_to_the_app_topic(): void
    {
        $topic = Topic::defaults()->topicFor(Level::Warning);

        self::assertSame('app.warning', $topic->name());
        self::assertSame(Level::Warning, $topic->level());
        self::assertSame(4, $topic->threadId());
    }

    public function test_topic_for_level_synthesizes_when_the_registry_lacks_the_row(): void
    {
        $topic = (new Topic)->topicFor(Level::Alert);

        self::assertSame('app.alert', $topic->name());
        self::assertSame(Level::Alert, $topic->level());
    }

    public function test_chat_or_returns_the_row_chat_or_the_given_fallback(): void
    {
        // Predefined rows carry no explicit chat (null = host default_chat).
        self::assertSame('@fallback', Topic::defaults()->resolve('app.info')->chatOr('@fallback'));
        self::assertSame('@ops', (new Topic)->define('custom', Level::Info, '', '@ops')->resolve('custom')->chatOr('@fallback'));
    }
}
