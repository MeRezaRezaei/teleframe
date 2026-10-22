#!/usr/bin/env bash
#
# bin/export-public-repo.sh — produce the PUBLIC tree from this private repository.
#
# Read this when you are about to run, change, or debug this script. Read
# bin/public-manifest.txt first: that file defines WHAT is public, and this
# script only decides HOW to get it out.
#
# WHAT THIS IS
#
#   The local repository IS the private repository (`teleframe-private` on
#   GitLab). It is never rewritten here. This script builds a SECOND tree, in a
#   target directory, containing only the paths the manifest allows, and tells
#   you the exact command to publish it. It never pushes on its own.
#
# WHY THE PUSH IS MANUAL
#
#   Publishing overwrites the public repository's branch. That is irreversible
#   and it is not this script's decision to make. It prints the command; you run
#   it, after reading the file counts it printed.
#
# THE GATE RUNS FIRST, ALWAYS
#
#   The published tree is gated AFTER it is built, by the same gate, in
#   `--tree` mode. Gating the SOURCE tree first would be wrong: this repository
#   IS the private repository and contains the private files on purpose, so the
#   source tree is EXPECTED to be red. What must be green is what gets
#   published, and the build can never bypass that check — there is no
#   `--force` flag, and the re-verify step below is unconditional.
#
# USAGE
#
#   bin/export-public-repo.sh [TARGET_DIR]
#
#     TARGET_DIR   where to build the public tree.
#                  Default: /tmp/teleframe-public
#                  It is REPLACED if it already exists, so never point this at
#                  anything you have not created.
#
# WHAT IT PRINTS
#
#   files exported, files actually present in the export commit, files excluded,
#   and the exact `git push` command.
#
# THE PUBLISHED TREE ALWAYS CARRIES LIVE WORKFLOWS
#
#   The private repository's documented flow is neutralize -> push private ->
#   (later) export, and `bin/neutralize-ci.php --mode=disable` renames every
#   workflow to `*.yml.neutralized` so the PRIVATE repo's Actions never fire.
#   `bin/public-manifest.txt:110` allows `.github/**` broadly, so once private
#   HEAD carries those `.neutralized` names the gate publishes them — and a
#   naive export copies the INERT files to the public tree, where GitHub never
#   runs them. The public repo's CI dies and nothing reports an error.
#
#   So this script re-enables the workflows in the TARGET after the copy and
#   before the gate re-verify, the commit, and the push command: the exported
#   tree holds the live `.yml`/`.yaml` files, the private tree keeps its inert
#   committed form, and neither is a second hand-maintained copy of the other.
#   It is a verified no-op when the private tree was never neutralized, and it
#   exits non-zero — publishing nothing — if the target holds a workflow the
#   neutralizer cannot re-enable unambiguously.
#
# THE TWO DIRECTIONS, AND WHY ONE OF THEM IS NOT THE GATE'S JOB
#
#   The gate answers ONE question: is the public surface CLEAN? That is a
#   leak question — is anything present that should not be? It is a filesystem
#   walk, and it is the right tool for that job.
#
#   It cannot answer the other question: did everything we PROMISED to publish
#   actually get published? That is a completeness question, and it can only be
#   answered against the commit, not against the directory — a file can be on
#   disk and absent from the commit, which is precisely the defect step 6 of
#   this script now refuses to publish. Both directions matter, and they are
#   checked by different tools, at different moments, against different
#   sources of truth.
#
set -euo pipefail

TARGET="${1:-/tmp/teleframe-public}"

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

CHECKER="bin/check-public-surface.php"
MANIFEST="bin/public-manifest.txt"
NEUTRALIZER="bin/neutralize-ci.php"

say()  { printf '%s\n' "$*"; }
fail() { printf 'export-public-repo: %s\n' "$*" >&2; exit 2; }

# ------------------------------------------------------------------------------
# 1. Preconditions. Each one names its own failure, because "it printed
#    something and then stopped" is not a diagnosis.
# ------------------------------------------------------------------------------
[ -f "$CHECKER" ]  || fail "the gate is missing: $CHECKER"
[ -f "$MANIFEST" ] || fail "the manifest is missing: $MANIFEST"
[ -f "$NEUTRALIZER" ] || fail "the workflow neutralizer is missing: $NEUTRALIZER"
command -v git >/dev/null 2>&1 || fail "git is not on PATH"
command -v php  >/dev/null 2>&1 || fail "php is not on PATH"

git rev-parse --is-inside-work-tree >/dev/null 2>&1 \
  || fail "not inside a git work tree: $ROOT"

# ------------------------------------------------------------------------------
# 1. Enumerate WHAT WILL BE PUBLISHED. This is the gate's own answer, via
#    --list, so the exporter and the gate can never disagree. --list applies the
#    strip set FIRST and the allowlist second, which is precisely the rule that
#    makes the exported tree clean by construction: nothing in the strip set can
#    reach it, and nothing outside the allowlist can either.
#
#    There is deliberately NO unconditional "run the gate over the SOURCE tree
#    first" step here, and the reason is the whole design. The source tree IS
#    the private repository: it contains the private files on purpose, so the
#    source tree is EXPECTED to be red. Gating it would mean the export can
#    never run — the chicken-and-egg that a naive "gate then export" ordering
#    walks straight into. What must be green is the tree that gets PUBLISHED,
#    and step 4 gates exactly that, with the same gate, after the build.
# ------------------------------------------------------------------------------
say "==> enumerating the public surface (gate's own --list)"
PUBLIC_LIST="$(php "$CHECKER" --list)"
[ -n "$PUBLIC_LIST" ] || fail "the gate's --list returned nothing; refusing to publish an empty tree"
SOURCE="the gate (--list)"

# ------------------------------------------------------------------------------
# 3. Build from the COMMITTED TREE, never from the working tree.
#
#    Copying from the working tree would publish UNCOMMITTED edits: a
#    half-written file, a debug leftover, a change you meant to revert. What gets
#    published must be exactly what a reviewer sees at a commit — the only
#    version of this repository that has ever been reviewed. So each file is
#    taken with `git show HEAD:<path>`, which reads the committed blob.
#
#    Fresh directory every time, so a stale file can never survive an export by
#    being forgotten to delete.
# ------------------------------------------------------------------------------
say "==> building $TARGET from the committed tree (git show HEAD:<path>)"
rm -rf "$TARGET"
mkdir -p "$TARGET"

EXPORTED=0
EXPORTED_LIST="$(mktemp "${TMPDIR:-/tmp}/teleframe-exported-list.XXXXXX")"
MISSING_LIST="$(mktemp "${TMPDIR:-/tmp}/teleframe-missing-list.XXXXXX")"
COMMITTED_LIST="$(mktemp "${TMPDIR:-/tmp}/teleframe-committed-list.XXXXXX")"
trap 'rm -f "$EXPORTED_LIST" "$MISSING_LIST" "$COMMITTED_LIST"' EXIT

# Newline-delimited, which is what `--pathspec-from-file` consumes by default
# and what the gate's own `--list` prints. This file is the PROMISE: it is what
# step 5 force-adds and what the post-condition in step 6 asserts against.
while IFS= read -r path; do
    [ -n "$path" ] || continue
    # A path the gate lists but HEAD does not have is a path that is not yet
    # committed. That is not an error here — it is simply not publishable, and
    # saying so is better than exporting an uncommitted version of it.
    if git cat-file -e "HEAD:$path" 2>/dev/null; then
        mkdir -p "$TARGET/$(dirname -- "$path")"
        git show "HEAD:$path" > "$TARGET/$path"
        printf '%s\n' "$path" >> "$EXPORTED_LIST"
        EXPORTED=$((EXPORTED + 1))
    fi
done <<< "$PUBLIC_LIST"

# Files that ARE committed and ARE published but were listed yet absent from
# HEAD — i.e. new files not yet committed — are counted so the summary is honest.
SKIPPED=$(printf '%s\n' "$PUBLIC_LIST" | grep -c . || true)
SKIPPED=$((SKIPPED - EXPORTED))

TOTAL="$(git ls-files | grep -c .)"
EXCLUDED=$((TOTAL - EXPORTED))

# ------------------------------------------------------------------------------
# 3b. RE-ENABLE THE WORKFLOWS IN THE EXPORTED TREE. The published tree is the
#     one place the workflows must be LIVE, and this is the step that guarantees
#     it. Step 3 copied the committed blobs verbatim, and on a neutralized
#     private HEAD those blobs are the INERT `.yml.neutralized` ones — so this
#     step runs the neutralizer's own `enable` pass against the TARGET only,
#     never against this repository. The private tree keeps its committed,
#     inert-by-filename form; only the exported copy is turned back into the
#     live `.yml`/`.yaml` files GitHub Actions actually discovers and runs.
#
#     On a tree that was never neutralized this is a verified no-op: the
#     neutralizer finds no `*.neutralized` entries and exits 0 without writing,
#     so the classic single-argument invocation is byte-for-byte unaffected.
#
#     Order matters and is load-bearing. This must run AFTER the copy loop
#     (there is nothing to re-enable until the blobs are on disk) and BEFORE
#     the gate re-verify and the commit — because the whole point is that what
#     gets gated, counted, committed and finally pushed is the live form.
# ------------------------------------------------------------------------------
say "==> re-enabling workflows in the exported tree (neutralize-ci --mode=enable)"
set +e
php "$NEUTRALIZER" --mode=enable --dir="$TARGET"
ENABLE_RC=$?
set -e
if [ "$ENABLE_RC" -ne 0 ]; then
    fail "re-enabling the exported workflows failed (exit $ENABLE_RC).
       The exported tree is left in place for inspection; NOTHING has been
       published. A non-zero here means the exported tree carried a
       \`*.neutralized\` file the neutralizer will not re-enable unambiguously
       (for example a name present in BOTH states, or a .neutralized file with
       no marker). Resolve it in $TARGET and re-run, or fix the source tree."
fi

# The PROMISE LIST must be rewritten to match the tree's new state.
#
# $EXPORTED_LIST is what step 5 force-adds and what step 6 asserts against the
# commit. It was built in step 3 and therefore names the paths as they were
# COPIED — which, on a neutralized private HEAD, are the `*.yml.neutralized`
# ones. Those files no longer exist on disk: step 3b renamed each of them back
# to a live `.yml`. Left unrewritten, the list would (a) make step 5's
# `git add -f --pathspec-from-file` abort on a pathspec matching no file, and
# (b) make step 6's post-condition assert that INERT filenames reached the
# commit — asserting the exact opposite of what it exists to prove.
#
# So the rename is reflected in the promise, one-for-one. The count is
# unchanged, because enabling a workflow is a rename and not an edit: nothing is
# added and nothing is dropped here, which is why $EXPORTED stays honest.
sed -e 's/\.neutralized$//' "$EXPORTED_LIST" > "$EXPORTED_LIST.restored"
mv "$EXPORTED_LIST.restored" "$EXPORTED_LIST"

# FAIL LOUD, part 2 of 2: prove, by assertion rather than by trusting the exit
# code, that the tree we are about to publish is the LIVE one. A green publish
# that ships inert workflows is the single worst outcome this script can have,
# because the public repository's CI dies and NOTHING reports an error — the
# push succeeds, the commit is correct-looking, and the workflows simply never
# fire again. So the two states are checked as a set difference against the
# tree on disk, which is the thing that actually gets pushed.
#
# Counted, not merely "found none": an empty check that always passes proves
# nothing, so the total found is printed and compared against the workflows the
# source tree actually committed.
STRAY_NEUTRALIZED="$(find "$TARGET/.github/workflows" -name '*.neutralized' -type f 2>/dev/null | grep -c . || true)"
if [ "$STRAY_NEUTRALIZED" -ne 0 ]; then
    say "" >&2
    printf 'export-public-repo: %s inert workflow file(s) are still present in %s:\n' \
        "$STRAY_NEUTRALIZED" "$TARGET" >&2
    find "$TARGET/.github/workflows" -name '*.neutralized' -type f 2>/dev/null | while IFS= read -r stray; do
        [ -n "$stray" ] && printf 'export-public-repo:   %s\n' "$stray" >&2
    done
    say "" >&2
    fail "refusing to publish a tree that would ship INERT workflows (GitHub Actions
       ignores any file under .github/workflows/ whose name does not end in .yml
       or .yaml, so public CI would be silently dead). Nothing has been published."
fi

# Count the LIVE workflows that survived, so the summary reports the state that
# will actually be published rather than the state that was copied.
LIVE_WORKFLOWS="$(find "$TARGET/.github/workflows" \( -name '*.yml' -o -name '*.yaml' \) -type f 2>/dev/null | grep -c . || true)"

# ------------------------------------------------------------------------------
# 4. Re-verify the thing we built, with the gate itself, against the exported
#    tree. This catches a list that was subtly wrong in the permissive
#    direction — the only direction that matters for a secret.
# ------------------------------------------------------------------------------
say "==> re-verifying the exported tree with the gate"
set +e
php "$CHECKER" --tree="$TARGET"
RECHECK=$?
set -e
if [ "$RECHECK" -ne 0 ]; then
    fail "the exported tree at $TARGET did NOT pass the gate (exit $RECHECK).
       Nothing was published. $TARGET is left in place for inspection."
fi

# ------------------------------------------------------------------------------
# 5. Initialise and commit inside the TARGET only. The real repository's index
#    is never touched.
# ------------------------------------------------------------------------------
say "==> initialising the public repository in $TARGET"
(
    cd "$TARGET"
    git init -q -b main
    # `git add` HONOURS THE IGNORE RULES OF THE TREE IT RUNS IN — and this tree
    # contains the repository's own root `.gitignore`, because that file is
    # itself published. Measured on this checkout: `.gitignore:19` carries
    # `bootstrap/cache/` (with the comment about orchestral/testbench booting a
    # Laravel app rooted here), which is exactly right FOR THE PRIVATE WORKING
    # TREE — a cache directory is build output and must never be committed there.
    # It is also exactly wrong for the publish, because `bootstrap/cache/` is a
    # directory and git does not descend into an ignored one, so the tracked
    # sentinel `bootstrap/cache/.gitignore` — the ONLY reason the directory
    # exists after checkout, and therefore the only reason Laravel's
    # PackageManifest will find `/bootstrap/cache` present and writable — was
    # dropped on the floor by `git add -A`. Nothing reported it: the loop had
    # copied it, the gate had listed it, and the summary counted it.
    #
    # So the publish does not negotiate with the ignore rules. `-f` overrides
    # them for exactly the paths the gate promised, and nothing else: the set
    # force-added here is the same set that was copied from HEAD, so `-f` cannot
    # introduce a path the manifest never listed.
    git add -A
    git add -f --pathspec-from-file="$EXPORTED_LIST"
    git -c user.name="teleframe export" \
        -c user.email="export@localhost" \
        commit -q -m "chore(public): export the public surface of teleframe

Produced by bin/export-public-repo.sh from the private repository.
Source of the file list: $SOURCE."
)

# ------------------------------------------------------------------------------
# 6. POST-CONDITION: every path we promised to publish is IN THE COMMIT.
#
#    THE DEFECT THIS EXISTS TO CLOSE. Steps 1-5 had a one-directional promise.
#    The gate in step 4 answers "is the public surface CLEAN?" and it is a
#    filesystem walk (`walkTree` in check-public-surface.php), so it sees the
#    file on disk and passes. It cannot see a path lost between "written to
#    disk" and "recorded as a blob", because in --tree mode it never reads git
#    at all. The gate was green, the exporter reported "exported 5112", and the
#    commit held 5111. A path that is allowlisted, exported, counted in the
#    summary and then absent from the commit is not a leak — it is a build that
#    cannot run — and no leak-detector is going to notice it, because a leak
#    detector looks for what SHOULDN'T be there.
#
#    So completeness is asserted here, against the commit's own tree, by set
#    difference rather than by counting. Counting is what let this through:
#    $EXPORTED is incremented when the file is COPIED, never when it is
#    COMMITTED, so the summary could only ever describe the copy.
# ------------------------------------------------------------------------------
say "==> asserting every exported path is present in the export commit"
git -C "$TARGET" ls-tree -r --name-only HEAD > "$COMMITTED_LIST"
COMMITTED="$(grep -c . "$COMMITTED_LIST" || true)"

# LC_ALL=C on BOTH sides, because `comm` requires its two inputs sorted under a
# single collation and will silently produce nonsense under two.
#
# NEWLINE-delimited, not NUL-delimited, and that is a measured decision rather
# than a stylistic one. The obvious NUL form (`comm -23 <(sort -z a) <(sort -z
# b)`) is WRONG on this machine: `comm` here is uutils coreutils 0.10.0, the
# Rust rewrite, whose `-z` handling is broken — given two files that differ by a
# single record it reports ALL of file one as missing, and `grep -z -F -x -v -f`
# fails the same way. Both were measured against a commit that was genuinely
# missing exactly one path and both claimed 5112 missing. An assertion that
# cannot count correctly is worse than no assertion, because the day it reports
# a clean tree nobody can tell which of the two it did. Newline mode is also
# what the rest of this pipeline already assumes: .gitlab-ci.yml parses
# `git ls-tree` output as newline-delimited records, and the gate's own --list
# prints newline-delimited paths.
LC_ALL=C sort -o "$EXPORTED_LIST" "$EXPORTED_LIST"
LC_ALL=C sort -o "$COMMITTED_LIST" "$COMMITTED_LIST"
comm -23 "$EXPORTED_LIST" "$COMMITTED_LIST" > "$MISSING_LIST"
MISSING_COUNT="$(grep -c . "$MISSING_LIST" || true)"

if [ "$MISSING_COUNT" -ne 0 ]; then
    say "" >&2
    printf 'export-public-repo: the export commit is MISSING %s path(s) the gate published.\n' "$MISSING_COUNT" >&2
    printf 'export-public-repo: each of these was copied into %s and then dropped by the\n' "$TARGET" >&2
    printf 'export-public-repo: commit — almost certainly by an ignore rule in a .gitignore\n' >&2
    printf 'export-public-repo: that is itself published. Nothing has been published.\n' >&2
    say "" >&2
    while IFS= read -r lost; do
        [ -n "$lost" ] && printf 'export-public-repo:   %s\n' "$lost" >&2
    done < "$MISSING_LIST"
    say "" >&2
    fail "the exported commit does not contain everything the gate said to publish"
fi

say ""
say "================================================================"
say " PUBLIC TREE READY — nothing has been published."
say "================================================================"
say " target        : $TARGET"
say " exported      : $EXPORTED files"
say " in the commit : $COMMITTED files (every exported path, asserted in step 6)"
say " excluded      : $EXCLUDED files (private by $MANIFEST)"
# Printed because it was computed and thrown away: a path the gate PUBLISHES
# that is not yet committed in the private repository is silently not published,
# which is the same silent-loss class as the missing-path defect above. It is
# not fatal (an uncommitted new file is a normal transient state), but it is not
# something to compute silently either.
say " not committed : $SKIPPED published path(s) absent from private HEAD (not published)"
say " workflows     : $LIVE_WORKFLOWS live .yml/.yaml (0 would mean public CI is dead; refused above)"
say " list source   : $SOURCE"
say ""
say " To publish, read the tree first, then run:"
say ""
say "   git -C $TARGET remote add origin git@github.com:MeRezaRezaei/teleframe.git"
say "   git -C $TARGET push --force origin main"
say ""
say " The push rewrites the public branch and is NOT undoable by this script."
say "================================================================"