#!/usr/bin/env bash
# =============================================================================
# bin/resolve-publish-baseline.sh — WHICH PRIVATE COMMIT IS THE PUBLIC COPY A
# PUBLISH OF?
#
# WHY THIS IS A SCRIPT AND NOT A BLOCK INSIDE .gitlab-ci.yml. The answer used to
# live in a YAML shell blob, which is why nobody could test it and why it broke
# silently. Measured 2026-10-18: it scanned a hardcoded 50-commit window, the
# public branch was 55 commits stale, no candidate matched, the job fell back to
# comparing the public tip against private HEAD, reported 168 paths of "content
# drift", and refused to push — so GitHub received nothing at all and every CI
# gate in the project went dark. A bounded search answers an unbounded question
# and its answer EXPIRES; the window was 50 and the distance was 55.
#
# THE ANSWER IS ALREADY ON THE PUBLIC BRANCH. Every publish commit says which
# private commit produced it:
#
#   chore(public): publish the public surface of teleframe. Produced by
#   bin/export-public-repo.sh from the private repository (<sha> on main), …
#
# That sha is a CANDIDATE, never a conclusion. It is accepted only when the same
# blob-for-blob comparison the gate already performs passes against it, so a
# message that was edited, forged or left stale by a rewritten history fails
# verification and the search runs anyway. THE GATE IS NOT WEAKENED BY THIS
# SCRIPT; IT IS ONLY NO LONGER ABLE TO EXPIRE.
#
# ORDER, and why each step falls through to the next:
#   1. the sha the public tip DECLARES  — exact, O(1), and never expires
#   2. a newest-first SEARCH            — the old answer, kept as the fallback,
#                                        window raised so it is not the first
#                                        thing that breaks again
#   3. nothing                           — the caller falls back to comparing
#                                        against private HEAD and FATALs, exactly
#                                        as it does today. An out-of-band edit
#                                        still fails here; it just fails against
#                                        the right tree instead of the newest one.
#
# USAGE
#   bin/resolve-publish-baseline.sh \
#       --public-dir=DIR   a checkout of the public repository
#       --list=FILE        the published-path list (check-public-surface --list)
#       --tree=FILE        `git -C DIR ls-tree -r HEAD` of that public checkout
#       [--repo=DIR]       the private repository (default: this script's repo)
#       [--search=N]       fallback window (default 200)
#
# OUTPUT — stdout, one line per fact, so a CI log is the evidence:
#   declared   : <sha> | none
#   source     : declared | search(<n>) | none
#   RESOLVED <sha>          exit 0
#   UNRESOLVED <reason>     exit 1
#
# The comparison itself is unchanged from the version this replaces: `ls-tree -r`
# prints "<mode> <type> <sha>\t<path>", the blob is field 3 of $1 and the path is
# $2, the split is on TAB so a path containing a space is not misread, and the
# private side is keyed with FILENAME == PRIV rather than FNR == NR because that
# idiom silently treats the first line of the second file as private data when the
# first file is empty — and "the private tree is empty" is exactly the state in
# which this comparison must not be trusted to mean anything.
# =============================================================================
set -u

PUBLIC_DIR=""
LIST=""
TREE=""
REPO=""
SEARCH=200

for ARG in "$@"; do
  case "$ARG" in
    --public-dir=*) PUBLIC_DIR="${ARG#*=}" ;;
    --list=*)       LIST="${ARG#*=}" ;;
    --tree=*)       TREE="${ARG#*=}" ;;
    --repo=*)       REPO="${ARG#*=}" ;;
    --search=*)     SEARCH="${ARG#*=}" ;;
    -h|--help)      sed -n '2,60p' "$0"; exit 0 ;;
    *) echo "resolve-publish-baseline: unknown argument: $ARG" >&2; exit 2 ;;
  esac
done

SELF_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO="${REPO:-$(cd "$SELF_DIR/.." && pwd)}"

for REQUIRED in PUBLIC_DIR LIST TREE; do
  VAR_NAME="--${REQUIRED,,}"
  if [ -z "${!REQUIRED}" ]; then
    echo "resolve-publish-baseline: $VAR_NAME is required" >&2
    exit 2
  fi
done
if [ ! -d "$PUBLIC_DIR" ]; then
  echo "resolve-publish-baseline: --public-dir is not a directory: $PUBLIC_DIR" >&2
  exit 2
fi
for FILE in "$LIST" "$TREE"; do
  if [ ! -f "$FILE" ]; then
    echo "resolve-publish-baseline: no such file: $FILE" >&2
    exit 2
  fi
done
if ! git -C "$REPO" rev-parse --git-dir >/dev/null 2>&1; then
  echo "resolve-publish-baseline: --repo is not a git repository: $REPO" >&2
  exit 2
fi

CANDIDATE_TREE="$(mktemp)"
trap 'rm -f "$CANDIDATE_TREE"' EXIT

# One candidate, one verdict. `$1` is the sha, `$2` is where it came from.
verify_candidate() {
  git -C "$REPO" ls-tree -r "$1" > "$CANDIDATE_TREE" 2>/dev/null || return 1
  awk -F'\t' \
      -v PRIV="$CANDIDATE_TREE" \
      -v LIST="$LIST" \
      -v TREE="$TREE" \
      '
      FILENAME == PRIV  { n = split($1, f, " "); priv[$2] = f[3]; next }
      FILENAME == LIST  { published[$1] = 1; next }
      {
        p = $2
        if (!(p in published)) { next }
        n = split($1, f, " ")
        if (!(p in priv) || priv[p] != f[3]) { drift++ }
      }
      END { exit (drift > 0) }
      ' "$CANDIDATE_TREE" "$LIST" "$TREE"
}

# ── 1. THE DECLARED CANDIDATE ────────────────────────────────────────────────
# Read the public tip's own message and take the first 40-hex token out of it.
# It is only a candidate if the PRIVATE repository actually has that commit — a
# message naming a sha this repository has never heard of is not evidence, and
# `cat-file -e` is what distinguishes "stale" from "fabricated" without trusting
# the text at all.
SUBJECT="$(git -C "$PUBLIC_DIR" log -1 --format=%s HEAD 2>/dev/null || true)"
# The sha is written as "(<sha> on main)", so it arrives wrapped in punctuation.
# Pull every 40-hex run out of the message and take the first one this
# repository actually has — `cat-file -e` is what distinguishes "stale" from
# "fabricated", and it never trusts the surrounding text.
DECLARED=""
for SHA in $(printf '%s\n' "$SUBJECT" | grep -oE '[0-9a-f]{40}' | sort -u); do
  if git -C "$REPO" cat-file -e "${SHA}^{commit}" 2>/dev/null; then
    DECLARED="$SHA"
    break
  fi
done

if [ -n "$DECLARED" ]; then
  echo "declared   : $DECLARED"
  if verify_candidate "$DECLARED"; then
    echo "source     : declared"
    echo "RESOLVED $DECLARED"
    exit 0
  fi
  echo "source     : declared — REJECTED, the public branch does not match it"
else
  echo "declared   : none (no 40-hex commit in the public tip's message that this repository has)"
fi

# ── 2. THE SEARCH, KEPT AS THE FALLBACK ──────────────────────────────────────
# Unchanged in substance, and still the only thing that can catch a public branch
# whose message was rewritten. The window is a COST control and never a
# correctness bound; it was 50 and the repository crossed 55 while the gate was
# busy being correct about something else.
echo "searching  : $SEARCH candidates, newest first"
N=0
for CANDIDATE in $(git -C "$REPO" log --format=%H -"$SEARCH" HEAD 2>/dev/null); do
  N=$((N + 1))
  if verify_candidate "$CANDIDATE"; then
    echo "source     : search($N)"
    echo "RESOLVED $CANDIDATE"
    exit 0
  fi
done

# ── 3. NOTHING ───────────────────────────────────────────────────────────────
echo "searching  : exhausted $N candidates with no match"
echo "UNRESOLVED the public branch carries a published blob this repository has"
echo "           never produced. That is the out-of-band edit this gate exists to"
echo "           catch: compare against private HEAD and refuse."
exit 1
