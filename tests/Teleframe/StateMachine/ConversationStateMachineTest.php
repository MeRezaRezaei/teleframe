<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Teleframe\StateMachine;

use MeRezaRezaei\Teleframe\StateMachine\ConversationStateMachine;
use MeRezaRezaei\Teleframe\StateMachine\StateTransition;
use MeRezaRezaei\Teleframe\StateMachine\TelegramPattern;
use PHPUnit\Framework\TestCase;

final class ConversationStateMachineTest extends TestCase
{
    public function test_initial_state_is_initial(): void
    {
        $machine = new ConversationStateMachine;
        self::assertSame(ConversationStateMachine::STATE_INITIAL, $machine->getState('peer1'));
    }

    public function test_valid_transition_initial_to_active(): void
    {
        $machine = new ConversationStateMachine;
        $next = $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE);
        self::assertSame('active', $next);
        self::assertSame('active', $machine->getState('peer1'));
    }

    public function test_invalid_transition_throws(): void
    {
        $machine = new ConversationStateMachine;
        $this->expectException(\InvalidArgumentException::class);
        $machine->transition('peer1', ConversationStateMachine::STATE_COMPLETED);
    }

    public function test_query_guarded_transition(): void
    {
        $machine = new ConversationStateMachine;
        $machine->registerQuery('has_gender', static fn (array $ctx): bool => ($ctx['gender'] ?? null) !== null);
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE);

        $transition = new StateTransition(
            ConversationStateMachine::STATE_ACTIVE,
            ConversationStateMachine::STATE_WAITING_INPUT,
            query: 'has_gender',
        );
        // guard fails without gender
        $this->expectException(\LogicException::class);
        $machine->transition('peer1', $transition, []);
    }

    public function test_query_guarded_transition_succeeds_with_context(): void
    {
        $machine = new ConversationStateMachine;
        $machine->registerQuery('has_gender', static fn (array $ctx): bool => ($ctx['gender'] ?? null) !== null);
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE);

        $transition = new StateTransition(
            ConversationStateMachine::STATE_ACTIVE,
            ConversationStateMachine::STATE_WAITING_INPUT,
            query: 'has_gender',
        );
        $next = $machine->transition('peer1', $transition, ['gender' => 'female']);
        self::assertSame('waiting_input', $next);
    }

    public function test_callable_guard(): void
    {
        $machine = new ConversationStateMachine;
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE);
        $transition = new StateTransition(
            'active',
            'completed',
            guard: static fn (array $ctx): bool => ($ctx['done'] ?? false) === true,
        );
        $this->expectException(\LogicException::class);
        $machine->transition('peer1', $transition, ['done' => false]);
    }

    public function test_pattern_detection_channel(): void
    {
        $pattern = TelegramPattern::detect(['chat' => ['type' => 'channel']]);
        self::assertSame(TelegramPattern::Channel, $pattern);
        self::assertTrue($pattern->isOrdered());
        self::assertTrue($pattern->isQueue());
    }

    public function test_pattern_detection_group(): void
    {
        $pattern = TelegramPattern::detect(['chat' => ['type' => 'supergroup']]);
        self::assertSame(TelegramPattern::Group, $pattern);
        self::assertTrue($pattern->isBroadcast());
    }

    public function test_pattern_detection_bot_assistant(): void
    {
        $pattern = TelegramPattern::detect(['from' => ['is_bot' => true]]);
        self::assertSame(TelegramPattern::BotAssistant, $pattern);
        self::assertTrue($pattern->isDeduplicated());
    }

    public function test_pattern_detection_private_default(): void
    {
        $pattern = TelegramPattern::detect(['chat' => ['type' => 'private']]);
        self::assertSame(TelegramPattern::Private, $pattern);
        self::assertTrue($pattern->isStateful());
    }

    public function test_per_peer_pattern_storage(): void
    {
        $machine = new ConversationStateMachine;
        $machine->setPattern('peer1', TelegramPattern::Channel);
        self::assertSame(TelegramPattern::Channel, $machine->getPattern('peer1'));
        self::assertSame(TelegramPattern::Private, $machine->getPattern('peer2'));
    }

    public function test_binary_base64_handling(): void
    {
        $machine = new ConversationStateMachine;
        $payload = base64_encode('# Hello');
        self::assertTrue($machine->validateBinary($payload));
        $decoded = $machine->handleBinary('peer1', $payload);
        self::assertSame('# Hello', $decoded);
        self::assertSame('# Hello', $machine->getStateData('peer1')['binary_decoded']);
    }

    public function test_transition_with_binary_context(): void
    {
        $machine = new ConversationStateMachine;
        $payload = base64_encode('code');
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE, ['binary' => $payload]);
        self::assertSame('code', $machine->getStateData('peer1')['binary_decoded']);
    }

    public function test_invalid_binary_throws(): void
    {
        $machine = new ConversationStateMachine;
        $this->expectException(\InvalidArgumentException::class);
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE, ['binary' => 'not-base64!!!']);
    }

    public function test_valid_transitions_list(): void
    {
        $machine = new ConversationStateMachine;
        $valid = $machine->getValidTransitions('initial');
        self::assertContains('active', $valid);
        self::assertTrue($machine->isValidTransition('initial', 'active'));
        self::assertFalse($machine->isValidTransition('initial', 'completed'));
    }

    public function test_reset_clears_state(): void
    {
        $machine = new ConversationStateMachine;
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE);
        $machine->reset('peer1');
        self::assertSame('initial', $machine->getState('peer1'));
    }

    public function test_state_transition_transform(): void
    {
        $machine = new ConversationStateMachine;
        $machine->transition('peer1', ConversationStateMachine::STATE_ACTIVE);
        $transition = new StateTransition(
            'active',
            'completed',
            transform: static fn (array $data): array => array_merge($data, ['transformed' => true]),
        );
        $machine->transition('peer1', $transition);
        self::assertTrue($machine->getStateData('peer1')['transformed']);
    }
}
