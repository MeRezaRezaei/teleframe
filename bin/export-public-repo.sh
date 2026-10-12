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
#   files exported, files excluded, and the exact `git push` command.
#
set -euo pipefail

TARGET="${1:-/tmp/teleframe-public}"

ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

CHECKER="bin/check-public-surface.php"
MANIFEST="bin/public-manifest.txt"

say()  { printf '%s\n' "$*"; }
fail() { printf 'export-public-repo: %s\n' "$*" >&2; exit 2; }

# ------------------------------------------------------------------------------
# 1. Preconditions. Each one names its own failure, because "it printed
#    something and then stopped" is not a diagnosis.
# ------------------------------------------------------------------------------
[ -f "$CHECKER" ]  || fail "the gate is missing: $CHECKER"
[ -f "$MANIFEST" ] || fail "the manifest is missing: $MANIFEST"
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
while IFS= read -r path; do
    [ -n "$path" ] || continue
    # A path the gate lists but HEAD does not have is a path that is not yet
    # committed. That is not an error here — it is simply not publishable, and
    # saying so is better than exporting an uncommitted version of it.
    if git cat-file -e "HEAD:$path" 2>/dev/null; then
        mkdir -p "$TARGET/$(dirname -- "$path")"
        git show "HEAD:$path" > "$TARGET/$path"
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
    git add -A
    git -c user.name="teleframe export" \
        -c user.email="export@localhost" \
        commit -q -m "chore(public): export the public surface of teleframe

Produced by bin/export-public-repo.sh from the private repository.
Source of the file list: $SOURCE."
)

say ""
say "================================================================"
say " PUBLIC TREE READY — nothing has been published."
say "================================================================"
say " target        : $TARGET"
say " exported      : $EXPORTED files"
say " excluded      : $EXCLUDED files (private by $MANIFEST)"
say " list source   : $SOURCE"
say ""
say " To publish, read the tree first, then run:"
say ""
say "   git -C $TARGET remote add origin git@github.com:MeRezaRezaei/teleframe.git"
say "   git -C $TARGET push --force origin main"
say ""
say " The push rewrites the public branch and is NOT undoable by this script."
say "================================================================"