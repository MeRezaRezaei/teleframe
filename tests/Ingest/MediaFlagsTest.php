<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\MediaFlags;
use PHPUnit\Framework\TestCase;

class MediaFlagsTest extends TestCase
{
    public function test_empty_payload_returns_zero(): void
    {
        $this->assertSame(0, MediaFlags::fromPayload(['_' => 'message']));
    }

    public function test_photo_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaPhoto'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'photo'));
        $this->assertFalse(MediaFlags::has($flags, 'video'));
    }

    public function test_video_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaVideo'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'video'));
    }

    public function test_document_with_sticker(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => [
                '_' => 'messageMediaDocument',
                'document' => [
                    '_' => 'document',
                    'attributes' => [
                        ['_' => 'documentAttributeSticker'],
                    ],
                ],
            ],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'document'));
        $this->assertTrue(MediaFlags::has($flags, 'sticker'));
        $this->assertFalse(MediaFlags::has($flags, 'photo'));
    }

    public function test_voice_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaAudio', 'audio' => ['_' => 'audio', 'voice' => true]],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'voice'));
    }

    public function test_location_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaGeo'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'location'));
    }

    public function test_poll_detected(): void
    {
        $flags = MediaFlags::fromPayload([
            '_' => 'message',
            'media' => ['_' => 'messageMediaPoll'],
        ]);
        $this->assertTrue(MediaFlags::has($flags, 'poll'));
    }
}
