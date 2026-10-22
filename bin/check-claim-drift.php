#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * check-claim-drift.php — catch documentation and ledger claims that the tree has outgrown.
 *
 * THE DEFECT CLASS. A document asserts that a named capability does NOT exist
 * ("not implemented", "no such", "there is no", "never used", "zero call sites").
 * Time passes, the code lands, and the assertion silently becomes false. Three
 * of the six V1 capability audits run in October 2026 found exactly this, and the
 * cost was measured, not guessed: the top-priority "security finding" of that
 * cycle was investigated three times before it turned out to be already fixed,
 * because a stale claim said the code was absent.
 *
 * WHAT MAKES THIS CHECK CONSERVATIVE. A false positive trains the operator to
 * ignore the gate, which is worse than the bug it hunts. An earlier revision of
 * this file was run against this repository and produced 107 findings of which
 * most were wrong; the causes were structural and are the reason the matcher is
 * shaped the way it is now:
 *
 *   - a subject is only credible if it sits in the SAME CLAIM SEGMENT as the
 *     assertion. Attributing `call()` to "there is no shared event loop" because
 *     `callMany()` appears elsewhere in the paragraph is unsound, not noisy-but-fine.
 *   - a method claim is resolved against the class the sentence names. "There is
 *     no `service()` on `NotifyService`" is about NotifyService, not about the
 *     unrelated `service()` that happens to exist in AppCommand.
 *   - a claim that qualifies its subject ("no Laravel `Vault` FACADE") is answered
 *     against the qualified kind, not against the bare name.
 *   - "absent from the ledger / the doc / the record" is an absence in a DOCUMENT.
 *     Reading the tree cannot refute it, so it is never checked.
 *   - a deadness claim is answered within the scope it names, and a reference is
 *     not a use: a `use` statement, a `class_exists()` probe and a prose mention
 *     do not make a shipped class alive.
 *
 * WHY IT RESOLVES A SCOPE AND NEVER ASKS A GLOBAL QUESTION. The largest measured
 * false-positive family was not a bad pattern — it was a wrong QUESTION. "there
 * is no `id()` column" was answered by asking whether an `id()` METHOD exists
 * anywhere; "no reachable MySQL server: … (`tests/MySql/MySqlTestCase.php`)" was
 * answered by asking whether that FILE exists; "absent from every TL source" was
 * answered by asking whether the cited source FILES exist. All three are the same
 * bug: the subject pool was the whole SENTENCE, so any code span in the sentence
 * could refute any assertion in it. Global existence is never the question a
 * scoped claim asks, so every assertion is now cut into the two windows the
 * grammar actually gives it, and nothing outside them can refute anything:
 *
 *   - the SUBJECT window is the noun phrase the negation applies to — the clause
 *     on the side of the match the pattern's own grammar puts it, cut at the
 *     nearest clause boundary (sentence end, `;`, `:`, em dash, ", so", ", and",
 *     ", that"). "there is no X" takes what FOLLOWS the match; "X is absent from
 *     Y" takes what PRECEDES it. A neighbour clause can no longer supply a
 *     subject, which is what let `PassportAnonymousLoginService` refute "there is
 *     no session to read a user id out of".
 *   - the SCOPE window is the prepositional phrase the assertion itself attaches
 *     to its negation ("no `service()` ON `NotifyService`"). A class in that
 *     phrase becomes the OWNER a method claim is answered against; a PATH in it
 *     becomes the boundary the subject is searched inside instead of the whole
 *     tree.
 *   - a CITATION is not a subject. A span inside brackets, or one followed by
 *     `:line`, is the sentence pointing at evidence — `tests/MySql/MySqlTestCase.php`
 *     in a sentence about whether a server is reachable is a citation, and the
 *     file existing says nothing about the server.
 *   - a claim about something the tree index cannot answer — a COLUMN, a TABLE
 *     ROW, an INDEX, a BINARY on PATH, a reachable SERVER — is SUPPRESSED with
 *     its reason counted, never resolved against the nearest thing of a similar
 *     shape. This is the "prefer silence over a guess" rule, and it is why
 *     `id()` as a column name is never checked against `Users::id()`.
 *
 * A claim is flagged only when the assertion is present, is not historicised or
 * self-refuting, names a subject specific enough to verify INSIDE a scope the
 * sentence itself names, and the tree POSITIVELY contradicts it there.
 * Everything else is suppressed and counted with its reason, so silence is
 * auditable rather than invisible.
 *
 * BOTH DIRECTIONS, SINCE 2026-10-17. Everything above is ABSENCE-directed, and
 * that left a hole measured rather than argued: a two-line probe reading "the
 * class `MeRezaRezaei\Teleframe\Mirror\Models\ThisModelDoesNotExist` is declared
 * in this repository" and "the gate script `bin/verify-nonexistent-gate.php`
 * exists here" scored ZERO findings and, worse, ZERO suppressions — not even an
 * audit entry, because no pattern matched. A gate that is silent in a whole
 * direction cannot be told apart from one that is clean, and the absence
 * direction getting all the tool while the existence direction got nothing is
 * the same waste the absence direction was built to stop.
 *
 * So an existence assertion is now read too, under four verbs, and answered only
 * where the question is CLOSED:
 *
 *   - a FQCN under this package's own namespace (`MeRezaRezaei\Teleframe\…`)
 *     either is declared in src/, tests/ or bin/ or it is not.
 *   - a repo-relative FILE path that is not a glob, an ellipsis or a directory.
 *   - `Class::method()`, where the span names its own class.
 *
 * Everything else is suppressed with a counted reason rather than guessed, and
 * the two rules that keep it honest are both conservative in the same direction:
 *
 *   - A SHORT class name is not a claim about this repository. "`UpdateStored`
 *     is dispatched by …" and "`PendingThing` is dispatched by …" are shaped
 *     identically, and nothing in the sentence separates them, so a short name
 *     is only resolved when the same sentence names a path inside the tree.
 *     A FQCN from a dependency is suppressed for the mirror reason: this repo
 *     does not vendor `Illuminate\Support\Str`, so its absence proves nothing.
 *   - A NAME THAT DOES NOT EXIST YET IS NOT DRIFT. "We will add X" is a true
 *     sentence about the future; flagging it would report a false claim as
 *     drift, which is the one error this file must not make. Existence
 *     assertions in a prospective or promissory frame are suppressed with the
 *     frame named in the tally.
 *
 * The narrowing that cost one measured false positive and is documented at
 * VERB_GAP_WORDS: the named span must be in the SUBJECT POSITION of its own
 * verb. "…`TStore::buckets()` … work before any credential exists" is about the
 * credential, and reading it as a claim about `buckets()` was wrong.
 *
 * WHAT IT NEVER DOES. It never reads .env values, never executes anything a
 * document suggests, and resolves every path relative to the tree it was invoked
 * in (LAW 18: a gate's subject is the tree it runs from — ask git, never derive
 * the root from this script's own location).
 *
 * USAGE
 *   php bin/check-claim-drift.php            scan and report
 *   php bin/check-claim-drift.php --list     show the pattern catalogue, scan nothing
 *   php bin/check-claim-drift.php --quiet    print only the verdict and the tallies
 *   php bin/check-claim-drift.php --strict   exit 1 on any candidate (gating; read the
 *                                            exit-policy note at the bottom first)
 *   php bin/check-claim-drift.php --path=docs/usage/live.md    scan one path instead
 *
 * EXIT CODES
 *   0  clean          1  drift found
 *   2  usage error    3  the tree could not be measured (a real verdict, not a pass)
 */
const EXIT_CLEAN = 0;
const EXIT_FINDINGS = 1;
const EXIT_USAGE = 2;
const EXIT_UNMEASURABLE = 3;

/**
 * The tree this gate measures, resolved from the INVOCATION via git and never
 * from this script's own location and never from a literal path (LAW 18).
 */
function resolveRoot(): string
{
    $out = [];
    $code = 0;
    @exec('git rev-parse --show-toplevel 2>/dev/null', $out, $code);
    $root = $code === 0 ? trim((string) ($out[0] ?? '')) : '';
    if ($root === '' || ! is_dir($root)) {
        fwrite(STDERR, "FATAL: could not resolve the repository root from this invocation.\n");
        fwrite(STDERR, "       Run this from inside a git checkout; it measures the tree it is run in.\n");
        exit(EXIT_UNMEASURABLE);
    }

    return realpath($root) ?: $root;
}

// -----------------------------------------------------------------------------
// 1. THE PATTERN CATALOGUE
// -----------------------------------------------------------------------------

/**
 * ABSENCE families: a claim that something DOES NOT EXIST.
 *
 * The fourth element is the assertion's GRAMMAR, and it is what stops the
 * subject leaking out of its own clause:
 *
 *   'after'   the subject FOLLOWS the match — "there is no `TopicRegistry`".
 *   'before'  the subject PRECEDES the match — "`acked` is absent from …".
 *   'tight'   the backward window also stops at the soft connectors
 *             ("…, which also pins that the methods (…) ARE ABSENT FROM both").
 *             Needed for the reverse form, whose subject sits in the noun phrase
 *             immediately before the verb. Absent for `dead-nothingcalls`,
 *             whose subject is joined to its predicate by a bare "and".
 */
const PATTERNS = [
    // "the feature was never built" — the plainest form.
    ['absent-implemented', 'not implemented', '/\b(?:not|never) implemented\b|\bhas not been implemented\b/i', ['side' => 'after']],
    // "the named thing is not there" — the form the V1 audits found three times.
    ['absent-notexist',    'does not exist', '/\b(?:do(?:es)?|did) not exist\b|\bdoesn\'t exist\b/i', ['side' => 'after']],
    // "no such X" — used for methods, tables, namespaces and verbs alike.
    ['absent-nosuch',      'no such', '/\bno such\b/i', ['side' => 'after']],
    // "the package does not ship it".
    ['absent-shipped',     'is not shipped', '/\b(?:is|are) not shipped\b|\bnot shipped\b|\bnever shipped\b/i', ['side' => 'after']],
    // "the operator cannot reach it" — flags, keys, surfaces.
    ['absent-available',   'not available', '/\bnot available\b|\bno longer available\b/i', ['side' => 'after']],
    // "we deferred it" — usually too vague to verify; the suppression tally is
    // where that honesty shows, rather than being discovered by guessing.
    ['absent-tbd',         'TBD', '/\bTBD\b|\bnot yet (?:built|decided|specified)\b|\bplaceholder\b/i', ['side' => 'after']],
    // The resource is absent from the named scope. This is the ONE form whose
    // subject legitimately sits BEFORE the verb, so it is the one form given a
    // backward window — and a tight one, because "which also pins that the
    // methods X ARE ABSENT FROM both" would otherwise contribute the pinning
    // test as the subject.
    ['absent-absent',      'is absent from', '/\b(?:is|are) absent from\b/i', ['side' => 'before', 'tight' => true]],
    // "there is no X at all" — very common; kept because the extractor can often
    // rescue a specific one ("there is no `TopicRegistry`").
    ['absent-thereisno',   'there is no', '/\bthere (?:is|are) no\b|\bthere is not\b/i', ['side' => 'after']],

    // REVERSE FORM — "it shipped but nothing reaches it". This family matters
    // because this repo has twice shipped a class with no call site, and once
    // recorded a decryptor as dead code while it was shipping on the login path.
    ['dead-nocallsite',    'no call site / zero callers', '/\b(?:no|zero) call sites?\b|\b(?:has|have|with) zero callers?\b|\bzero callers?\b/i', ['side' => 'after']],
    ['dead-neverused',     'never used', '/\bnever used\b|\bnever called\b|\bnever invoked\b|\bnever (?:constructed|instantiated)\b/i', ['side' => 'before']],
    ['dead-noconstruct',   'no construction site', '/\bno construction site\b|\bnever constructed\b|\bno instantiation\b/i', ['side' => 'after']],
    // "nothing in src/ calls it" — the F15-4 shape. It is scope-checkable only
    // because the claim itself names the directory it means, and its subject is
    // joined to that phrase by a bare "and", so the backward window must reach
    // across it.
    ['dead-nothingcalls',  'nothing in src/ calls it', '/\bnothing (?:in|under|inside) [^.]{0,60}?\b(?:calls?|invokes?|uses?|constructs?)\b/i', ['side' => 'before']],
    ['dead-deadcode',      'dead code', '/\bdead code\b|\bunreferenced\b|\borphaned\b/i', ['side' => 'after']],
];

/**
 * EXISTENCE families: a claim that something DOES exist.
 *
 * THE DIRECTION THIS FILE DID NOT HAVE, and the reason it is here. Everything
 * above finds documents that assert a thing is GONE. The measured hole is the
 * mirror image: a document can equally assert that a thing EXISTS when it does
 * not, and nothing here could see that — a planted "the class `X` is declared in
 * this repository" scored ZERO, not even a suppression, because no pattern
 * matched it. The absence direction got the tool; the existence direction got
 * silence. Silence that looks identical to "checked and clean" is the failure
 * this section exists to close.
 *
 * `side` is BEFORE for every one of these, and that is a grammar fact rather
 * than a preference: the subject of "X exists" is definite and named, so it sits
 * in front of the verb. There is no indefinite positive ("there exists X") form
 * in this repository's prose, and adding a forward window here would hand one
 * clause's span to the previous clause's verb — the same leak the absence side
 * spent three measured false positives fixing. So a positive assertion whose
 * subject does NOT precede its verb is not read at all.
 */
const EXISTENCE_PATTERNS = [
    // "the class `X` is declared in …" — the form that scores zero today.
    //
    // The MODAL+BE frames are in the pattern on purpose. "must be declared" is
    // not an assertion this tree should report on, but it IS a presence claim,
    // and leaving it out of the pattern makes the intent guard the wrong place to
    // catch it: the sentence would simply never match, so it would vanish with
    // no entry in the ledger at all. Matching it here and suppressing it below
    // turns a silent hole into counted silence, which is the difference between
    // a gate you can audit and one you have to take on trust.
    //
    // The PAST tense ("was declared") is deliberately NOT here. A past-tense
    // presence claim is a record of a former tree, and this file already refuses
    // to read archives; letting one through would turn every "the old pipeline
    // declared `X`" into a candidate.
    ['exis-declared',  'is declared',    '/\b(?:is|are|must be|should be|will be|shall be|can be|needs? to be|has(?: to|ha) to be|have to be)\s+declared\b/i', ['side' => 'before']],
    // "the gate script `bin/x.php` exists here" — and the bare verb, because
    // "X WILL EXIST" is the tense partner of "X does not exist", and a pattern
    // that cannot see it would let a plan read as a fact.
    //
    // The NEGATED form is excluded on purpose. "does not exist" is an ABSENCE
    // claim and is already owned, scoped and reasoned about by the family above;
    // letting it match here as well would examine one sentence from both
    // directions at once, which put an `existence:` skip on the ledger line of a
    // perfectly good absence check. The two lookbehinds are fixed-length, which is
    // what PCRE requires.
    ['exis-exists',    'exists',         '/(?<!not )(?<!never )\b(?:exists?|existed)\b/i', ['side' => 'before']],
    // "X is defined as / is implemented at".
    ['exis-defined',   'is defined',     '/\b(?:is|are|must be|should be|will be|shall be|can be|needs? to be|has(?: to|ha) to be|have to be)\s+defined\b/i', ['side' => 'before']],
    ['exis-implemented', 'is implemented', '/\b(?:is|are|must be|should be|will be|shall be|can be|needs? to be|has(?: to|ha) to be|have to be)\s+implemented\b/i', ['side' => 'before']],
];

/**
 * Every pattern, both directions, in the order the scan collects them. PATTERNS
 * itself is left untouched so the absence catalogue and `--list` keep reporting
 * exactly what they reported before this section existed.
 *
 * A function and not a third constant, because `array_merge()` is not a constant
 * expression in PHP; the array is built once and memoised, so the per-segment
 * and per-line call sites cost one array copy for the whole run.
 */
function allPatterns(): array
{
    static $all = null;
    if ($all === null) {
        $all = array_merge(PATTERNS, EXISTENCE_PATTERNS);
    }

    return $all;
}

/**
 * Deadness patterns that a CLASS subject may answer. `dead-nothingcalls` is
 * deliberately absent: its object is a routine the sentence already names, and
 * letting it pull an unrelated class in was a measured false-positive source.
 */
const DEADNESS_CLASS_SCOPED = ['dead-nocallsite', 'dead-neverused', 'dead-noconstruct', 'dead-deadcode'];

/**
 * GUARD — PLANNED WORK. A document that names a class which does not exist YET
 * is not lying: "we will add `PlannedModel`" is a true sentence about the
 * future. Flagging it would be reporting a FALSE claim as drift, which is the
 * one error this file must never make — worse than the hole it hunts, because
 * the operator's whole defence against a noisy gate is that its silence is
 * trustworthy.
 *
 * So an existence assertion is only read when the sentence is in the PRESENT
 * tense about the current tree. Any prospective or promissory frame in the same
 * sentence suppresses it, with the frame named in the tally so the silence is
 * auditable rather than invisible.
 *
 * WHY A MARKER LIST AND NOT GRAMMAR. Distinguishing "is declared" (a fact) from
 * "will be declared" (a plan) is a mood judgement, and a full mood analysis is
 * exactly the kind of cleverness that produces an unexplainable false positive
 * later. The frames below are the ones this repository's documentation actually
 * uses, they are read from the SAME sentence and never from a neighbouring one,
 * and an unrecognised plan-shaped sentence falls through to the kind rules and
 * is suppressed there too. Two independent silences, either of which is enough.
 *
 * The `must be` / `should be` frame is deliberately broad: those words mark a
 * REQUIREMENT rather than a report of what is on disk, and a requirement that
 * names a real symbol is still better answered by a reader than by this gate.
 */
const PLANNED_WORK_GUARDS = [
    // Order is significance, not severity: the frame that says most about WHY the
    // sentence is not an assertion is asked first, so the tally names the real
    // reason instead of the vaguest one that happens to match. A question is not
    // a claim at all; `will` and `not yet` are the tense pair of the patterns
    // above; `must be` is a requirement. `planned work` is last because it carries
    // the loosest words ("later", "eventually", "proposed").
    ['question about the tree', '/\b(?:whether|whether or not|if)\s+(?:`|the\b|there\b|a\b|an\b|any\b)/i'],
    ['future tense', '/\b(?:will|shall|would|going to|intends? to|intended to|plans? to|planned to)\b/i'],
    ['not yet', '/\bnot yet\b|\byet to\b|\bto be (?:added|written|shipped|landed|implemented|created|extracted|built)\b|\bis to be\b/i'],
    ['requirement, not a report', '/\b(?:must|should|needs? to|has to|have to|ought to)\s+be\b/i'],
    ['planned work', '/\b(?:planned|roadmap|milestones?|next steps?|future work|forthcoming|upcoming|prospective|proposal|proposed|once (?:we|this|the)|someday|eventually|eventual|later)\b/i'],
];

/**
 * THE ROOT NAMESPACE OF THIS PACKAGE, from composer.json's PSR-4 root
 * ("MeRezaRezaei\\Teleframe\\…"). It is the boundary of what this tree can
 * answer: a fully-qualified class under it either is declared in src/, tests/
 * or bin/ or it is not, and the question is closed. A FQCN under any OTHER root
 * (`Illuminate\Support\Str`, `Symfony\Component\Console\…`) belongs to a
 * dependency this repository does not vendor, so "the tree does not declare it"
 * says nothing at all about whether the document is right.
 */
const OWN_PACKAGE_NAMESPACE = 'MeRezaRezaei\\Teleframe';

/**
 * NOUN KINDS THE TREE INDEX CANNOT MEASURE. A subject one of these words
 * describes is not a PHP symbol: asking whether a `column` named `id()` exists
 * as a METHOD is the exact question that produced "method `id()` is implemented
 * at src/Core/Methods/Generated/Users.php:42" as a contradiction of a claim about
 * the columns of `tf_updates`. So the assertion is suppressed with a named
 * reason instead of being answered against the nearest thing of similar shape.
 */
const NOUN_KINDS = [
    'SCHEMA' => ['column', 'columns', 'table', 'tables', 'row', 'rows', 'field', 'fields',
        'index', 'indexes', 'primary key', 'foreign key', 'unique key', 'constraint',
        'trigger', 'sequence', 'migration', 'migrations', 'seed row', 'view'],
    'HOST' => ['server', 'servers', 'binary', 'binaries', 'executable', 'daemon',
        'process', 'socket', 'port', 'package', 'library', 'command', 'shell'],
];

/**
 * WORDS THAT MAKE AN ASSERTION A CLAIM ABOUT THE MACHINE, NOT THE REPOSITORY.
 * "`command -v mysql` returned 127 (nothing on PATH)" is a fact about the host
 * that happened to run the audit. The tree index measures files; it cannot
 * measure whether a server is reachable, so these claims are counted and
 * suppressed rather than answered against the nearest file-shaped thing.
 */
const HOST_FACT_WORDS = '/\b(?:server|servers|binary|binaries|executable|daemon|process|socket|network|internet|host|machine|environment|PATH|localhost|reachable)\b/i';

/**
 * A preposition after the negation introduces the assertion's SCOPE, not its
 * subject: "there is no `service()` ON `NotifyService`" is about `service()`,
 * and `NotifyService` is where to look. Splitting on the first one keeps a
 * class out of the subject pool — which is how a claim about the ABSENCE of a
 * method came to be reported as "class `NotifyService` is declared in …".
 *
 * A bare "and"/"that"/"which" is deliberately not here: those join a subject to
 * its predicate inside one assertion, so cutting there would drop the subject.
 */
const SCOPE_PREPOSITION = '/\b(?:on|in|inside|within|under|beneath|from|across|for|to|at|via|through|into|outside|of|by|against)\b[ \t]/i';

/**
 * PATHS THAT MAY TAKE THEIR SUBJECT FROM BEHIND THE VERB.
 *
 * English puts the subject of a negative in one of two places. "There is no
 * `TopicRegistry`" is indefinite, so the subject always FOLLOWS. "There is no
 * `MessageFactory` does not exist" is definite — the thing was already named —
 * so the subject PRECEDES: "`MessageFactory` does not exist".
 *
 * Only the three patterns that read as a predicate about an already-named thing
 * are allowed to look backwards, and only as a FALLBACK for a forward window
 * that holds no subject at all. "there is no" is deliberately absent from this
 * list: its subject can never precede it, and allowing it to look would hand
 * "there is no session to read a user id out of" the `PassportAnonymousLoginService`
 * named three clauses later.
 */
const BACKWARD_SUBJECT_FALLBACK = ['absent-notexist', 'absent-implemented', 'absent-shipped'];

/**
 * Cut offsets that bound one assertion's own window.
 *
 * Boundaries, on the code-masked text so that offsets index the ORIGINAL: a
 * sentence end, `;`, `:`, the em dash, and the connectors ", so" / ", but" /
 * ", because" / ", and". `:` is here because a colon introduces the sentence's
 * EVIDENCE — "There is no reachable server in this environment: `command -v …`
 * returned 127" hands its evidence to everything after the colon, and none of
 * that evidence is the subject of the claim.
 *
 * `tight` adds ", that" / ", which" / ", or" / ", while", which the reverse form
 * needs: "…which also pins that the account-destroying methods (…) are absent
 * from both" would otherwise offer the pinning TEST as the subject of its own
 * absence.
 *
 * A BARE " and " is deliberately not a boundary in either mode. The real F15-4
 * claim puts its subject one "and" before the negation ("`decryptFileCredentials()`
 * take raw bytes … and nothing in `src/` invokes them") and cutting there would
 * lose the one catch worth having — a subject joined to its own predicate by
 * "and" is still the same assertion. The comma-forms are boundaries because a
 * premise's subject is not a consequence's object: "…`QueuedNotifyJob` declares
 * no retry budget, so the documented escape does not exist" is a claim about the
 * escape, not the job.
 */
function clauseCuts(string $text, bool $tight): array
{
    $masked = blankCodeSpans($text);
    $len = strlen($masked);
    $cuts = [0];

    for ($i = 0; $i < $len; $i++) {
        $c = $masked[$i];
        if ($c === ';') {
            $cuts[] = $i + 1;

            continue;
        }
        if ($c === '.' || $c === '?' || $c === '!' || $c === ':') {
            // A sentence end only counts when whitespace follows it, so `v1.4`
            // and `src/Foo.php` never split. Markdown emphasis and blockquote
            // markers are stepped over first: "…DELIBERATELY NOT IMPLEMENTED.**
            // Read this before touching `TLRegistry`" is TWO assertions, and the
            // second one must not be able to answer the first.
            $j = $i + 1;
            $spaces = 0;
            while ($j < $len && ($masked[$j] === ' ' || $masked[$j] === "\t"
                || $masked[$j] === '*' || $masked[$j] === '_' || $masked[$j] === '>')) {
                if ($masked[$j] === ' ' || $masked[$j] === "\t") {
                    $spaces++;
                }
                $j++;
            }
            if ($spaces > 0 && $j < $len) {
                $cuts[] = $j;
            }
        }
    }

    // A connector's trailing whitespace is EXACTLY ONE character, never a run.
    // `blankCodeSpans()` turns a code span into a run of spaces, so a greedy
    // `\s+` here would swallow the span the cut is supposed to stop in front of
    // and land the cut past the end of the segment — which silently loses the
    // boundary and hands the next clause's evidence back to the subject. That
    // was a measured cause of "It does not exist on `StoreService`**, so
    // `TStore::service()`…" resolving `StoreService` as the subject.
    $connectors = $tight
        ? ['/(?:,|)[ \t]+(?:so|but|because|that|which|or|while|since)[ \t]/', '/,[ \t]+and[ \t]/']
        : ['/(?:,|)[ \t]+(?:so|but|because)[ \t]/', '/,[ \t]+and[ \t]/'];

    foreach ($connectors as $cre) {
        if (preg_match_all($cre, $masked, $mm, PREG_OFFSET_CAPTURE)) {
            foreach ($mm[0] as $m) {
                $cuts[] = $m[1] + strlen($m[0]);
            }
        }
    }
    // The em dash is three bytes; match it as bytes, never as a character.
    if (preg_match_all("/\xe2\x80\x94/", $masked, $mm, PREG_OFFSET_CAPTURE)) {
        foreach ($mm[0] as $m) {
            $cuts[] = $m[1] + 3;
        }
    }

    $cuts = array_values(array_unique(array_filter($cuts, static fn ($b) => $b >= 0 && $b <= $len)));
    sort($cuts);

    return $cuts;
}

/**
 * The SUBJECT and SCOPE windows of one assertion match.
 *
 * Both are substrings of the segment, cut at the nearest clause boundary on
 * either side of the match. A subject can only ever come out of the window its
 * own grammar selects, which is the whole point: the segment may contain ten
 * code spans and only the one the negation applies to is checkable.
 */
function assertionWindows(string $text, int $mo, int $ml, array $opt): array
{
    $end = $mo + $ml;
    $lo = 0;
    $hi = strlen($text);
    foreach (clauseCuts($text, (bool) ($opt['tight'] ?? false)) as $c) {
        if ($c <= $mo && $c > $lo) {
            $lo = $c;
        }
        if ($c > $end && $c < $hi) {
            $hi = $c;
        }
    }

    if (($opt['side'] ?? 'after') === 'before') {
        return [substr($text, $lo, $mo - $lo), substr($text, $end, $hi - $end)];
    }

    return [substr($text, $end, $hi - $end), ''];
}

/**
 * Split a forward window into [subject head | scope phrase] on its first
 * preposition. Code spans are masked first, so "take raw bytes inside a
 * `values:Vector<SecureValue>`" never splits on a word inside the backticks.
 */
function splitScopePhrase(string $window): array
{
    $masked = blankCodeSpans($window);
    if (preg_match(SCOPE_PREPOSITION, $masked, $m, PREG_OFFSET_CAPTURE)) {
        $at = $m[0][1] + strlen($m[0][0]);

        return [substr($window, 0, $at), trim(substr($window, $at))];
    }

    return [$window, ''];
}

/**
 * Is the span inside brackets? A span a sentence parenthesises is its evidence
 * — "no reachable server … (`tests/MySql/MySqlTestCase.php`)" cites the file
 * that holds the opt-in flag, and the file existing says nothing about a server.
 */
function bracketDepthAt(string $text, int $off): int
{
    $masked = blankCodeSpans($text);
    $len = strlen($masked);
    $depth = 0;
    for ($i = 0; $i < $off && $i < $len; $i++) {
        if ($masked[$i] === '(' || $masked[$i] === '[') {
            $depth++;
        } elseif ($masked[$i] === ')' || $masked[$i] === ']') {
            $depth = max(0, $depth - 1);
        }
    }

    return $depth;
}

/**
 * The noun kind the SENTENCE gives this span, read from the words immediately
 * around it, or null when the span is being talked about as code.
 *
 * Only the words directly touching the span count. "there is no `id()` column,
 * on that table or on any of its ~20 children" is a claim about COLUMNS because
 * `column` follows `id()` — not because the words "children" and "table" appear
 * later in the same clause.
 */
function nounKindNear(string $win, int $off, int $len): ?string
{
    $after = preg_replace('/^[\s*_`\[\](),:.\-]*/', '', substr($win, $off + $len, 26)) ?? '';
    $start = max(0, $off - 30);
    $before = preg_replace('/[\s*_`\[\](),:.\-]*$/', '', substr($win, $start, $off - $start)) ?? '';

    foreach (NOUN_KINDS as $kind => $words) {
        foreach ($words as $w) {
            $q = preg_quote($w, '/');
            if (preg_match('/^'.$q.'\b/i', $after) || preg_match('/\b'.$q.'$/i', $before)) {
                return $kind;
            }
        }
    }

    return null;
}

/**
 * A SCOPE that is a TABLE turns its subject into a table operation. "there is no
 * `delete()` on `tf_messages` or on any child table" is a claim about the schema,
 * and answering it with `S3GatewayContract::delete()` — a PHP method of an
 * entirely unrelated spelling — is the global-existence bug wearing a scope.
 */
const SCHEMA_SCOPE = '/\b(?:tables?|columns?|rows?|migrations?|index|schema|sql|database|constraint|trigger)\b/i';

function scopeNounKind(string $scope): ?string
{
    return preg_match(SCHEMA_SCOPE, ' '.$scope.' ') ? 'SCHEMA' : null;
}

/**
 * Why an assertion produced no checkable subject. Named per case, because the
 * suppression tally is the audit record: "silence" is only honest if it says
 * which of these it was.
 */
function unresolvableSubjectReason(string $windows, string $head, string $side): string
{
    // $windows is EVERY window this assertion owns, both directions. A reason is
    // not a verdict, so reading the whole window here cannot turn a citation
    // into a subject — it only names the shape that made it unanswerable.
    if (preg_match(HOST_FACT_WORDS, ' '.$windows.' ')) {
        return 'subject is a HOST FACT (server / binary / PATH) — the tree index measures the repository, not the machine';
    }
    if (trim($head) === '') {
        return $side === 'before'
            ? 'subject is named in a PRECEDING CLAUSE — not resolvable without guessing'
            : 'assertion names its subject as a bare noun phrase — not resolvable without guessing';
    }

    return str_contains($head, '`')
        ? 'assertion names no verifiable subject in its own scope (every span it names is a citation)'
        : 'assertion names no verifiable subject in its own scope (it names no code span)';
}

/**
 * HISTORICISED GUARDS. A claim in any of these frames describes a past state, or
 * is itself the refutation of a claim. Flagging these is a measured false positive.
 */
const HISTORICISED_GUARDS = [
    ['was true until',   '/\bwas true until\b|\btrue until\b/i'],
    ['replaces',         '/\bthis (?:paragraph|bullet|line|row|section|claim) replaces\b|\breplaces (?:said|the)\b|\bthe (?:paragraph|bullet|line|row|section|claim) this replaces\b/i'],
    ['used to',          '/\bused to\b|\bformerly\b|\bpreviously\b|\bonce (?:shipped|claimed|existed|said|used)\b/i'],
    ['no longer',        '/\bno longer\b|\bsince (?:removed|deleted|refactored|fixed)\b/i'],
    ['was removed',      '/\b(?:was|were) (?:removed|deleted|refuted|retracted|wrong|false|incorrect)\b/i'],
    ['corrected/stale',  '/\bcorrected\b|\bstale\b|\bout of date\b|\boutdated\b|\bsince fixed\b|\bwas a path error\b/i'],
    ['superseded',       '/\bsupersed\w*\b|\breplaced by\b|\bobsolete\b|\bdeprecated\b/i'],
    ['refuted/claimed',  '/\brefuted\b|\bclaimed\b|\bCLAIMED\b|\bthe ledger (?:said|claimed|recorded|cited)\b|\bthe doc(?:ument)? (?:said|claimed)\b|\bnamed (?:the commit|a commit)\b|\bclaimed\b/i'],
    ['commit-anchored',  '/\buntil\s+`?[0-9a-f]{7,40}`?/i'],
    ['historical tense', '/\bhistor\w*\b|\bback then\b|\bat the time\b|\bin the past\b/i'],
];

/**
 * GUARD — the absence is in a DOCUMENT, not in the tree. Reading the tree cannot
 * refute "absent from the ledger"; treating it as a code claim produced a measured
 * false positive on a real audit line.
 */
const DOC_SCOPED_GUARDS = [
    ['absent from a document', '/\b(?:is|are)?\s*absent from the (?:ledger|docs?|documentation|record|audit|file|row|table above|list above|spec|plan)\b|\bmissing from the (?:ledger|docs?|record|audit)\b/i'],
];

/**
 * GUARD — the text is a runtime error string, not a claim about the tree.
 * "NOGROUP No such key 'tg:stream:updates'" is what Redis prints, not a document
 * asserting a key does not exist.
 */
const ERROR_STRING_GUARDS = [
    ['runtime error string', '/\b(?:NOGROUP|SQLSTATE|Stack trace|Fatal error|Warning:|Uncaught|Exception:|errno|error code)\b/i'],
];

/**
 * QUALIFIERS. "there is no Laravel `Vault` FACADE" is true while a `Vault`
 * service ships. A qualified claim is answered against the qualified kind.
 */
const QUALIFIER_KINDS = [
    'facade' => 'facade',
    'command' => 'command',
    'test' => 'test',
];

const SCAN_DIRS = ['docs'];
const SCAN_FILES = [
    'AGENTS.md',
    'README.md',
    '.brain/verbatim-goals.md',
    '.brain/master-goal.md',
    '.brain/repo-status.md',
    '.brain/what-has-been-done.md',
    '.brain/verbatim-coverage.md',
];

/**
 * Deliberately NOT scanned, printed at run time so the omission is part of the
 * record rather than a silent hole. Archives record what was true at a past
 * date, which is their purpose, so an absence claim inside one is not drift.
 */
const SCAN_EXCLUDED = [
    '.brain/verbatim.md' => 'raw operator verbatim archive — a record of the past, not a claim about the tree',
    '.brain/verbatim/' => 'dated verbatim notes — same',
    '.brain/subagents/' => 'session transcripts — an agent\'s words mid-task are not a claim about the tree',
    '.brain/evidence/' => 'audit evidence — these QUOTE the refuted claims in order to refute them; flagging them would flag the correction',
    '.brain/superpowers/' => 'historical plans and specs — dated design records',
    '.brain/decisions/' => 'dated decision records',
    '.brain/missions/',
    '.brain/plans/',
    '.brain/agent-registry.md' => 'a registry of agents, not a claim about capabilities',
    'docs/schema/' => 'machine-written TL audit tables; scan explicitly with --path= to override',
];

// -----------------------------------------------------------------------------
// 2. TREE INDEX
// -----------------------------------------------------------------------------

final class TreeIndex
{
    /** @var array<string,bool> repo-relative path => true */
    public array $files = [];

    /** @var array<string,list<string>> class short name => declaring files */
    public array $classes = [];

    /** @var array<string,array<string,int>> class short name => method => line, in its own file */
    public array $classMethods = [];

    /** @var array<string,list<array{0:string,1:int}>> method name => [file,line] */
    public array $methods = [];

    /** @var array<string,bool> dotted wire method implemented as `const METHOD = '...'` */
    public array $wireMethods = [];

    /** @var array<string,list<string>> artisan command => declaring files */
    public array $commands = [];

    /** @var array<string,list<string>> config key or env var => declaring files */
    public array $configKeys = [];

    /** @var array<string,bool> class => is a Laravel facade */
    public array $facades = [];

    /** @var array<string,list<string>> class => real use sites as "file:line", production only */
    public array $uses = [];

    /** @var list<string> production (src/ + bin/) files, for scope-limited deadness */
    public array $prodFiles = [];

    /** @var list<string> every PHP file read */
    public array $readFiles = [];

    public function __construct(private string $root) {}

    public function root(): string
    {
        return $this->root;
    }

    private function rel(string $abs): string
    {
        return ltrim(str_replace($this->root, '', $abs), '/');
    }

    /** True for paths that must never be read — .env holds session credentials. */
    private function skipEnv(string $rel): bool
    {
        $base = basename($rel);

        return $base === '.env' || str_starts_with($base, '.env.');
    }

    public function build(): void
    {
        foreach (['src', 'tests', 'bin'] as $dir) {
            $this->walkDir($this->root.'/'.$dir);
        }
        foreach (array_keys($this->files) as $rel) {
            if (str_ends_with($rel, '.php')) {
                $this->indexPhp($this->root.'/'.$rel, $rel);
            }
        }
    }

    private function walkDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $f) {
            if (! $f->isFile()) {
                continue;
            }
            $rel = $this->rel($f->getPathname());
            if ($this->skipEnv($rel)) {
                continue;
            }
            $this->files[$rel] = true;
            if (str_ends_with($rel, '.php')) {
                $this->prodFiles[] = $rel;
            }
        }
    }

    private function indexPhp(string $abs, string $rel): void
    {
        $src = @file_get_contents($abs);
        if ($src === false) {
            return;
        }
        $this->readFiles[] = $rel;
        $isProd = str_starts_with($rel, 'src/') || str_starts_with($rel, 'bin/');
        $currentClass = null;

        foreach (explode("\n", $src) as $i => $line) {
            $lineNo = $i + 1;

            if (preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/', $line, $m)) {
                $currentClass = $m[1];
                $this->classes[$currentClass][] = $rel;
                if (str_contains($rel, '/Facades/')) {
                    $this->facades[$currentClass] = true;
                }
                if ($currentClass === 'Vault' || str_ends_with($rel, '/Facades/'.$currentClass.'.php')) {
                    $this->facades[$currentClass] = $this->facades[$currentClass] ?? false;
                }
            }

            if (preg_match('/^\s*(?:public\s+|private\s+|protected\s+|static\s+|final\s+|abstract\s+)*function\s+&?(\w+)\s*\(/', $line, $m)) {
                $this->methods[$m[1]][] = [$rel, $lineNo];
                if ($currentClass !== null) {
                    $this->classMethods[$currentClass][$m[1]] ??= $lineNo;
                }
            }

            // A wire method counts only when IMPLEMENTED, never when merely named.
            if (preg_match("/const\s+METHOD\s*=\s*'([a-z][a-zA-Z0-9_.]+)'/", $line, $m)) {
                $this->wireMethods[$m[1]] = true;
            }

            if (preg_match("/\\\$signature\s*=\s*'([a-z][a-z0-9:_-]*)/i", $line, $m)) {
                $this->commands[$m[1]][] = $rel;
            }

            if (preg_match('#(^|/)config/[^/]+\.php$#', $rel)) {
                if (preg_match("/env\(\s*'([A-Z0-9_]+)'/", $line, $m)) {
                    $this->configKeys[$m[1]][] = $rel;
                }
                if (preg_match("/'([a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+)'/", $line, $m)) {
                    $this->configKeys[$m[1]][] = $rel;
                }
            }

            // Real USE of a symbol, counted by construction or static call only.
            // A `use` statement, a class_exists() probe and a doc comment are
            // references, not uses; counting them would flag a dead class.
            if ($isProd && preg_match_all('/\bnew\s+([A-Z]\w*)\s*\(|\b([A-Z]\w*)::/', $line, $um, PREG_SET_ORDER)) {
                foreach ($um as $u) {
                    $sym = $u[1] !== '' ? $u[1] : $u[2];
                    if ($sym !== '') {
                        $this->uses[$sym][] = $rel.':'.$lineNo;
                    }
                }
            }
        }
    }
}

// -----------------------------------------------------------------------------
// 3. SEGMENTING — the fix for the largest measured false-positive source
// -----------------------------------------------------------------------------

/**
 * Blank out the bytes inside backtick spans, preserving byte offsets exactly.
 *
 * An earlier revision used the /u modifier with a one-byte filler, which made
 * preg offsets (characters) disagree with strlen (bytes) and silently shredded
 * the clause boundaries this file exists to find — subjects then leaked from one
 * clause into the next. Nothing here uses /u, and the filler is a same-length
 * space, so an offset found in the masked string indexes the ORIGINAL correctly.
 */
function blankCodeSpans(string $s): string
{
    $out = '';
    $in = false;
    $len = strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = $s[$i];
        if ($c === '`') {
            $in = ! $in;
            $out .= ' ';

            continue;
        }
        $out .= $in ? ' ' : $c;
    }

    return $out;
}

// -----------------------------------------------------------------------------
// 4. SUBJECT EXTRACTION
// -----------------------------------------------------------------------------

/**
 * Pull the specific things one segment names, from code spans and quoted
 * literals. Each subject is [kind, name, qualifier, ownerClass, offset, length].
 *
 * TWO KINDS OF SPAN ARE NOT SUBJECTS, and both were measured false positives:
 *
 *  - a CITATION. A span inside brackets, or one the sentence pins to a line with
 *    `:40-46`, is the sentence pointing at evidence. `tests/MySql/MySqlTestCase.php`
 *    in a claim about whether a MySQL server is reachable is a citation; the file
 *    existing refutes nothing about a server.
 *  - a `bare` identifier whose KIND is unknown. `acked` in a claim that it is
 *    absent from the TL sources is a FIELD there and a CLASS somewhere else.
 *    It is kept as a subject so the suppression can name it, and it can only ever
 *    answer "skip" — never "contradicted".
 */
function extractSubjects(string $segment): array
{
    $subjects = [];
    $spans = [];

    if (preg_match_all('/`([^`]+)`/', $segment, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $raw) {
            $spans[] = [$raw[0], $raw[1], strlen($raw[0])];
        }
    }
    if (preg_match_all('/"([^"]{3,80})"/', $segment, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as $raw) {
            $spans[] = [$raw[0], $raw[1], strlen($raw[0])];
        }
    }

    // A qualifier sits immediately before or after the subject: "no Laravel `Vault` facade".
    $qualifierNear = static function (string $name) use ($segment): ?string {
        foreach (QUALIFIER_KINDS as $word => $kind) {
            if (preg_match('/(?:'.preg_quote($word, '/').'\s+(?:`)?'.preg_quote($name, '/').'|'
                .preg_quote($name, '/').'(?:`)?\s+'.preg_quote($word, '/').')/i', $segment)) {
                return $kind;
            }
        }

        return null;
    };

    $at = 0;
    foreach ($spans as [$raw, $off, $len]) {
        // Skip to the content, so a parenthetical is judged on the span INSIDE it.
        $at = $off + 1;
        if (bracketDepthAt($segment, $at) > 0) {
            continue;
        }
        // A span the sentence pins to a line is a citation to a location.
        $after = substr($segment, $off + $len);
        if ($after !== '' && ($after[0] === '`' || $after[0] === '"')) {
            $after = substr($after, 1);
        }
        if (preg_match('/^:\d+(?:-\d+)?(?!\d)/', $after)) {
            continue;
        }

        $t = trim($raw);
        if ($t === '') {
            continue;
        }
        $t = preg_replace('/:\d+(?:-\d+)?$/', '', $t) ?? $t;

        if (preg_match('#^(?:\./)?(?:src|tests|bin|docs|config|resources)/[^\s`]*[^\s`/]#', $t, $p)) {
            // The RAW span is kept as element 6. `src/Foo/` is a DIRECTORY in the
            // sentence and `src/Foo.php` is a FILE, but both normalise to a name
            // that ends without a slash — only the raw text can tell them apart,
            // and answering a directory reference against `is_file()` would be a
            // false positive on every "everything under `src/X/`" sentence.
            $subjects[] = ['path', rtrim($p[0], '/'), null, null, $off, $len, $t];

            continue;
        }
        if (preg_match('/^([\w\\\\]+)::(\w+)\(\)$/', $t, $p)) {
            $short = substr((string) strrchr('\\'.$p[1], '\\'), 1);
            $subjects[] = ['method', $p[2], $qualifierNear($p[2]), $short, $off, $len];

            continue;
        }
        if (preg_match('/^(\w+)\(\)$/', $t, $p)) {
            $subjects[] = ['method', $p[1], $qualifierNear($p[1]), null, $off, $len];

            continue;
        }
        if (preg_match('/^([a-z][a-zA-Z0-9_]*\.[a-z][a-zA-Z0-9_.]*)$/', $t)) {
            $subjects[] = ['wire', $t, null, null, $off, $len];

            continue;
        }
        if (preg_match('/^(teleframe:[a-z0-9:_-]+)$/', $t)) {
            $subjects[] = ['command', $t, null, null, $off, $len];

            continue;
        }
        if (preg_match('/^(TELEFRAME_[A-Z0-9_]+)$/', $t)) {
            $subjects[] = ['config', $t, null, null, $off, $len];

            continue;
        }
        if (preg_match('/^\\\\?([A-Z]\w*(?:\\\\[A-Z]\w*)*)$/', $t, $p)) {
            $parts = explode('\\', $p[1]);
            $short = $parts[count($parts) - 1];
            // `$p[1]` is kept as element 6 so the existence side can tell a
            // fully-qualified name under this package's own namespace — a
            // closed question, declared here or not — from a SHORT name, which
            // is a claim about this repository only when the sentence also says
            // where to look. A short name alone could be a Laravel class, a PHP
            // builtin, or a framework type. See verifyExistence().
            $subjects[] = ['class', $short, $qualifierNear($short), null, $off, $len, $p[1]];

            continue;
        }
        if (preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+$/', $t)) {
            $subjects[] = ['config', $t, null, null, $off, $len];

            continue;
        }
        // A bare lowercase identifier: real, but of an unknown kind.
        if (preg_match('/^[a-z][a-z0-9_]*$/', $t)) {
            $subjects[] = ['bare', $t, null, null, $off, $len];
        }
    }

    return $subjects;
}

/** The tree paths a scope phrase names — a file, or every file under a dir/glob. */
function scopePathsOf(array $subjects, TreeIndex $idx): array
{
    $paths = [];
    foreach ($subjects as [$kind, $name]) {
        if ($kind !== 'path') {
            continue;
        }
        if (isset($idx->files[$name])) {
            $paths[] = $name;

            continue;
        }
        foreach (array_keys($idx->files) as $file) {
            if (str_starts_with($file, rtrim($name, '/').'/')) {
                $paths[] = $file;
            }
        }
    }

    return array_values(array_unique($paths));
}

/** Lines at which a name occurs inside a scoped set of files. */
function nameInFiles(TreeIndex $idx, array $paths, string $name): array
{
    $hits = [];
    foreach ($paths as $rel) {
        if (! str_ends_with($rel, '.php')) {
            continue;
        }
        foreach (explode("\n", (string) @file_get_contents($idx->root().'/'.$rel)) as $i => $line) {
            $trim = ltrim($line);
            if (str_starts_with($trim, '*') || str_starts_with($trim, '//') || str_starts_with($trim, '#[')) {
                continue;
            }
            if (str_contains($line, $name)) {
                $hits[] = $rel.':'.($i + 1);
            }
        }
    }

    return $hits;
}

// -----------------------------------------------------------------------------
// 5. VERIFICATION
// -----------------------------------------------------------------------------

/**
 * Does the tree contradict a claim that something is ABSENT?
 *
 * Four orderings matter here, and each one exists because the ordering before it
 * produced a measured false positive:
 *
 *  1. the SENTENCE's noun kind beats the index. "`id()` column" is a claim about
 *     a column, so it is skipped — never answered against `Users::id()`.
 *  2. an unknown KIND is never guessed. A bare identifier answers only "skip".
 *  3. a resolved SCOPE beats the whole tree. "no `Foo` in `src/Bar.php`" is
 *     asked inside `src/Bar.php`; global existence is not the question.
 *  4. only then does the tree-wide index answer, and only for kinds where
 *     existence is genuinely global (a class either is declared or is not).
 */
function verifyAbsence(TreeIndex $idx, array $s, array $scopePaths, string $window, ?string $scopeNoun = null): array
{
    [$kind, $name, $qualifier, $owner] = $s;

    if ($kind === 'bare') {
        return ['skip', "`{$name}` is a bare identifier — the sentence never says whether it is a class, a column, a key or a field, so its kind cannot be resolved without guessing"];
    }

    // A subject the sentence called a COLUMN, a TABLE or a BINARY is not a
    // symbol the index can look for, whatever its spelling looks like.
    $noun = nounKindNear($window, $s[4], $s[5]);
    if ($noun !== null) {
        return ['skip', "`{$name}` is a {$noun} noun in this sentence — the tree index measures code symbols, not {$noun} objects"];
    }

    // A method or class named against a TABLE is a schema operation. Only the
    // kinds that could be mistaken for a code symbol are held back; a `path`
    // subject against a table scope is still exactly what it says it is.
    if ($scopeNoun !== null && in_array($kind, ['method', 'class', 'config', 'command'], true)) {
        return ['skip', "the scope this claim names is a {$scopeNoun} scope, so `{$name}` is a {$scopeNoun} operation — the tree index measures code symbols, not {$scopeNoun} objects"];
    }

    if ($scopePaths !== [] && $kind !== 'path') {
        $hits = nameInFiles($idx, $scopePaths, $name);
        if ($hits !== []) {
            return ['contradicted', "`{$name}` appears at ".implode(', ', array_slice($hits, 0, 3)).' — inside the scope this claim names'];
        }

        return ['consistent', "`{$name}` appears nowhere in the scope this claim names (".implode(', ', array_slice($scopePaths, 0, 2)).') — the claim holds'];
    }

    switch ($kind) {
        case 'path':
            // A path claim is refuted only if that exact file exists. A class that
            // moved elsewhere does not refute a claim about this path.
            return isset($idx->files[$name])
                ? ['contradicted', "the file exists: `{$name}`"]
                : ['consistent', "no file at `{$name}` — the claim holds"];

        case 'class':
            // "no `X` facade" is answered against facades, not against the bare name.
            if ($qualifier === 'facade') {
                return ! empty($idx->facades[$name])
                    ? ['contradicted', "`{$name}` ships as a facade at `{$idx->classes[$name][0]}`"]
                    : ['consistent', "`{$name}` exists but no facade by that name ships — the claim holds"];
            }
            if (isset($idx->classes[$name])) {
                return ['contradicted', "class `{$name}` is declared in `{$idx->classes[$name][0]}`"];
            }

            return ['consistent', "no class `{$name}` is declared in src/, tests/ or bin/"];

        case 'method':
            // Scoped to the class the sentence names: "no `service()` on `NotifyService`"
            // is about NotifyService, not about the unrelated `service()` in AppCommand.
            if ($owner !== null && isset($idx->classMethods[$owner])) {
                return isset($idx->classMethods[$owner][$name])
                    ? ['contradicted', "`{$owner}::{$name}()` IS implemented at `{$idx->classes[$owner][0]}:{$idx->classMethods[$owner][$name]}`"]
                    : ['consistent', "`{$owner}` has no `{$name}()` — the claim holds"];
            }
            if ($owner !== null && ! isset($idx->classes[$owner])) {
                return ['consistent', "class `{$owner}` does not exist either — nothing to check `{$name}()` against"];
            }
            if (isset($idx->methods[$name])) {
                [$f, $l] = $idx->methods[$name][0];

                return ['contradicted', "method `{$name}()` is implemented at `{$f}:{$l}`"];
            }

            return ['consistent', "no implementation of `{$name}()` in src/, tests/ or bin/"];

        case 'wire':
            if (isset($idx->wireMethods[$name])) {
                return ['contradicted', "`{$name}` is implemented (a `const METHOD = '{$name}'` binding exists)"];
            }

            // Deliberate: a name appearing only inside a schema JSON is NOT an
            // implementation. `setPassportDataErrors` is present in
            // botapi-spec.json and nothing in src/ can still call it, so treating
            // the schema entry as existence would flag a claim that is true.
            return ['consistent', "no `const METHOD = '{$name}'` — nothing in the tree implements `{$name}`"];

        case 'command':
            return isset($idx->commands[$name])
                ? ['contradicted', "`{$name}` is a registered artisan command (`{$idx->commands[$name][0]}`)"]
                : ['consistent', "no artisan command `{$name}` is registered"];

        case 'config':
            return isset($idx->configKeys[$name])
                ? ['contradicted', "config key `{$name}` is read in `{$idx->configKeys[$name][0]}`"]
                : ['consistent', "no config key `{$name}` in any src/**/config/*.php"];
    }

    return ['skip', "unverified kind `{$kind}` — not answerable against the tree"];
}

/**
 * THE ONLY WORDS ALLOWED TO SIT BETWEEN A NAMED SPAN AND ITS VERB.
 *
 * This is the existence side of the "wrong QUESTION" bug the file header
 * describes, and it was measured here rather than theorised: on the real tree
 * `docs/usage/integration.md:648` reads "So `TStore::buckets()` and `php artisan
 * teleframe:tstore buckets` work before any credential exists", and the naive
 * window hands the verb "exists" the nearest code span — the whole topic of the
 * sentence — and reports "`TStore::buckets()` does not exist", which is a FALSE
 * CLAIM about a method that is on disk. The verb's real subject is the noun
 * phrase "any credential".
 *
 * So the named span must be in the SUBJECT POSITION of its own verb. A bare
 * "and" is deliberately not a cut, because a subject joined to its own predicate
 * by "and" is still one assertion — but an intervening VERB ("work", "runs") or
 * an intervening NOUN ("any credential") is a different subject, and the
 * adjacency test catches both without needing to tell them apart.
 *
 * The cost is recall, and it is paid in the safe direction: an appositive
 * ("`Foo` (the dispatcher) is declared") is not resolved, because "dispatcher" is
 * not on this list. Losing a check beats inventing one.
 */
const VERB_GAP_WORDS = [
    'is', 'are', 'was', 'were', 'will', 'would', 'shall', 'can', 'could', 'may',
    'might', 'must', 'should', 'does', 'do', 'not', 'also', 'already', 'still',
    'actually', 'currently', 'now', 'then', 'here', 'indeed', 'only', 'just',
    'simply', 'really', 'genuinely', 'permanently',
];

/**
 * The text between the LAST code span before an existence verb and the verb
 * itself. Empty when the verb abuts its span, which is the only shape this
 * detector will answer.
 */
function verbGap(string $head): string
{
    $last = 0;
    if (preg_match_all('/`[^`]+`|"[^"]{3,80}"/', $head, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $raw) {
            $end = $raw[1] + strlen($raw[0]);
            if ($end > $last) {
                $last = $end;
            }
        }
    }

    return substr($head, $last);
}

/** Is the named span the subject of its own verb, or is the verb about something else? */
function verbGapIsSubjectPosition(string $gap): bool
{
    $words = preg_split('/[^A-Za-z]+/', strtolower($gap), -1, PREG_SPLIT_NO_EMPTY);
    foreach ($words === false ? [] : $words as $w) {
        if (! in_array($w, VERB_GAP_WORDS, true)) {
            return false;
        }
    }

    return true;
}

/**
 * Does the tree contradict a claim that something EXISTS?
 *
 * The mirror of verifyAbsence(), and deliberately much narrower. An absence
 * claim can be refuted by any one of a dozen registries — a class, a method, a
 * file, a wire binding, a command — so it has room to be generous about which
 * registry answers it. An existence claim has to be ANSWERED to be worth
 * reporting, and a wrong registry produces a confident false positive rather
 * than a missed one. So only three subjects are resolved:
 *
 *   - a class under OWN_PACKAGE_NAMESPACE. `MeRezaRezaei\Teleframe\…` is this
 *     repository's own namespace, so "is it declared" has exactly one answer.
 *   - a repo-relative FILE path that is not a glob, an ellipsis or a directory.
 *   - `Class::method()`, where the class is named by the span itself.
 *
 * Everything else returns `skip` WITH ITS REASON, which lands in the printed
 * suppression tally. That is the whole posture: the kinds this detector does not
 * resolve are counted, not guessed, so a reader can see exactly how much of the
 * existence surface was left unsaid.
 *
 * THE SHORT-NAME RULE, which is the one that decides whether this is usable.
 * A doc that writes "`UpdateStored` is dispatched by …" names a class this tree
 * has, and a doc that writes "`PendingCacheThing` is dispatched by …" names one
 * it does not — and both sentences are shaped identically. Nothing in the
 * sentence distinguishes them, so the sentence's absence of evidence cannot be
 * read as evidence of absence. A short name is therefore only resolved when the
 * SAME sentence names a path inside this repository, which turns the question
 * from "whose class is this?" into the closed one — "is it declared in the file
 * this sentence points at?"
 */
function verifyExistence(TreeIndex $idx, array $s, array $scopePaths, string $segment, string $gap): array
{
    [$kind, $name, $_q] = $s;
    $raw = $s[6] ?? null;

    // The named span must be in the SUBJECT POSITION of the verb. Measured on
    // this tree: without it, "…`TStore::buckets()` … work before any credential
    // exists" is reported as a missing `buckets()` — a false claim about a
    // method that is on disk. See VERB_GAP_WORDS.
    if (trim($gap) !== '' && ! verbGapIsSubjectPosition($gap)) {
        return ['skip', 'the named span is the sentence\'s TOPIC, not the verb\'s subject — "'.shorten(trim($gap), 40).'" sits between them, so this verb is about something else'];
    }

    // An unknown KIND is never guessed — same rule, same reason as the absence
    // side. `acked` is a field in one document and a class in another.
    if ($kind === 'bare') {
        return ['skip', "`{$name}` is a bare identifier — the sentence never says whether it is a class, a file or a key, so its kind cannot be resolved without guessing"];
    }

    // A subject the sentence itself calls a COLUMN, a TABLE or a BINARY is not a
    // code symbol, whatever its spelling looks like.
    $noun = nounKindNear($segment, $s[4], $s[5]);
    if ($noun !== null) {
        return ['skip', "`{$name}` is a {$noun} noun in this sentence — the tree index measures code symbols, not {$noun} objects"];
    }

    // A QUALIFIED subject ("the `X` facade is declared …") is answered against a
    // registry the existence side does not resolve. Suppressed by name rather
    // than answered, because a wrong registry here is a confident false positive.
    // The qualifier travels on the subject tuple, set by extractSubjects().
    $qualifier = $s[2] ?? null;
    if ($qualifier !== null) {
        return ['skip', "`{$name}` is qualified as a {$qualifier} — the existence side does not resolve a qualified registry, so it is not answered"];
    }

    switch ($kind) {
        case 'path':
            // `src/Teleframe/` names a DIRECTORY and `src/**` names a PATTERN.
            // Both normalise to a name that looks like a file, and asking
            // `is_file()` about either one manufactures a false positive on
            // exactly the sentences that are most certainly true.
            if (is_string($raw) && str_ends_with(trim($raw), '/')) {
                return ['skip', "`{$name}` names a DIRECTORY, not a file — a directory's existence is not one yes/no the tree can answer for this claim"];
            }
            if (preg_match('/[*?{}<>…]/', $name)) {
                return ['skip', "`{$name}` is a PATTERN or an ellipsis, not one file — whether it resolves is a different question from whether it exists"];
            }
            if (isset($idx->files[$name]) || is_file($idx->root().'/'.$name)) {
                return ['consistent', "`{$name}` is in the tree — the claim holds"];
            }

            // A path that exists as a DIRECTORY satisfies an existence claim just
            // as a file does. Without this the verdict turned on a slash: the
            // trailing-slash spelling is skipped above, but the SAME directory
            // written bare fell through to `is_file()`, which is false for a
            // directory, and was reported contradicted. Measured on this tree:
            // `.brain/verbatim-goals.md:1719` names `tests/Concerns`, and the gate
            // called it "NOT in the tree" while `git ls-tree HEAD tests/` lists
            // the tree and six files are tracked under it. A sentence that merely
            // says a directory exists is not drift, so it is answered, not guessed.
            if (is_dir($idx->root().'/'.$name)) {
                return ['consistent', "`{$name}` is a DIRECTORY in the tree — the claim holds"];
            }

            return ['contradicted', "`{$name}` is NOT in the tree — this sentence says it exists"];

        case 'class':
            // A short name is a guess about ownership unless the sentence says
            // where the class lives. `isset($idx->classes[…]) === false` on a
            // short name means "not this repository's", not "does not exist".
            $isFqcn = is_string($raw) && str_contains($raw, '\\');
            if (! $isFqcn) {
                if ($scopePaths === []) {
                    return ['skip', "`{$name}` is a SHORT class name and this sentence names no path inside this repository — it never says whose class it is, so its absence cannot be called drift"];
                }
                $declared = array_intersect($idx->classes[$name] ?? [], $scopePaths);
                if ($declared !== []) {
                    return ['consistent', "class `{$name}` is declared at `{$declared[0]}` — the claim holds"];
                }

                return ['contradicted', "the path this sentence names does not declare class `{$name}` — it is declared in neither ".implode(', ', array_slice($scopePaths, 0, 3))];
            }

            // A FQCN under another root belongs to a dependency this repository
            // does not vendor. "We do not declare it" is not a refutation.
            if ($raw !== OWN_PACKAGE_NAMESPACE && ! str_starts_with($raw, OWN_PACKAGE_NAMESPACE.'\\')) {
                $root = substr($raw, 0, (int) strpos($raw, '\\'));

                return ['skip', "`{$name}` is declared in the `{$root}\\…` namespace — a dependency this repository does not vendor, so its absence here says nothing about the claim"];
            }
            if (isset($idx->classes[$name])) {
                return ['consistent', "class `{$name}` is declared at `{$idx->classes[$name][0]}` — the claim holds"];
            }

            return ['contradicted', "no class `{$raw}` is declared anywhere in src/, tests/ or bin/ — this sentence says it is"];

        case 'method':
            // Only a span that names its own class is a closed question. A bare
            // `service()` would be answered against an unrelated method of the
            // same spelling, which is the global-existence bug in miniature.
            if (($s[3] ?? null) === null) {
                return ['skip', "`{$name}()` is named without its class — the tree-wide method index would answer about an unrelated method of the same spelling"];
            }
            $owner = $s[3];
            if (! isset($idx->classes[$owner])) {
                return ['contradicted', "`{$owner}::{$name}()` is asserted as implemented, but no class `{$owner}` is declared anywhere in the tree"];
            }
            if (isset($idx->classMethods[$owner][$name])) {
                return ['consistent', "`{$owner}::{$name}()` is declared at `{$idx->classes[$owner][0]}:{$idx->classMethods[$owner][$name]}` — the claim holds"];
            }

            return ['contradicted', "class `{$owner}` is declared at `{$idx->classes[$owner][0]}` but has no `{$name}()` — this sentence says it does"];
    }

    return ['skip', "existence of a `{$kind}` is not a kind this detector resolves (only an own-namespace class, a real file path, and `Class::method()` are) — silence over a guess"];
}

/**
 * Does the tree contradict a DEADNESS claim?
 *
 * Two checks, kept apart because their false-positive rates differ sharply.
 *
 *  - METHOD deadness ("nothing in `src/` invokes them") is only checked when the
 *    claim names the scope it means. An unscoped "nothing ever calls
 *    fkViolations()" is scoped to the class it was written about, and no
 *    tree-wide count refutes that without guessing.
 *  - CLASS deadness is refuted only by CONSTRUCTION or a static call in
 *    production code — never by a `use`, a `class_exists()` or a doc comment.
 *    A class's own file and its own test are excluded: a test is not production
 *    use, and the convention is the class name inside the test filename.
 */
function verifyDeadness(TreeIndex $idx, string $kind, string $name, string $segment, bool $scopeIsSrc): array
{
    if ($kind === 'method') {
        if (! $scopeIsSrc) {
            return ['skip', 'method deadness without a named scope (`src/`) — not checkable without guessing'];
        }
        $hits = methodUsesUnderSrc($idx, $name);
        if ($hits !== []) {
            return ['contradicted', "`{$name}()` is called from ".implode(', ', array_slice($hits, 0, 4)).(count($hits) > 4 ? ' (+'.(count($hits) - 4).' more)' : '')];
        }

        return ['consistent', "no call to `{$name}()` anywhere under src/ — the claim holds"];
    }

    if ($kind !== 'class') {
        return ['skip', "deadness of a `{$kind}` is not checkable without guessing scope"];
    }

    $sites = [];
    foreach ($idx->uses[$name] ?? [] as $site) {
        $file = explode(':', $site)[0];
        if (str_contains(basename($file, '.php'), $name)) {
            continue;
        }
        $sites[] = $site;
    }
    if ($sites !== []) {
        return ['contradicted', "`{$name}` is constructed or statically called at ".implode(', ', array_slice($sites, 0, 3)).(count($sites) > 3 ? ' (+'.(count($sites) - 3).' more)' : '')];
    }

    return ['consistent', "`{$name}` is never constructed or statically called in production code — the claim holds"];
}

/** Real call sites of a method under src/ — calls, not declarations, not doc comments. */
function methodUsesUnderSrc(TreeIndex $idx, string $method): array
{
    static $cache = [];
    if (isset($cache[$method])) {
        return $cache[$method];
    }
    $hits = [];
    foreach ($idx->readFiles as $rel) {
        if (! str_starts_with($rel, 'src/')) {
            continue;
        }
        $lines = explode("\n", (string) @file_get_contents($idx->root().'/'.$rel));
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*(?:public\s+|private\s+|protected\s+|static\s+|final\s+)*function\s+&?'.preg_quote($method, '/').'\s*\(/', $line)) {
                continue;
            }
            $trim = ltrim($line);
            if (str_starts_with($trim, '*') || str_starts_with($trim, '//') || str_starts_with($trim, '/*')) {
                continue;
            }
            if (preg_match('/(?<![\w$])'.preg_quote($method, '/').'\s*\(/', $line)) {
                $hits[] = $rel.':'.($i + 1);
            }
        }
    }
    $cache[$method] = $hits;

    return $hits;
}

// -----------------------------------------------------------------------------
// 6. UNITS AND THE SCAN
// -----------------------------------------------------------------------------

/**
 * Split one unit's text into the individual assertions it carries.
 *
 * WHY SENTENCES AND NOT LINES. `claimUnits()` has already decided where a
 * paragraph or bullet begins, so the only boundary left to decide is where one
 * assertion ends and the next begins. Matching per LINE does neither job:
 * a single line can carry two unrelated claims ("X is absent, and Y is dead"),
 * which merges two findings into one unreadable report entry, and one claim can
 * wrap across three lines, which hides the pattern match entirely. A sentence
 * boundary is what actually separates one falsifiable assertion from the next.
 *
 * A unit with no sentence terminator is still a unit, so the empty case returns
 * the trimmed original rather than nothing — an unmatched unit must never
 * silently disappear just because it is short.
 */
function segments(string $text): array
{
    $parts = preg_split('/(?<=[.!?])\s+(?=[A-Z0-9`"\'(\[])/u', $text);
    if ($parts === false) {
        // A non-UTF-8 document cannot be split by the unicode-aware pattern.
        // Degrade to the whole unit: fewer findings, never a crash.
        return [trim($text)];
    }

    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $out[] = $part;
        }
    }

    return $out === [] ? [trim($text)] : $out;
}

/** A unit is a paragraph or bullet plus its continuation lines. */
function claimUnits(string $text): array
{
    $units = [];
    $cur = null;
    $inFence = false;

    foreach (explode("\n", $text) as $i => $line) {
        $lineNo = $i + 1;
        if (preg_match('/^\s*(```|~~~)/', $line)) {
            $inFence = ! $inFence;
        }
        $startsNew = ! $inFence && (
            trim($line) === ''
            || preg_match('/^\s*#{1,6}\s/', $line)
            || preg_match('/^\s*([-*+]|\d+\.)\s/', $line)
            || preg_match('/^\s*\|/', $line)
            || preg_match('/^\s*>/', $line)
        );
        if ($startsNew || $cur === null) {
            if ($cur !== null) {
                $units[] = $cur;
            }
            $cur = ['lines' => [], 'fenced' => $inFence];
        }
        $cur['lines'][] = [$lineNo, $line];
    }
    if ($cur !== null) {
        $units[] = $cur;
    }

    return array_map(static fn ($u) => [
        'lines' => $u['lines'],
        'fenced' => $u['fenced'],
        'text' => implode(' ', array_map(static fn ($l) => $l[1], $u['lines'])),
    ], $units);
}

function scanFile(string $abs, string $rel, TreeIndex $idx, array &$suppressed): array
{
    $text = @file_get_contents($abs);
    if ($text === false) {
        return [];
    }

    $findings = [];
    $seen = [];

    foreach (claimUnits($text) as $unit) {
        if ($unit['fenced']) {
            continue;
        }

        foreach (segments($unit['text']) as $segment) {
            // Every assertion match in this segment, each with its own offset, so
            // each can be cut into its own subject and scope windows. A pattern
            // hit with no offset is not usable and must not widen the pool.
            // BOTH directions are collected — an absence pattern and an existence
            // pattern can share one sentence, and each is then cut independently.
            $hits = [];
            foreach (allPatterns() as $p) {
                if (preg_match($p[2], $segment, $mm, PREG_OFFSET_CAPTURE)) {
                    $hits[] = [$p[0], $p[1], $p[2], $p[3], (string) $mm[0][0], (int) $mm[0][1]];
                }
            }
            if ($hits === []) {
                continue;
            }

            // Does THIS segment carry an existence assertion? Computed once, used
            // to stamp every silence below with the direction that earned it.
            $segmentHasExis = false;
            foreach ($hits as [$hid]) {
                if (str_starts_with($hid, 'exis-')) {
                    $segmentHasExis = true;
                    break;
                }
            }

            $guard = null;
            foreach ([HISTORICISED_GUARDS, DOC_SCOPED_GUARDS, ERROR_STRING_GUARDS] as $set) {
                foreach ($set as [$gname, $gre]) {
                    if (preg_match($gre, $segment)) {
                        $guard = $gname;
                        break 2;
                    }
                }
            }
            if ($guard !== null) {
                $why = $guard === 'absent from a document'
                    ? 'absence is in a DOCUMENT — reading the tree cannot refute it'
                    : ($guard === 'runtime error string'
                        ? 'runtime error string, not a claim about the tree'
                        : "historicised or self-refuting ({$guard})");
                $suppressed[$why] = ($suppressed[$why] ?? 0) + 1;

                // Stamped for the same reason as every other silence: a sentence
                // that is both "used to exist" and "X exists" is killed once, by
                // the historicised guard, and the ledger must say which direction
                // that guard was standing in front of.
                if ($segmentHasExis) {
                    $prefixed = 'existence: '.$why;
                    unset($suppressed[$why]);
                    $suppressed[$prefixed] = ($suppressed[$prefixed] ?? 0) + 1;
                }

                continue;
            }

            // THE EXISTENCE PRE-FILTER AND THE INTENT GUARD. Both are applied to
            // the existence hits ONLY, and both are counted, so the ledger shows
            // how much of the existence surface was left unread rather than the
            // tool quietly ignoring it.
            //
            // 1. The pre-filter. A sentence that names NO code span at all cannot
            //    have proposed anything to check ("no live-Temporal-server
            //    verification exists in this repo"). Recording those one by one
            //    would bury the reason that actually matters under prose, and the
            //    absence side has the same shape already — 61 of its 106
            //    suppressions are "names no code span".
            // 2. The intent guard. A PROSPECTIVE sentence is true, so a named
            //    thing that is not there yet is not drift. This is the check that
            //    keeps "we will add X" out of the report.
            //
            // Either one removes only the existence hits; an absence assertion in
            // the same sentence is still checked exactly as before.
            $exisHits = $segmentHasExis ? array_values(array_filter($hits, static fn ($h) => str_starts_with($h[0], 'exis-'))) : [];
            if ($exisHits !== []) {
                $stripExis = static fn (array $h) => ! str_starts_with($h[0], 'exis-');
                if (! str_contains($segment, '`')) {
                    $suppressed['existence assertion names no code span anywhere in its own sentence — nothing was proposed to check'] = ($suppressed['existence assertion names no code span anywhere in its own sentence — nothing was proposed to check'] ?? 0) + 1;
                    $hits = array_values(array_filter($hits, $stripExis));
                } else {
                    $frame = null;
                    foreach (PLANNED_WORK_GUARDS as [$pname, $pre]) {
                        if (preg_match($pre, $segment)) {
                            $frame = $pname;
                            break;
                        }
                    }
                    if ($frame !== null) {
                        $why = 'assertion is PLANNED WORK or a REQUIREMENT, not a report about the tree ('.$frame.') — a named-but-absent thing is not drift';
                        $suppressed[$why] = ($suppressed[$why] ?? 0) + 1;
                        $hits = array_values(array_filter($hits, $stripExis));
                    }
                }
                if ($hits === []) {
                    continue;
                }
            }

            $contradicted = [];
            $considered = [];
            $skips = [];
            $checkedOne = false;
            $allConsistent = true;
            $exisTrue = false;
            $absTrue = false;

            foreach ($hits as [$id, $label, $re, $opt, $mtext, $mo]) {
                $side = $opt['side'] ?? 'after';
                $isDead = str_starts_with($id, 'dead-');
                $isExis = str_starts_with($id, 'exis-');
                [$subjectWin, $scopeWin] = assertionWindows($segment, $mo, strlen($mtext), $opt);

                // A deadness claim's phrase is the scope ITSELF ("nothing in `src/`
                // invokes them"), so no preposition split is applied to it.
                if ($side === 'before' || $isDead) {
                    [$head, $scope] = [$subjectWin, $scopeWin];
                } else {
                    [$head, $scope] = splitScopePhrase($subjectWin);
                }

                $scopeSubjects = extractSubjects($scope);
                $scopePaths = scopePathsOf($scopeSubjects, $idx);
                $subjects = extractSubjects($head);

                // A definite negative puts its subject in front of the verb
                // ("`MessageFactory` does not exist"). Fall back to the backward
                // window ONLY for the patterns allowed to, ONLY when the forward
                // window holds nothing, and ONLY when the last span there sits
                // immediately against the match — so "`QueuedNotifyJob` declares
                // none, so there is no documented escape" cannot reach back three
                // clauses to offer the job as the escape's subject.
                if ($subjects === [] && in_array($id, BACKWARD_SUBJECT_FALLBACK, true)) {
                    [$altHead, $altScope] = assertionWindows($segment, $mo, strlen($mtext), ['side' => 'before', 'tight' => true]);
                    $trimmed = rtrim($altHead);
                    if ($trimmed !== '' && (str_ends_with($trimmed, '`') || str_ends_with($trimmed, '"'))) {
                        $altSubjects = extractSubjects($trimmed);
                        if ($altSubjects !== []) {
                            $head = $altHead;
                            $scope = $altScope;
                            $scopeSubjects = extractSubjects($scope);
                            $scopePaths = scopePathsOf($scopeSubjects, $idx);
                            $subjects = $altSubjects;
                            $considered[] = 'subject taken from the DEFINITE form behind the verb — `'.shorten($head).'`';
                        }
                    }
                }

                if ($subjects === []) {
                    // The DIRECTION is stamped onto the reason. unresolvableSubjectReason()
                    // is shared by both directions, and without this prefix an
                    // existence sentence that named nothing checkable is filed in
                    // the ledger under an absence reason — the tally then cannot be
                    // read, because 94 "names no code span" lines could be any
                    // mixture of the two checks. Silence is only auditable if a
                    // reader can tell WHICH check stayed silent.
                    $why = unresolvableSubjectReason($subjectWin.' '.$scopeWin, $head, $side);
                    $why = $isExis ? 'existence: '.$why : $why;
                    $skips[] = $why;
                    $considered[] = 'subject window "'.shorten($head).'" → '.$why;

                    continue;
                }

                $deadClassScoped = $isDead && in_array($id, DEADNESS_CLASS_SCOPED, true);
                $scopeNoun = scopeNounKind($scope);
                // The scope of a deadness claim is often on the far side of its
                // match from its subject — "… and NOTHING IN `src/` INVOKES them"
                // names `src/` after the assertion. Both windows count.
                $scopeIsSrc = stripos($subjectWin.' '.$scopeWin, 'src/') !== false;
                // What stands between the last named span and THIS verb. Measured
                // once per assertion; an existence verb whose subject position
                // holds an ordinary noun is about that noun, not about the span.
                $verbGap = $isExis ? verbGap($head) : '';

                foreach ($subjects as $s) {
                    [$kind, $name] = $s;
                    // "no `service()` ON `NotifyService`": the class the scope names
                    // is where the method claim is answered, not a subject itself.
                    if ($kind === 'method' && $s[3] === null) {
                        foreach ($scopeSubjects as $ss) {
                            if ($ss[0] === 'class') {
                                $s[3] = $ss[1];
                                $considered[] = "scope `class:{$ss[1]}` adopted as the owner of `method:{$name}`";

                                break;
                            }
                        }
                    }

                    if ($isDead) {
                        // A class subject only answers a class-scoped deadness claim;
                        // otherwise its presence says nothing about the claim's object.
                        if ($kind === 'class' && ! $deadClassScoped) {
                            $considered[] = "`class:{$name}` not eligible for this deadness phrasing";

                            continue;
                        }
                        if ($kind !== 'class' && $kind !== 'method') {
                            $considered[] = "`{$kind}:{$name}` not eligible for deadness";

                            continue;
                        }
                        [$verdict, $evidence] = verifyDeadness($idx, $kind, $name, $subjectWin, $scopeIsSrc);
                    } elseif ($isExis) {
                        // An existence claim is asked about the subject the sentence
                        // names, and its SCOPE is the location the sentence points
                        // at ("…is declared in `src/X/Foo.php`"), so a path in the
                        // scope narrows the class question instead of widening it.
                        // `$gap` is measured once per assertion, from the last span
                        // before the verb, so every subject of one verb is judged
                        // against the same subject position.
                        [$verdict, $evidence] = verifyExistence($idx, $s, $scopePaths, $segment, $verbGap);
                    } else {
                        [$verdict, $evidence] = verifyAbsence($idx, $s, $scopePaths, $head, $scopeNoun);
                    }

                    $checkedOne = true;
                    $considered[] = "`{$kind}:{$name}` => {$verdict}";
                    if ($verdict === 'contradicted') {
                        $contradicted[] = "{$kind}:{$name} — {$evidence}";
                    } elseif ($verdict === 'skip') {
                        // Stamped for the same reason as above: a skip from either
                        // direction is silence, and silence has to say which
                        // direction earned it.
                        $skips[] = $isExis ? 'existence: '.$evidence : $evidence;
                        $allConsistent = false;
                    } elseif ($isExis) {
                        $exisTrue = true;
                    } else {
                        $absTrue = true;
                    }
                }
            }

            if ($contradicted !== []) {
                $line = lineOfFirstMatch($unit['lines'], $hits);
                $key = $rel.':'.$line.':'.$segment;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $isExisSegment = false;
                foreach ($hits as [$hid]) {
                    if (str_starts_with($hid, 'exis-')) {
                        $isExisSegment = true;
                        break;
                    }
                }
                $findings[] = [
                    'file' => $rel,
                    'line' => $line,
                    'claim' => trim($segment),
                    'direction' => $isExisSegment ? 'existence' : 'absence',
                    'pattern' => implode(' / ', array_column($hits, 1)),
                    'evidence' => $contradicted,
                    'considered' => $considered,
                ];

                continue;
            }

            // A skip means "unanswerable", which is NOT the same as TRUE. The most
            // specific skip wins, and it wins over "the claim is TRUE" — an
            // assertion whose subject could not be resolved must never be counted
            // as evidence that the tree agrees with it.
            if ($skips !== []) {
                $why = mostSpecific($skips);
            } elseif (! $checkedOne) {
                $why = 'assertion too vague — names nothing verifiable';
            } elseif ($exisTrue && ! $absTrue) {
                // Its own string, not a shared "claim is TRUE". A reader has to be
                // able to tell "the tree HAS the thing this sentence says is there"
                // from "the tree agrees the thing is gone" — they are opposite
                // verdicts reached by opposite searches, and pooling them would
                // make the ledger unreadable in exactly the place it matters.
                $why = 'claim is TRUE — the tree DOES have the subject this existence assertion names';
            } elseif (! $allConsistent) {
                $why = 'claim is TRUE — the named thing is absent from the tree';
            } else {
                $why = 'claim is TRUE — the tree agrees the thing is absent or unreached';
            }
            $suppressed[$why] = ($suppressed[$why] ?? 0) + 1;
        }
    }

    return $findings;
}

/** The line the assertion actually sits on, so file:line points at the claim. */
function lineOfFirstMatch(array $lines, array $hits): int
{
    $ids = array_column($hits, 0);
    foreach ($lines as [$ln, $txt]) {
        // allPatterns(), not PATTERNS: an existence finding sits on a sentence
        // that carries no absence pattern at all, and PATTERNS alone would send
        // the report line to the top of the paragraph instead of the claim.
        foreach (allPatterns() as [$id, $_label, $re]) {
            if (in_array($id, $ids, true) && preg_match($re, $txt)) {
                return $ln;
            }
        }
    }

    return $lines[0][0] ?? 0;
}

/** Trim a window to a readable length for the "what we looked at" line. */
function shorten(string $s, int $max = 90): string
{
    $s = trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    if ($s === '') {
        return '(empty — the subject sits outside this assertion\'s own clause)';
    }

    return strlen($s) > $max ? substr($s, 0, $max - 1).'…' : $s;
}

/**
 * Which of several reasons to record. The most specific wins: a skip that names
 * a COLUMN beats a skip that only says the claim was vague, and the tally is the
 * audit record, so the reason printed has to be the one that explains the shape.
 */
function mostSpecific(array $reasons): string
{
    $best = '';
    foreach ($reasons as $r) {
        if (strlen((string) $r) > strlen($best)) {
            $best = (string) $r;
        }
    }

    return $best === '' ? 'assertion too vague — names nothing verifiable' : $best;
}

// -----------------------------------------------------------------------------
// 7. ARGUMENTS, --list, REPORT
// -----------------------------------------------------------------------------

$args = array_slice($argv, 1);
$list = in_array('--list', $args, true);
$quiet = in_array('--quiet', $args, true);
$paths = [];
foreach ($args as $a) {
    if (str_starts_with($a, '--path=')) {
        $paths[] = substr($a, 7);

        continue;
    }
    if (in_array($a, ['-h', '--help'], true)) {
        echo "usage: php bin/check-claim-drift.php [--list] [--quiet] [--strict] [--path=<file-or-dir>]\n";
        exit(EXIT_CLEAN);
    }
    if (! in_array($a, ['--list', '--quiet', '--strict'], true)) {
        fwrite(STDERR, "unknown argument: {$a}\nusage: php bin/check-claim-drift.php [--list] [--quiet] [--strict] [--path=<file-or-dir>]\n");
        exit(EXIT_USAGE);
    }
}

$root = resolveRoot();

if ($list) {
    echo "check-claim-drift — what this gate recognises\n";
    echo "============================================\n\n";
    echo "ABSENCE FAMILIES (a claim that something DOES NOT EXIST)\n";
    foreach (PATTERNS as [$id, $label, $re]) {
        if (str_starts_with($id, 'absent-')) {
            printf("  %-18s %s\n      %s\n", $id, $label, $re);
        }
    }
    echo "\nDEADNESS FAMILIES (a claim that something EXISTS BUT IS UNREACHED)\n";
    foreach (PATTERNS as [$id, $label, $re]) {
        if (str_starts_with($id, 'dead-')) {
            printf("  %-18s %s\n      %s\n", $id, $label, $re);
        }
    }
    echo "\nEXISTENCE FAMILIES (a claim that something DOES exist)\n";
    foreach (EXISTENCE_PATTERNS as [$id, $label, $re]) {
        printf("  %-18s %s\n      %s\n", $id, $label, $re);
    }
    echo "  every one reads its subject BEFORE the verb, because \"X exists\" is a\n";
    echo "  definite claim with a named subject. A positive assertion whose subject\n";
    echo "  does not precede its verb is not read at all.\n";
    echo "\nEXISTENCE — WHAT IS RESOLVED, AND WHAT IS SILENCE INSTEAD\n";
    echo "  resolved  MeRezaRezaei\\Teleframe\\…   a FQCN under this package's own\n";
    echo "            namespace: declared in src/, tests/ or bin/, or not.\n";
    echo "  resolved  src/Foo/Bar.php             a repo-relative FILE path that is\n";
    echo "            not a glob, an ellipsis or a directory.\n";
    echo "  resolved  Class::method()             only when the span names its own class.\n";
    echo "  SUPPRESS  Foo                         a SHORT class name with no in-tree\n";
    echo "            path in the same sentence — it never says whose class it is,\n";
    echo "            and 'we have no such class' is not the same as 'it does not exist'.\n";
    echo "  SUPPRESS  Illuminate\\…\\Str           a FQCN outside this package: a\n";
    echo "            dependency is not vendored here, so absence proves nothing.\n";
    echo "  SUPPRESS  wire / command / config     not resolved for existence at all.\n";
    echo "  SUPPRESS  a qualified subject         (\"the `X` facade is declared\") — a\n";
    echo "            qualified registry is answered for absence, not for existence.\n";
    echo "  SUPPRESS  an intervening noun        \"`A::b()` … work before any credential\n";
    echo "            exists\" is about the credential. The named span must be in the\n";
    echo "            SUBJECT POSITION of its own verb, and only copulas, modals and\n";
    echo "            adverbs may sit between a span and that verb.\n";
    echo "\nPLANNED WORK IS NOT DRIFT — the frames that silence an existence assertion\n";
    echo "  question about the tree | future tense (will / shall / going to)\n";
    echo "  not yet                 | requirement, not a report (must be / should be)\n";
    echo "  planned work            | (planned / roadmap / upcoming / later / proposed)\n";
    echo "  A name that does not exist YET is a FALSE-CLAIM shape, not a drift: \"we will\n";
    echo "  add X\" is a true sentence about the future. Every frame above is counted\n";
    echo "  in the tally, so the silence is auditable rather than invisible.\n";
    echo "\nASSERTION GRAMMAR — which side of the match the subject is on\n";
    printf("  %-18s %-8s %s\n", 'pattern', 'side', 'note');
    foreach (PATTERNS as [$id, $label, $re, $opt]) {
        $side = $opt['side'] ?? 'after';
        if ($side !== 'before') {
            continue;
        }
        $note = match ($id) {
            'absent-absent' => 'backward window also stops at ", that" / ", which"',
            'dead-nothingcalls' => 'a bare " and" does NOT cut it off — the subject is joined to its own predicate',
            default => '',
        };
        printf("  %-18s %-8s %s\n", $id, $side, $note);
    }
    echo "  every other pattern   after    subject FOLLOWS the match\n";
    echo '  definite negatives ('.implode(', ', BACKWARD_SUBJECT_FALLBACK).") may also read\n";
    echo "                        behind    a subject that abuts the verb, when the forward\n";
    echo "                                 window holds none — \"`MessageFactory` does not exist\".\n";
    echo "\nSUBJECT KINDS a claim must name to be verifiable\n";
    echo "  path      src/Foo/Bar.php       resolved as a path in the tree\n";
    echo "  class     FooBar, Ns\\FooBar     resolved against class/interface/trait/enum declarations\n";
    echo "  method    fooBar()              resolved against PHP method DECLARATIONS\n";
    echo "  wire      upload.getFile        resolved against a `const METHOD = '...'` BINDING,\n";
    echo "                                  never against a schema/JSON mention\n";
    echo "  command   teleframe:tstore      resolved against protected \$signature\n";
    echo "  config    TELEFRAME_X, a.b.c    resolved against env()/key literals in src/**/config/*.php\n";
    echo "  bare      acked                 kind unknown → NEVER answered, always suppressed by name\n";
    echo "\nSILENCE RULES\n";
    echo "  Every assertion is cut into a SUBJECT window and a SCOPE window at the\n";
    echo "    nearest clause boundary. A span outside its own window cannot refute it.\n";
    echo "  A span inside brackets, or pinned to a line with `:40`, is a CITATION.\n";
    echo "  A method claim naming a class is answered against THAT class, not tree-wide.\n";
    echo "  A claim qualifying its subject (\"no `Vault` FACADE\") is answered against the\n";
    echo "    qualified kind.\n";
    echo "  A subject the sentence calls a COLUMN, TABLE, INDEX or BINARY is never\n";
    echo "    answered against a code symbol of the same spelling.\n";
    echo "  An absence \"in the ledger / the doc / the record\" is a DOCUMENT claim and is\n";
    echo "    never checked against the tree.\n";
    echo "  A runtime error string (\"NOGROUP No such key\") is not a claim.\n";
    echo "  Class deadness is refuted only by construction/static use in production code.\n";
    echo "  Method deadness is checked only when the claim names `src/` as its scope.\n";
    echo "  This gate never reads .env values and never executes a documented command.\n";
    echo "\nSCAN SET\n";
    foreach (SCAN_DIRS as $d) {
        echo "  dir   {$d}/\n";
    }
    foreach (SCAN_FILES as $f) {
        echo "  file  {$f}\n";
    }
    echo "\nDELIBERATELY NOT SCANNED\n";
    foreach (SCAN_EXCLUDED as $p => $why) {
        printf("  %-30s %s\n", $p, $why);
    }
    exit(EXIT_CLEAN);
}

$idx = new TreeIndex($root);
$idx->build();

$docs = [];
if ($paths !== []) {
    foreach ($paths as $p) {
        $abs = str_starts_with($p, '/') ? $p : $root.'/'.$p;
        // A file outside the tree is named by its own path, never truncated to
        // whatever a repo-relative slice of it would happen to be.
        $label = static function (string $abs) use ($root): string {
            $real = realpath($abs) ?: $abs;

            return str_starts_with($real, $root.'/') ? substr($real, strlen($root) + 1) : $real;
        };
        if (is_file($abs)) {
            $docs[$label($abs)] = $abs;
        } elseif (is_dir($abs)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.md')) {
                    $docs[$label($f->getPathname())] = $f->getPathname();
                }
            }
        } else {
            fwrite(STDERR, "no such path: {$p}\n");
            exit(EXIT_USAGE);
        }
    }
} else {
    foreach (SCAN_DIRS as $d) {
        $abs = $root.'/'.$d;
        if (is_dir($abs)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($abs, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.md')) {
                    $docs[substr($f->getPathname(), strlen($root) + 1)] = $f->getPathname();
                }
            }
        }
    }
    foreach (SCAN_FILES as $f) {
        if (is_file($root.'/'.$f)) {
            $docs[$f] = $root.'/'.$f;
        }
    }
}
ksort($docs);

$suppressed = [];
$findings = [];
foreach ($docs as $rel => $abs) {
    foreach (scanFile($abs, $rel, $idx, $suppressed) as $finding) {
        $findings[] = $finding;
    }
}
$suppressedTotal = array_sum($suppressed);

if ($quiet) {
    printf("tree %s | docs %d | flagged %d | suppressed %d\n", $root, count($docs), count($findings), $suppressedTotal);
} else {
    echo "check-claim-drift — docs and ledgers vs the tree\n";
    echo "==============================================\n\n";
    printf("tree measured : %s\n", $root);
    echo "  resolved by : git rev-parse --show-toplevel  (the invocation, never this script's location)\n";
    printf("php files read: %d   (.env excluded at the walk level and never opened)\n", count($idx->readFiles));
    printf("documents read: %d\n\n", count($docs));
}

if ($findings !== []) {
    // The banner names the DIRECTION, because the two sentences are opposite and
    // a reader who saw a fabricated class filed under "the tree has outgrown this"
    // would be told something false about what the check found.
    $anyExis = false;
    $anyAbs = false;
    foreach ($findings as $f) {
        if ($f['direction'] === 'existence') {
            $anyExis = true;
        } else {
            $anyAbs = true;
        }
    }
    $banner = match (true) {
        $anyAbs && $anyExis => 'CLAIMS THAT DO NOT MATCH THE TREE, IN BOTH DIRECTIONS — ',
        $anyExis => 'CLAIMS THAT SOMETHING EXISTS WHEN THE TREE DOES NOT HAVE IT — ',
        default => 'CLAIMS THE TREE HAS OUTGROWN — ',
    };
    printf("%s%d\n\n", $banner, count($findings));
    foreach ($findings as $i => $f) {
        printf("%d) %s:%d\n", $i + 1, $f['file'], $f['line']);
        printf("   claim    : %s\n", $f['claim']);
        printf("   pattern  : %s\n", $f['pattern']);
        echo "   tree says:\n";
        foreach ($f['evidence'] as $e) {
            printf("               - %s\n", $e);
        }
        printf("   checked  : %s\n", implode('; ', $f['considered']));
        echo "\n";
    }
}

/*
 * EXIT POLICY — THIS TOOL IS A REPORT, NOT A GATE. DELIBERATE, AND UNCHANGED.
 *
 * HISTORY, because it is the whole reason the report mode exists. Measured on
 * this tree, 2026-10-16: it flagged 16 claims in docs/ and at least THREE of
 * those were false, each for the same reason — it resolved the wrong SUBJECT,
 * because the subject pool was the whole sentence rather than the assertion's
 * own window. A gate that is red on a clean tree is a gate the operator switches
 * off, and the one time its output mattered would be the one time it is ignored
 * — which is strictly worse than having no such tool, because it converts a
 * missing check into a trusted one.
 *
 * WHERE IT STANDS AFTER THE SCOPING FIX (2026-10-16, same day). Those three
 * named false positives are gone, and the reasoning is reproducible rather than
 * asserted — see .brain/evidence/2026-10-16-claim-drift-scope.md, which pastes
 * both directions: four planted claims that are FALSE and all four are flagged
 * (class, path, command, and the definite "X does not exist" form), five planted
 * claims that are TRUE and all five are silent.
 *
 * WHY IT IS STILL NOT A GATE, even with a clean run. "Zero findings on docs/"
 * after a fix that removed false positives is ALSO what "zero sensitivity" looks
 * like, and the two are indistinguishable from the outside. The discrimination
 * evidence is a 9-claim synthetic corpus, not a corpus of real documentation
 * whose true drift status is known. Promoting this on my own judgement would be
 * the exact error the policy exists to prevent.
 *
 * Therefore: findings are REVIEW CANDIDATES. Default exit is 0, whatever it
 * finds. `--strict` opts into gating, and is the honest choice ONLY once the
 * false-positive rate has been driven to zero on a corpus large enough that
 * "CLEAN" means the tree moved rather than the matcher going deaf.
 *
 * THE SECOND DIRECTION DOES NOT CHANGE THIS, and the reason is worth stating
 * rather than assuming. The existence direction added on 2026-10-17 is gated by
 * exactly the same report policy, and its evidence is the SAME KIND of evidence:
 * a twelve-sentence synthetic corpus of planted pairs — a present-tense false
 * claim beside a prospective twin of the same shape, where the control must be
 * flagged and the plan must not. That proves discrimination on sentences whose
 * truth is known because it was constructed. It is NOT a corpus of real
 * documentation whose drift status is known, and the real-tree run is silent for
 * both directions. Zero findings on docs/ is exactly what "zero sensitivity"
 * looks like; the two remain indistinguishable from the outside. Adding a
 * direction does not earn promotion, so promotion is not taken.
 */
printf(
    "VERDICT: %s — %d review candidate(s), %d suppressed.\n",
    $findings === [] ? 'CLEAN' : 'CANDIDATES',
    count($findings),
    $suppressedTotal
);

if ($findings !== [] && ! $quiet) {
    echo "\n  These are CANDIDATES for human review, not proven defects. Each one\n";
    echo "  must be read before it is believed: scoping removed three measured\n";
    echo "  false positives on 2026-10-16 (wrong subject resolved), and the\n";
    echo "  remaining candidates carry no such excuse. See the exit-policy note.\n";
}

if (! $quiet) {
    echo "\nSILENCE IS AUDITABLE — what was suppressed and why:\n";
    arsort($suppressed);
    foreach ($suppressed as $why => $n) {
        printf("  %4d  %s\n", $n, $why);
    }
    echo "\nDELIBERATELY NOT SCANNED (archives record a past state; flagging them would flag the record):\n";
    foreach (SCAN_EXCLUDED as $p => $why) {
        printf("  %-30s %s\n", $p, $why);
    }
    echo "\nOverride with --path=<file-or-dir> to scan any of the above.\n";
}

// Default is REPORT: exit 0 even with candidates. `--strict` opts into gating.
if ($findings !== [] && in_array('--strict', $argv, true)) {
    exit(EXIT_FINDINGS);
}

exit(EXIT_CLEAN);
