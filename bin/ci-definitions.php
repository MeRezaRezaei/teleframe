<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/**
 * THE TWO CI DIALECTS, NORMALIZED TO ONE SHAPE. Shared by the gate and its test.
 *
 * WHY THIS FILE EXISTS
 * --------------------
 * A repository that runs CI on GitLab writes one dialect: every top-level key is
 * a job, its commands are that job's `script:`. A repository that runs CI on
 * GitHub Actions writes the other: jobs live under a nested `jobs:` map keyed by
 * job id, and their commands are the `run:` of each entry in the job's `steps:`
 * list. There is no `script:` key in a workflow at all.
 *
 * Commit ddbdef18 ("ci: move CI/CD to GitHub Actions; GitLab becomes the
 * private/public split") moved the shard jobs. Everything that still read
 * `.gitlab-ci.yml` by that literal filename went to zero the moment the move
 * landed — and a check that reads zero of what it is checking does not go red.
 * It goes GREEN having proved nothing, which is strictly worse than going red,
 * because a red gate is read and a silent one is not.
 *
 * So the CI surface is DISCOVERED here, never named, and both dialects are
 * flattened onto the same shape. The consumers of this file —
 * bin/verify-shard-partition.php and tests/Concerns/ShardPartitionTest.php —
 * hold the CLAIMS; this file holds the VOCABULARY. That split is the point:
 * the vocabulary is the thing that rots, and there is now exactly one of it.
 *
 * DISCOVERY, NOT A HARDcoded LIST
 * ------------------------------
 * `.github/workflows/*.{yml,yaml}` plus `.gitlab-ci.yml`, whichever exist.
 * `glob()` order is filesystem-dependent, so the workflows are sorted — a gate
 * whose report reorders itself between runs is harder to trust than one that
 * does not. If NO definition can be read, the callers fail loudly rather than
 * passing on an empty set; see `ciDefinitions()` returning `[]`.
 *
 * WHY THE ACCESSORS BELOW PROBE BOTH SHAPES INSTEAD OF SWITCHING ON THE DIALECT
 * ---------------------------------------------------------------------------
 * A caller knows the job id it wants, not which file spells it. So each
 * accessor answers "what does this job body say?" by looking for every spelling
 * a job may legitimately use — `if` AND `when`, `timeout-minutes` AND `timeout`,
 * `needs` as a list of strings AND as a list of mappings — and returns the
 * normalized answer, or null / false when the job body says neither. Probing is
 * strictly safer than switching on a dialect label: a job that acquires an
 * unfamiliar-but-valid spelling is still read, where a label would route it to
 * the wrong branch and report nothing at all. A check that reports nothing is
 * the failure this file was written to end.
 *
 * ZERO REGEX, like the rest of bin/. Every matcher below is a string function.
 */

/**
 * Every CI definition file on disk, in a deterministic order.
 *
 * @return list<string> absolute paths
 */
function ciDefinitionPaths(string $root): array
{
    $paths = [];

    // GitLab: the single pipeline file at the repo root, if it is still there.
    // It may legitimately coexist with the workflows — this repository ships
    // both today, because the GitLab file holds the credential-bearing lanes
    // that must never be published.
    $gitlab = $root.'/.gitlab-ci.yml';

    if (is_file($gitlab)) {
        $paths[] = $gitlab;
    }

    $workflows = array_merge(
        glob($root.'/.github/workflows/*.yml') ?: [],
        glob($root.'/.github/workflows/*.yaml') ?: [],
    );
    sort($workflows);

    return array_merge($paths, $workflows);
}

/**
 * A workflow is Actions-shaped by where it lives, not by a name convention.
 *
 * Reported for diagnostics only — the accessors below probe both shapes — but
 * it is the honest answer to "which parser read this", so a failure message can
 * say so instead of leaving the reader to guess.
 */
function isActionsWorkflow(string $path): bool
{
    return str_contains($path, '/.github/workflows/');
}

/**
 * Every CI definition this repository ships, in whichever dialect it is written.
 *
 * Each entry is:
 *
 *   path      the file it came from, relative to the repository root
 *   dialect   'actions' or 'gitlab', for diagnostics
 *   jobs      job id => the job body, verbatim, exactly as the dialect wrote it
 *   commands  job id => the command text that job executes, both dialects
 *             flattened onto one string
 *
 * A file that will not parse is skipped, and the callers treat "no readable
 * definition" as a FAILURE. That direction is deliberate: a parse error turned
 * into "this file has no jobs" is a false pass, which is the exact inversion of
 * what a gate is for.
 *
 * @return list<array{path: string, dialect: string, jobs: array<string, array>, commands: array<string, string>}>
 */
function ciDefinitions(string $root): array
{
    if (! class_exists(Yaml::class)) {
        return [];
    }

    $definitions = [];

    foreach (ciDefinitionPaths($root) as $path) {
        try {
            // PARSE_CUSTOM_TAGS so GitLab's `!reference` does not abort the parse.
            $document = Yaml::parseFile($path, Yaml::PARSE_CUSTOM_TAGS);
        } catch (Throwable) {
            continue;
        }

        if (! is_array($document)) {
            continue;
        }

        $jobs = isActionsWorkflow($path) ? actionsJobs($document) : gitlabJobs($document);

        $commands = [];
        foreach ($jobs as $id => $body) {
            $commands[$id] = ciJobCommandText($body);
        }

        $definitions[] = [
            'path' => ltrim(str_replace($root, '', $path), '/'),
            'dialect' => isActionsWorkflow($path) ? 'actions' : 'gitlab',
            'jobs' => $jobs,
            'commands' => $commands,
        ];
    }

    return $definitions;
}

/**
 * GitLab jobs: every top-level key that is a mapping carrying a `script`.
 *
 * The reserved top-level keys (`stages`, `variables`, `default`, `cache`,
 * `workflow`, `include`, `image`, `services`) are mappings too, so the test is
 * "has a script" rather than "is not on a hardcoded list" — a hardcoded list
 * would rot the same way the hardcoded filename just did.
 *
 * @return array<string, array> job id => job body
 */
function gitlabJobs(array $document): array
{
    $jobs = [];

    foreach ($document as $name => $body) {
        if (! is_array($body) || ! array_key_exists('script', $body)) {
            continue;
        }

        $jobs[(string) $name] = $body;
    }

    return $jobs;
}

/**
 * Actions jobs: `jobs.<id>`.
 *
 * @return array<string, array> job id => job body
 */
function actionsJobs(array $document): array
{
    $jobs = [];

    foreach (($document['jobs'] ?? []) as $id => $job) {
        if (is_array($job)) {
            $jobs[(string) $id] = $job;
        }
    }

    return $jobs;
}

/**
 * The command text a job executes, whichever dialect wrote it.
 *
 * GitLab: the job's `script:` — a list of lines, or one string.
 * Actions: the `run:` of every step, collected recursively because a step's
 * value may itself be a mapping and a job's command surface is the union of
 * all of them.
 */
function ciJobCommandText(array $job): string
{
    $lines = [];

    foreach ((array) ($job['script'] ?? []) as $line) {
        if (is_string($line)) {
            $lines[] = $line;
        }
    }

    collectRunText($job, $lines);

    return implode("\n", $lines);
}

/**
 * @param  list<string>  $lines  filled in place
 */
function collectRunText(mixed $node, array &$lines): void
{
    if (! is_array($node)) {
        return;
    }

    foreach ($node as $key => $value) {
        if ($key === 'run' && is_string($value)) {
            $lines[] = $value;

            continue;
        }

        collectRunText($value, $lines);
    }
}

/**
 * Every definition that declares the given job id.
 *
 * A job id may legitimately be defined in more than one file, and which one the
 * caller means is not something it should have to guess — so all of them are
 * returned and the caller asserts over every one. A shard defined twice, once
 * with the escape hatch on it, is a real hole, and reading only the first
 * definition would report the clean one.
 *
 * @param  list<array{path: string, dialect: string, jobs: array<string, array>, commands: array<string, string>}>  $definitions
 * @return list<array{path: string, dialect: string, job: array, commands: string}>
 */
function ciJobDefinitions(array $definitions, string $jobId): array
{
    $found = [];

    foreach ($definitions as $definition) {
        if (array_key_exists($jobId, $definition['jobs'])) {
            $found[] = [
                'path' => $definition['path'],
                'dialect' => $definition['dialect'],
                'job' => $definition['jobs'][$jobId],
                'commands' => $definition['commands'][$jobId] ?? '',
            ];
        }
    }

    return $found;
}

/**
 * The shard names a matrix job will actually run.
 *
 * GitLab: `parallel.matrix` is a list of one-key mappings, and the key is
 * conventionally uppercase — `SHARD`. Actions: `strategy.matrix` is a mapping
 * of variable to list, and the variable here is lowercase `shard`. Actions may
 * also express the matrix through `include:` entries instead, so those are read
 * too.
 *
 * Both spellings of a value are accepted — a matrix entry may hold one name or
 * a list of them, and both mean the same thing to the runner that owns it.
 *
 * @return list<string> empty when the job declares no shard matrix at all,
 *                      which is a FAILURE the caller must not read as "none"
 */
function ciJobMatrixShardNames(array $job): array
{
    $names = [];

    // GitLab: parallel.matrix[*].SHARD
    foreach ((array) ($job['parallel']['matrix'] ?? []) as $row) {
        if (! is_array($row)) {
            continue;
        }

        foreach ($row as $key => $value) {
            if (strtolower((string) $key) === 'shard') {
                foreach ((array) $value as $name) {
                    $names[] = (string) $name;
                }
            }
        }
    }

    // Actions: strategy.matrix.shard, and strategy.matrix.include[*].shard
    $matrix = (array) ($job['strategy']['matrix'] ?? []);
    $rows = $matrix;

    if (isset($matrix['include'])) {
        $rows[] = ['include' => $matrix['include']];
    }

    foreach ($rows as $key => $value) {
        if (strtolower((string) $key) === 'shard') {
            foreach ((array) $value as $name) {
                $names[] = (string) $name;
            }
        }
    }

    foreach ((array) ($matrix['include'] ?? []) as $entry) {
        if (! is_array($entry)) {
            continue;
        }

        foreach ($entry as $key => $value) {
            if (strtolower((string) $key) === 'shard') {
                $names[] = (string) $value;
            }
        }
    }

    sort($names);

    return $names;
}

/**
 * The job ids a job declares a dependency on.
 *
 * GitLab: `needs: [{job: verify-shards, artifacts: true}]` — a list of mappings.
 * Actions: `needs: [verify-shards]` — a list of plain strings. GitLab also
 * accepts the bare-string form, so both are read from both.
 *
 * @return list<string>
 */
function ciJobNeeds(array $job): array
{
    $needs = [];

    foreach ((array) ($job['needs'] ?? []) as $need) {
        if (is_string($need)) {
            $needs[] = $need;

            continue;
        }

        if (! is_array($need)) {
            continue;
        }

        // Actions addresses an output by `id`; GitLab addresses a job by `job`.
        foreach (['job', 'id'] as $key) {
            if (isset($need[$key]) && is_string($need[$key])) {
                $needs[] = $need[$key];
            }
        }
    }

    return $needs;
}

/**
 * The job's run CONDITION, normalized so both dialects compare equal.
 *
 * Actions `if: ${{ always() }}` and GitLab `when: always` both mean "run
 * regardless of outcome", and both must be recognizable as the same condition
 * or a gate comparing them is comparing two dialects' punctuation instead of
 * their meaning. `${{ }}`, whitespace and the trailing `()` are stripped.
 *
 * null when the job declares no condition, which is the default: run when the
 * pipeline runs.
 */
function ciJobCondition(array $job): ?string
{
    foreach (['if', 'when'] as $key) {
        $value = $job[$key] ?? null;

        if (! is_string($value)) {
            continue;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace('{{', '', $normalized);
        $normalized = str_replace('}}', '', $normalized);
        $normalized = str_replace('(', '', $normalized);
        $normalized = str_replace(')', '', $normalized);

        $normalized = trim(str_replace(' ', '', $normalized));

        if ($normalized !== '') {
            return $normalized;
        }
    }

    return null;
}

/**
 * The job's timeout, in whatever unit its dialect uses, as a string.
 *
 * GitLab writes `timeout: 45m`; Actions writes `timeout-minutes: 45`. Only the
 * PRESENCE is compared by the gates — a job with no timeout inherits its
 * runner's default, which on a shared runner is "forever".
 */
function ciJobTimeout(array $job): ?string
{
    foreach (['timeout-minutes', 'timeout'] as $key) {
        if (isset($job[$key]) && (is_string($job[$key]) || is_int($job[$key]) || is_float($job[$key]))) {
            return (string) $job[$key];
        }
    }

    return null;
}

/**
 * Every way this job — or any of its steps — is permitted to fail quietly.
 *
 * The dialects disagree on the spelling and agree completely on the escape:
 *
 *   GitLab    `allow_failure: true` on the job. `exit_codes` is the same escape
 *             with more steps.
 *   Actions   `continue-on-error: true`, on the JOB or on any single STEP. A
 *             step-level one is the easier accident of the two, because adding
 *             it to one step looks like scoping rather than weakening.
 *
 * A key set to exactly `false` is NOT reported: `allow_failure: false` is the
 * explicit spelling of "not permitted", and failing a build for writing down
 * what it already does is how a gate trains people to delete it.
 *
 * @return list<string> one human-readable finding per escape found; empty means
 *                      a failure of this job really is a failure of the pipeline
 */
function ciJobSoftFailures(array $job): array
{
    $found = [];

    foreach (['allow_failure', 'exit_codes', 'continue-on-error'] as $key) {
        if (array_key_exists($key, $job) && ! ciIsExplicitFalse($job[$key])) {
            $found[] = sprintf('the job sets `%s`', $key);
        }
    }

    foreach ((array) ($job['steps'] ?? []) as $index => $step) {
        if (! is_array($step)) {
            continue;
        }

        foreach (['continue-on-error', 'allow_failure', 'exit_codes'] as $key) {
            if (array_key_exists($key, $step) && ! ciIsExplicitFalse($step[$key])) {
                $found[] = sprintf(
                    'step %d (%s) sets `%s`',
                    $index + 1,
                    is_string($step['name'] ?? null) ? $step['name'] : 'unnamed',
                    $key,
                );
            }
        }
    }

    return $found;
}

/**
 * A matrix that cancels its siblings is a THIRD escape, and a subtle one.
 *
 * GitHub Actions defaults `fail-fast` to true: the first failing leg cancels the
 * rest, which destroys exactly the shard reports the rollup exists to count. The
 * surviving reports then describe fewer shards than the matrix declared, on a run
 * that is nonetheless red — so the damage is a blind spot rather than a red
 * build. Absence is NOT a pass: the Actions default is the dangerous value, so a
 * matrix with no `fail-fast` key is reported too.
 *
 * null when the job runs no matrix at all, where the question does not arise.
 */
function ciJobFailsFast(array $job): ?bool
{
    if (! array_key_exists('strategy', $job)) {
        return null;
    }

    return (bool) ($job['strategy']['fail-fast'] ?? true);
}

/**
 * Whether the job publishes its report on EVERY outcome, not only on success.
 *
 * GitLab: `artifacts: {when: always}`. The default is `on_success`, which
 * uploads nothing from a red job — so the rollup, the one check that notices a
 * shard silently running zero tests, would be blind to the shard that needs
 * looking at.
 *
 * Actions: an `upload-artifact` step guarded by `if: always()`. A step without
 * that guard is skipped when an earlier step fails, with the same result.
 */
function ciJobPublishesAlways(array $job): bool
{
    $artifacts = $job['artifacts'] ?? null;

    if (is_array($artifacts)) {
        $condition = ciJobCondition($artifacts);

        if ($condition === 'always') {
            return true;
        }
    }

    foreach ((array) ($job['steps'] ?? []) as $step) {
        if (! is_array($step)) {
            continue;
        }

        $uses = $step['uses'] ?? null;

        if (! is_string($uses) || ! str_contains($uses, 'upload-artifact')) {
            continue;
        }

        if (ciJobCondition($step) === 'always') {
            return true;
        }
    }

    return false;
}

/**
 * Whether the job downloads another job's artifacts — i.e. really merges them.
 *
 * GitLab spells it `needs: [{job: x, artifacts: true}]`; Actions uses an
 * `actions/download-artifact` step. A dependency WITHOUT the artifact fetch is
 * a dependency that guarantees only the ordering, and the rollup would then
 * report on reports it never received.
 */
function ciJobDownloadsArtifacts(array $job): bool
{
    foreach ((array) ($job['needs'] ?? []) as $need) {
        if (is_array($need) && ! ciIsExplicitFalse($need['artifacts'] ?? true) && array_key_exists('artifacts', $need)) {
            return true;
        }
    }

    foreach ((array) ($job['steps'] ?? []) as $step) {
        if (is_array($step) && is_string($step['uses'] ?? null) && str_contains($step['uses'], 'download-artifact')) {
            return true;
        }
    }

    return false;
}

function ciIsExplicitFalse(mixed $value): bool
{
    return $value === false || $value === 'false';
}
