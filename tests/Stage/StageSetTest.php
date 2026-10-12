<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Stage;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Stage\StageSet;
use MeRezaRezaei\Teleframe\Tests\Stage\Fixtures\Forms\SignupFormRequest;
use PHPUnit\Framework\TestCase;

/**
 * StageSet (Phase 5e): both declaration forms — array map and fluent builder —
 * converge on the SAME compiled shape; `compile()` freezes; the final step
 * defaults to the last stage when none is flagged; `pendingField()` walks the
 * capture queue; invalid definitions throw.
 */
final class StageSetTest extends TestCase
{
    public function test_define_array_compiles_declarative_shape(): void
    {
        $set = StageSet::define('onboarding', [
            'email' => ['steps' => ['email']],
            'confirm' => ['steps' => ['tos'], 'final_step' => true],
        ])->compile();

        self::assertSame('onboarding', $set->name());
        self::assertSame(['email', 'confirm'], $set->stageNames());
        self::assertSame(['email'], $set->stepsOf('email'));
        self::assertSame('confirm', $set->finalStep());
        self::assertSame(['steps' => ['email'], 'final_step' => false], $set->stages()['email']);
        self::assertTrue($set->isFinal('confirm'));
        self::assertFalse($set->isFinal('email'));
        self::assertSame('confirm', $set->stageAfter('email'));
        self::assertNull($set->stageAfter('confirm'));
        self::assertSame('email', $set->firstStage());
    }

    public function test_fluent_builder_matches_array_form(): void
    {
        $fluent = StageSet::define('onboarding')
            ->submit('/forms/onboarding', SignupFormRequest::class, 'POST')
            ->stage('email')->field('email')->rule('email')->prompt('ask_email')->error('email_bad')
            ->final('confirm')->field('tos')->rule('accepted')->prompt('ask_tos')->error('tos_bad')
            ->compile();

        $array = StageSet::define('onboarding', [
            'email' => ['steps' => ['email'], 'prompt' => 'ask_email', 'error' => 'email_bad'],
            'confirm' => ['steps' => ['tos'], 'prompt' => 'ask_tos', 'error' => 'tos_bad', 'final_step' => true],
        ])
            ->submit('/forms/onboarding', SignupFormRequest::class, 'POST')
            ->compile();

        self::assertSame($array->stages(), $fluent->stages());
        self::assertSame('confirm', $fluent->finalStep());
        self::assertSame('/forms/onboarding', $fluent->submitUri());
        self::assertSame('POST', $fluent->submitMethod());
        self::assertSame(SignupFormRequest::class, $fluent->submitFormRequest());
        self::assertTrue($fluent->hasSubmit());
        self::assertSame(['email'], $fluent->rulesFor('email', 'email'));
        self::assertSame(['accepted'], $fluent->rulesFor('confirm', 'tos'));
    }

    public function test_compile_freezes_the_set(): void
    {
        $set = StageSet::define('x', ['a' => ['steps' => ['a']]])->compile();

        $this->expectException(InvalidArgumentException::class);
        $set->stage('b');
    }

    public function test_final_stage_defaults_to_last_when_unflagged(): void
    {
        $set = StageSet::define('x', [
            'a' => ['steps' => ['a']],
            'b' => ['steps' => ['b']],
        ])->compile();

        self::assertSame('b', $set->finalStep());
    }

    public function test_pending_field_walks_stages_in_order(): void
    {
        $set = StageSet::define('x', [
            'a' => ['steps' => ['a', 'b']],
            'c' => ['steps' => ['c']],
        ])->compile();

        self::assertSame(['stage' => 'a', 'field' => 'a', 'complete' => false], $set->pendingField('a', []));
        self::assertSame(['stage' => 'a', 'field' => 'b', 'complete' => false], $set->pendingField('a', ['a' => 1]));
        self::assertSame(['stage' => 'c', 'field' => 'c', 'complete' => false], $set->pendingField('a', ['a' => 1, 'b' => 2]));
        self::assertSame(['stage' => 'c', 'field' => null, 'complete' => true], $set->pendingField('c', ['a' => 1, 'b' => 2, 'c' => 3]));
    }

    public function test_pending_field_from_suspended_complete_state_rewalks(): void
    {
        $set = StageSet::define('x', [
            'a' => ['steps' => ['a']],
            'b' => ['steps' => ['b']],
        ])->compile();

        self::assertSame(['stage' => 'a', 'field' => 'a', 'complete' => false], $set->pendingField(null, []));
        self::assertSame(['stage' => 'b', 'field' => null, 'complete' => true], $set->pendingField(null, ['a' => 1, 'b' => 2]));
    }

    public function test_expects_defaults_to_entry_constructor(): void
    {
        $set = StageSet::define('updateNewMessage', ['email' => ['steps' => ['email']]])->compile();

        self::assertSame('updateNewMessage', $set->expectsFor('email', 'email'));

        $pinned = StageSet::define('start %s', ['name' => ['steps' => ['name']]])
            ->stage('email')->field('user')->expects('join %s')
            ->compile();

        self::assertSame('join %s', $pinned->expectsFor('email', 'user'));
    }

    public function test_rule_defaults_and_explicit_rules(): void
    {
        $set = StageSet::define('x', ['a' => ['steps' => ['a']]])->compile();

        self::assertSame(['required', 'string'], $set->rulesFor('a', 'a'));
        self::assertSame(['required', 'string'], $set->rulesFor('unknown-stage', 'a'));

        $piped = StageSet::define('x')
            ->stage('a')->field('a')->rule('required|array')
            ->compile();

        self::assertSame(['required', 'array'], $piped->rulesFor('a', 'a'));
    }

    public function test_invalid_definitions_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StageSet::define('x')->compile();
    }

    public function test_stage_without_fields_is_rejected_at_compile(): void
    {
        $set = StageSet::define('x')
            ->stage('a')
            ->stage('b')->field('b');

        $this->expectException(InvalidArgumentException::class);
        $set->compile();
    }

    public function test_duplicate_stage_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StageSet::define('x')
            ->stage('a')->field('a')
            ->stage('a')->field('b');
    }

    public function test_duplicate_field_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StageSet::define('x')
            ->stage('a')->field('a')->field('a');
    }

    public function test_second_final_stage_is_rejected(): void
    {
        $set = StageSet::define('x', [
            'a' => ['steps' => ['a'], 'final_step' => true],
            'b' => ['steps' => ['b']],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $set->final('b');
    }

    public function test_explicit_final_stays_flagged_when_stages_are_appended_after_it(): void
    {
        $set = StageSet::define('x', [
            'a' => ['steps' => ['a']],
            'b' => ['steps' => ['b'], 'final_step' => true],
        ])
            ->stage('c')->field('c')
            ->compile();

        self::assertSame('b', $set->finalStep());
        self::assertTrue($set->isFinal('b'));
        self::assertFalse($set->isFinal('c'));
        self::assertSame(['stage' => 'b', 'field' => null, 'complete' => true], $set->pendingField('a', ['a' => 1, 'b' => 2]));
    }

    public function test_submit_error_template_default_and_override(): void
    {
        $set = StageSet::define('x')
            ->submit('/forms/x', SignupFormRequest::class)
            ->submitError('stages.bad')
            ->stage('a')->field('a')
            ->compile();

        self::assertSame('stages.bad', $set->submitErrorTemplate());
        self::assertFalse(StageSet::define('y', ['a' => ['steps' => ['a']]])->compile()->hasSubmit());
    }
}