<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Stage;

use InvalidArgumentException;

/**
 * Declarative stage value object (Phase 5e): stages = "form fields, telegram
 * paced". A stage is a named step that carries an ordered list of form-field
 * keys (`steps`); at most one stage is flagged `final_step`. Everything is
 * plain arrays — the Q17 "never serialized objects" rule — so the same set
 * can be persisted, diffed and replayed across processes.
 *
 * Two declaration forms converge on one compiled shape:
 *
 *   1. Array form:
 *
 *      StageSet::define('onboarding', [
 *          'email'   => ['steps' => ['email']],
 *          'profile' => ['steps' => ['name', 'bio']],
 *          'confirm' => ['steps' => ['tos'], 'final_step' => true],
 *      ]);
 *
 *   2. Fluent form:
 *
 *      StageSet::define('onboarding')
 *          ->submit('/forms/onboarding', SignupFormRequest::class)
 *          ->stage('email')->field('email')->expects('updateNewMessage')
 *              ->rule('email')->prompt('stages.ask_email')->error('stages.email_bad')
 *          ->profile()->field('name')->field('bio')   // not shown above
 *          ->final('confirm')->field('tos')->rule('accepted')
 *          ->compile();
 *
 * `compile()` freezes the set: any builder call afterwards throws, so a
 * compiled set is immutable and safe to hand to the registry. When no stage
 * carries `final_step`, the LAST declared stage acts as the final step
 * (documented convenience).
 *
 * Per-stage validation (template-shaped, Q13-style): `prompt(name)` is the
 * message template sent when the stage starts, `error(name)` the failure
 * template sent when a captured field fails that stage's rules. A field may
 * pin `expects(pattern)` — the constructor pattern that carries its answer;
 * the default is the set's own name (entry constructor). `submit(uri,
 * formRequest)` pins the SAME Laravel route the web form posts to: the
 * teleframe leg sends its final submit through that identical route
 * (Q20), reusing the identical `rules()`.
 */
final class StageSet
{
    /**
     * @var array<string, array{
     *     steps: list<string>,
     *     final_step: bool,
     *     expects: array<string, string>,
     *     rules: array<string, array<int, mixed>>,
     *     prompt: ?string,
     *     error: ?string
     * }>
     */
    private array $stages = [];

    private ?string $cursor = null;

    private ?string $fieldCursor = null;

    private bool $compiled = false;

    private string $submitUri = '';

    private string $submitMethod = 'POST';

    private string $submitFormRequest = '';

    private string $submitErrorTemplate = 'stages.invalid_submit';

    private function __construct(
        private readonly string $name,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('Stage set name must be a non-empty string.');
        }
    }

    /**
     * Begin a set. `$stages` (optional) is the declarative name → shape map:
     *
     *     ['email' => ['steps' => ['email']], 'confirm' => ['steps' => ['tos'], 'final_step' => true]]
     *
     * The fluent `stage()/field()/.../compile()` path may follow.
     *
     * @param array<string, mixed> $stages
     */
    public static function define(string $name, array $stages = []): self
    {
        $set = new self($name);

        foreach ($stages as $stageName => $entry) {
            if (! is_array($entry)) {
                throw new InvalidArgumentException("Stage [{$stageName}] must map to a stage shape array.");
            }
            $set->stage($stageName);

            $steps = $entry['steps'] ?? [];
            foreach ($steps as $step) {
                $set->field((string) $step);
            }
            if (($entry['final_step'] ?? false) === true) {
                $set->markFinal($stageName);
            }
            if (isset($entry['prompt']) && is_string($entry['prompt'])) {
                $set->prompt($entry['prompt']);
            }
            if (isset($entry['error']) && is_string($entry['error'])) {
                $set->error($entry['error']);
            }
        }

        return $set;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** Begin a stage; `$name` must be unique. Returns $this for fluency. */
    public function stage(string $name): self
    {
        $this->assertMutable();

        if ($name === '') {
            throw new InvalidArgumentException('Stage name must be a non-empty string.');
        }
        if (isset($this->stages[$name])) {
            throw new InvalidArgumentException("Stage [{$name}] is declared twice in set [{$this->name}].");
        }

        $this->stages[$name] = [
            'steps' => [],
            'final_step' => false,
            'expects' => [],
            'rules' => [],
            'prompt' => null,
            'error' => null,
        ];
        $this->cursor = $name;
        $this->fieldCursor = null;

        return $this;
    }

    /** Append a form-field key to the current stage. */
    public function field(string $field): self
    {
        $this->assertMutable();

        if ($this->cursor === null) {
            throw new InvalidArgumentException('field() requires an active stage(); call stage() first.');
        }
        if ($field === '') {
            throw new InvalidArgumentException('Field name must be a non-empty string.');
        }
        if ($this->ownsField($field)) {
            throw new InvalidArgumentException("Field [{$field}] is declared twice in set [{$this->name}].");
        }

        $this->stages[$this->cursor]['steps'][] = $field;
        $this->fieldCursor = $field;

        return $this;
    }

    /** Constructor pattern that carries this field's answer (default = set name). */
    public function expects(string $pattern): self
    {
        $this->assertMutable();

        $stage = $this->assertFieldCursor();
        if ($pattern === '') {
            throw new InvalidArgumentException('expects() needs a non-empty constructor pattern.');
        }
        $this->stages[$stage]['expects'][$this->fieldCursor ?? ''] = $pattern;

        return $this;
    }

    /** Illuminate-Validation rules for the current field (default: required string). */
    public function rule(string|array $rule): self
    {
        $this->assertMutable();

        $stage = $this->assertFieldCursor();
        $this->stages[$stage]['rules'][$this->fieldCursor ?? ''] = $this->normalizeRules($rule);

        return $this;
    }

    /** Message template sent when this stage becomes current. */
    public function prompt(string $template): self
    {
        $this->assertMutable();
        $this->assertStageCursor();
        if ($template === '') {
            throw new InvalidArgumentException('prompt() needs a non-empty template name.');
        }
        $this->stages[$this->cursor ?? '']['prompt'] = $template;

        return $this;
    }

    /** Message template sent when a captured field fails this stage's rules. */
    public function error(string $template): self
    {
        $this->assertMutable();
        $this->assertStageCursor();
        if ($template === '') {
            throw new InvalidArgumentException('error() needs a non-empty template name.');
        }
        $this->stages[$this->cursor ?? '']['error'] = $template;

        return $this;
    }

    /** Mark `$name` (current or to-be-created) as the flow's final step. */
    public function final(string $name): self
    {
        $this->stage($name);
        $this->markFinal($name);

        return $this;
    }

    /**
     * Pin the SAME Laravel route + FormRequest the web form posts to (the
     * roadmap gate's "one controller + FormRequest serves both legs"): the
     * teleframe leg validates its accumulated data against the identical
     * `rules()` and sub-dispatches an in-process `Request::create` to this
     * route (Q20).
     */
    public function submit(string $uri, string $formRequest, string $method = 'POST'): self
    {
        $this->assertMutable();

        if ($uri === '') {
            throw new InvalidArgumentException('submit() needs a non-empty route uri.');
        }
        if ($formRequest === '') {
            throw new InvalidArgumentException('submit() needs a FormRequest class name.');
        }
        $this->submitUri = $uri;
        $this->submitMethod = strtoupper($method);
        $this->submitFormRequest = $formRequest;

        return $this;
    }

    /** Failure template sent when the accumulated data fails the FormRequest rules. */
    public function submitError(string $template): self
    {
        $this->assertMutable();
        if ($template === '') {
            throw new InvalidArgumentException('submitError() needs a non-empty template name.');
        }
        $this->submitErrorTemplate = $template;

        return $this;
    }

    /**
     * Freeze the set. Immutable afterwards: any builder call throws.
     */
    public function compile(): self
    {
        $this->assertCompilable();
        $this->compiled = true;

        return $this;
    }

    /** Ordered stage names. */
    public function stageNames(): array
    {
        return array_keys($this->stages);
    }

    public function firstStage(): ?string
    {
        $names = $this->stageNames();

        return $names[0] ?? null;
    }

    public function hasStage(string $stage): bool
    {
        return isset($this->stages[$stage]);
    }

    /** The next stage after `$stage`, or null when it is the last. */
    public function stageAfter(string $stage): ?string
    {
        $names = $this->stageNames();
        $index = array_search($stage, $names, true);

        return $index !== false ? ($names[$index + 1] ?? null) : null;
    }

    public function isFinal(string $stage): bool
    {
        return $this->finalStep() === $stage;
    }

    /**
     * The final step: the stage flagged `final_step`, else the last declared
     * stage (documented convenience).
     */
    public function finalStep(): ?string
    {
        foreach ($this->stages as $name => $entry) {
            if ($entry['final_step'] === true) {
                return $name;
            }
        }

        $names = $this->stageNames();
        $last = end($names);

        return $last !== false ? $last : null;
    }

    /** The canonical name → shape map (insertion order). */
    public function stages(): array
    {
        $out = [];
        foreach ($this->stages as $name => $entry) {
            $shape = [
                'steps' => $entry['steps'],
                'final_step' => $entry['final_step'],
            ];
            if ($entry['prompt'] !== null) {
                $shape['prompt'] = $entry['prompt'];
            }
            if ($entry['error'] !== null) {
                $shape['error'] = $entry['error'];
            }
            $out[$name] = $shape;
        }

        return $out;
    }

    /** @return list<string> */
    public function stepsOf(string $stage): array
    {
        return $this->stages[$stage]['steps'] ?? [];
    }

    public function promptFor(string $stage): ?string
    {
        $entry = $this->stages[$stage] ?? null;

        return $entry !== null ? $entry['prompt'] : null;
    }

    public function errorFor(string $stage): ?string
    {
        $entry = $this->stages[$stage] ?? null;

        return $entry !== null ? $entry['error'] : null;
    }

    /**
     * The constructor pattern the current field's answer must carry. Defaults
     * to the set's own name (the entry constructor).
     */
    public function expectsFor(string $stage, string $field): string
    {
        $entry = $this->stages[$stage] ?? null;
        if ($entry === null) {
            return $this->name;
        }

        return $entry['expects'][$field] ?? $this->name;
    }

    /**
     * Validation rules for one field. Unconfigured fields default to
     * `['required', 'string']` (a missing text answer is a failure).
     *
     * @return array<int, mixed>
     */
    public function rulesFor(string $stage, string $field): array
    {
        $entry = $this->stages[$stage] ?? null;

        return $entry['rules'][$field] ?? ['required', 'string'];
    }

    /**
     * Fields of `$stage` not yet present in `$data` — the capture queue.
     *
     * @param array<string, mixed> $data
     *
     * @return list<string>
     */
    public function remainingFields(string $stage, array $data): array
    {
        $remaining = [];
        foreach ($this->stepsOf($stage) as $field) {
            if (! array_key_exists($field, $data)) {
                $remaining[] = $field;
            }
        }

        return $remaining;
    }

    /**
     * The next field the flow is waiting for, walking from `$currentStage`
     * (nullable → the first stage) over the ordered stages and skipping
     * already-collected fields (data is cumulative). `complete: true` means
     * the final stage is fully captured — the flow is ready to submit.
     *
     * @param array<string, mixed> $data
     *
     * @return array{stage: string, field: ?string, complete: bool}
     */
    public function pendingField(?string $currentStage, array $data): array
    {
        $names = $this->stageNames();
        $index = $currentStage !== null ? array_search($currentStage, $names, true) : false;
        if ($index === false) {
            $index = 0;
        }

        for ($i = $index; $i < count($names); ++$i) {
            $stage = $names[$i];
            $remaining = $this->remainingFields($stage, $data);
            if ($remaining !== []) {
                return ['stage' => $stage, 'field' => $remaining[0], 'complete' => false];
            }
            if ($this->isFinal($stage)) {
                return ['stage' => $stage, 'field' => null, 'complete' => true];
            }
        }

        return ['stage' => (string) $currentStage, 'field' => null, 'complete' => false];
    }

    public function submitUri(): string
    {
        return $this->submitUri;
    }

    public function submitMethod(): string
    {
        return $this->submitMethod;
    }

    public function submitFormRequest(): string
    {
        return $this->submitFormRequest;
    }

    public function submitErrorTemplate(): string
    {
        return $this->submitErrorTemplate;
    }

    /** True when the set declares a submit route + FormRequest (Q20 sub-dispatch). */
    public function hasSubmit(): bool
    {
        return $this->submitUri !== '' && $this->submitFormRequest !== '';
    }

    private function markFinal(string $name): void
    {
        $this->assertMutable();

        $already = $this->finalStep();
        if ($already !== null && $already !== $name && $this->hasExplicitFinal()) {
            throw new InvalidArgumentException(
                "Set [{$this->name}] already has a final stage ({$already}); only one final_step is allowed.",
            );
        }

        $this->stages[$name]['final_step'] = true;
    }

    private function hasExplicitFinal(): bool
    {
        foreach ($this->stages as $entry) {
            if ($entry['final_step'] === true) {
                return true;
            }
        }

        return false;
    }

    private function assertStageCursor(): void
    {
        if ($this->cursor === null) {
            throw new InvalidArgumentException('No active stage; call stage() first.');
        }
    }

    private function assertFieldCursor(): string
    {
        $this->assertStageCursor();
        if ($this->fieldCursor === null) {
            throw new InvalidArgumentException('No active field; call field() first.');
        }

        return (string) $this->cursor;
    }

    /** @param string|array<int, mixed> $rule */
    private function normalizeRules(string|array $rule): array
    {
        $rules = is_string($rule) ? explode('|', $rule) : array_values($rule);

        return array_values(array_filter($rules, static fn (mixed $r): bool => $r !== null && $r !== ''));
    }

    private function ownsField(string $field): bool
    {
        foreach ($this->stages as $entry) {
            if (in_array($field, $entry['steps'], true)) {
                return true;
            }
        }

        return false;
    }

    private function assertMutable(): void
    {
        if ($this->compiled) {
            throw new InvalidArgumentException(
                "Stage set [{$this->name}] is already compiled; it is immutable.",
            );
        }
    }

    private function assertCompilable(): void
    {
        if ($this->stages === []) {
            throw new InvalidArgumentException("Stage set [{$this->name}] declares no stages.");
        }
        foreach ($this->stages as $name => $entry) {
            if ($entry['steps'] === []) {
                throw new InvalidArgumentException("Stage [{$name}] declares no fields (steps).");
            }
        }
        if ($this->submitFormRequest !== '' && ! class_exists($this->submitFormRequest)) {
            throw new InvalidArgumentException(
                "Stage set [{$this->name}] submit FormRequest [{$this->submitFormRequest}] does not exist.",
            );
        }
    }
}