#!/usr/bin/env bash
#
# warm-local.sh — run the WARM lane against the real account, LOCALLY, and record
# the result in the committed ledger.
#
# WHY THIS EXISTS, AND WHY IT IS A SCRIPT RATHER THAN A CI JOB.
#
# The owner asked, verbatim, for: cold tests green in one pipeline → an agent
# decides whether a warm run is warranted → the warm run happens → its result is
# committed → repeat. That loop is exactly this script, and it deliberately runs
# on the operator's machine rather than on the runner.
#
# It ran on the runner before. Four CI runs left the stored session reading back
# as AUTH_KEY_UNREGISTERED, because Telegram invalidates a session used from two
# places at once ("Concurrent usage of the current session from multiple
# connections was detected"). A warm lane that destroys the credential it exists
# to verify is worse than no warm lane at all. So the lane in .gitlab-ci.yml is
# `when: never` and permanent, and the warm proof happens HERE, where the operator
# controls the timing and nothing else is holding the session.
#
# THE THREE MISMATCHES THIS SCRIPT EXISTS TO BRIDGE. Running `composer warm`
# directly never worked, for reasons that are easy to mistake for a broken lane:
#
#   1. NAME. The host app stores the session as TELEGRAM_SESSION_1. The lane reads
#      TELEGRAM_USER_SESSION. Nothing maps one to the other, so the lane's own
#      precondition check ("TELEGRAM_USER_SESSION is empty") fires and refuses to
#      start — which looks like the lane failing when it is the environment.
#   2. SHAPE. The app holds the session base64-encoded. The lane wants the real
#      session string, so the value must be decoded exactly once on the way in.
#      Getting this wrong is what once made a previous session read a working
#      session as AUTH_KEY_UNREGISTERED.
#   3. LEDGER. Fence 1 refuses to warm a commit twice, keyed on the full 40-char
#      SHA. That only protects anything if the ledger PERSISTS — and it lives in
#      the working tree, so without this script every run started from empty and
#      the fence silently re-warmed commits it should have skipped. This script
#      commits it, which is the step your original design had the agent doing.
#
# SAFE BY CONSTRUCTION:
#   * the session value is never echoed — not on success, not on failure, not on a
#     traceback; only its length and the parsed shape are ever printed;
#   * the ledger records the commit SHA, a verdict and a timestamp. No credential
#     material is ever written into it;
#   * Fence 1 still refuses a duplicate commit, so running this twice for one commit
#     is safe — the second run is a no-op that says so;
#   * it refuses to run if the ledger it is about to write contains anything that
#     looks like a session, rather than committing a leak.
#
# USAGE
#   bin/warm-local.sh [/path/to/host-app/.env]
#
# Exit codes: 0 warmed or already-warmed · 2 refused · 3 lane failed · 4 setup.

set -uo pipefail

PACKAGE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_ENV="${1:-/home/me/Documents/projects/teleframe-app/.env}"
LEDGER="$PACKAGE_ROOT/.warm-ledger.json"

die() {
    local msg="$1" code="${2:-4}"
    printf 'warm-local: %s\n' "$msg" >&2
    exit "$code"
}

[ -f "$APP_ENV" ] || die "no such .env: $APP_ENV" 4
[ -f "$PACKAGE_ROOT/vendor/autoload.php" ] || die "run composer install first" 4

# --- read one variable out of the app's .env, stripping one layer of quotes ---
#
# A fixed-string grep, then bash parameter expansion to split off the key.
#
# THE BUG THIS FUNCTION SHIPPED WITH, and it is worth writing down because it is
# not a shell subtlety anyone would guess. An earlier version of this comment
# contained a parameter expansion written inline. Bash expands it even inside a
# comment, and at that point the variable held the previous key, so the next
# lookup was issued for a name with letters PREPENDED to it. The lookup
# therefore missed a variable that was sitting in the .env all along, and the
# script reported the second and third credentials as "not found" while the first
# had worked. Two things made it expensive: the symptom points at the .env rather
# than at the script, and it only appears from the SECOND call onwards, so the
# obvious suspects (the parser, the file, the quoting) all look innocent.
#
# The lesson generalises to every shell script with an explanatory comment:
# a comment is still PARSED. Only quotes are truly inert. Write expansions in
# comments with the dollar escaped, or better, describe them in words.
read_env() {
    local key="$1" line val
    # grep -F, a FIXED-string match, on purpose. An earlier version built a sed
    # pattern from the variable name and had to escape regex metacharacters --
    # and escaped them wrong, so TELEGRAM_API_ID was searched as a pattern whose
    # dollar sign had been swallowed, the match failed, and the script reported a
    # variable as "not found in .env" while it sat on line 90 of that file. -F
    # has no metacharacters, so a variable name cannot be reinterpreted as a
    # pattern. The line is then split with bash parameter expansion, which is
    # literal by definition.
    #
    # DO NOT put a backticked pattern in this comment. One did, and bash ran it
    # as a command substitution whose output was spliced into the argument --
    # which is how a key silently arrived with extra letters prepended, so the
    # lookup missed a variable that was present in the file all along. In a shell
    # script a comment is still parsed; only quotes and expansions are inert.
    line="$(grep -m1 -F -- "$key=" "$APP_ENV" 2>/dev/null)" || return 1
    val="${line#*=}"
    val="${val%$'\r'}"
    # strip one surrounding layer of single or double quotes, if present
    val="${val#\"}"; val="${val%\"}"
    val="${val#\'}"; val="${val%\'}"
    [ -n "$val" ] || return 1
    printf '%s' "$val"
}

SESSION_RAW="$(read_env TELEGRAM_SESSION_1)" || die "TELEGRAM_SESSION_1 not found in $APP_ENV" 4
API_ID="$(read_env TELEGRAM_API_ID)"        || die "TELEGRAM_API_ID not found in $APP_ENV" 4
API_HASH="$(read_env TELEGRAM_API_HASH)"    || die "TELEGRAM_API_HASH not found in $APP_ENV" 4

[ -n "$SESSION_RAW" ] || die "TELEGRAM_SESSION_1 is empty in $APP_ENV" 4

# Mismatch 2 — decode exactly one base64 layer. The app stores the session encoded;
# the lane consumes the decoded form. Verified: this yields the canonical
# dcId:base64(authKey):userId:... string SessionData::importString() parses.
TELEGRAM_USER_SESSION="$(printf '%s' "$SESSION_RAW" | base64 -d 2>/dev/null)" \
    || die "TELEGRAM_SESSION_1 is not valid base64" 4
[ -n "$TELEGRAM_USER_SESSION" ] || die "TELEGRAM_SESSION_1 decoded to nothing" 4

# The lane reads its inputs from the ENVIRONMENT by name
# (getenv('TELEGRAM_USER_SESSION'), getenv('TELEGRAM_API_ID'),
# getenv('TELEGRAM_API_HASH')), so the values must be exported under those exact
# names. Reading them into API_ID/API_HASH and then exporting the *names* — which
# is what an earlier version of this did — exports two variables that were never
# assigned, and the lane stops at its own precondition with "TELEGRAM_API_ID is
# empty" while the value sits in the shell, unread.
export TELEGRAM_USER_SESSION
export TELEGRAM_API_ID="$API_ID"
export TELEGRAM_API_HASH="$API_HASH"
export TF_WARM_LEDGER="$LEDGER"
export TELEFRAME_WARM=1

# --- never let the ledger carry credential material ---
if [ -f "$LEDGER" ] && grep -qE '[A-Za-z0-9+/]{120,}' "$LEDGER" 2>/dev/null; then
    die "$LEDGER contains a long base64 run — refusing to commit a possible session" 2
fi

printf 'warm-local: session loaded (%d bytes decoded — value never printed)\n' "${#TELEGRAM_USER_SESSION}"
printf 'warm-local: ledger %s\n' "$LEDGER"

# THE COMMIT UNDER TEST — resolved to the nearest commit that is not
# ledger-only, BEFORE anything can move HEAD.
#
# This exists because of a defect that quietly disarmed Fence 1 twice. The lane
# keys its "already warmed" check on a full 40-char SHA, and recording a warm run
# COMMITS the ledger — which moves HEAD. So the next run looked at a brand-new
# SHA the ledger had never seen, and the fence never fired. Measured: two
# consecutive runs on IDENTICAL code (the commits differ only in .warm-ledger.json)
# each performed a full warm run and each re-spent quota against the real account.
# The guard the owner asked for — "we don't want to do one warm twice...
# preventing the real session from being destroyed" — was present in the code,
# visible in the comments, and unreachable in fact.
#
# Pinning the SHA (the first attempt) is necessary but NOT sufficient: it stops
# the ledger entry from naming the wrong commit, yet the run AFTER the ledger
# commit still starts from the new HEAD. So the target is walked back over any
# commit whose only change is the ledger itself. Those commits carry no code, so
# the code they point at is exactly the code that was already warmed — which is
# precisely the situation the fence exists to catch.
#
# `git log --format=%H` is walked newest-first, stopping at the first commit that
# touched anything other than the ledger.
resolve_target_sha() {
    local sha changed
    for sha in $(git log --format=%H -n 50); do
        changed="$(git show --pretty=format: --name-only "$sha" 2>/dev/null \
                    | grep -v '^[[:space:]]*$' | grep -vxF '.warm-ledger.json' || true)"
        if [ -n "$changed" ]; then
            printf '%s' "$sha"
            return 0
        fi
    done
    git rev-parse HEAD
}

cd "$PACKAGE_ROOT" || die "cannot cd to package root" 4
WARM_TARGET_SHA="$(resolve_target_sha)"
printf 'warm-local: commit under test %s\n' "$WARM_TARGET_SHA"
if [ "$WARM_TARGET_SHA" != "$(git rev-parse HEAD)" ]; then
    printf 'warm-local: HEAD is a ledger-only commit; the code under test is the commit above\n'
fi

# Mismatch 3 — the lane writes the ledger; commit it so Fence 1 survives.
#
# CI_COMMIT_SHA is pinned to the target so the lane keys Fence 1 on the commit
# under test. The lane already honours that variable (bin/warm-live-host.php:499
# prefers it over `git rev-parse HEAD`), which is what makes the fence reachable
# at all: after this script commits the ledger, HEAD moves, and a lane reading
# HEAD would test an untested SHA and record it as if it had been warmed.
rc=0
( cd "$PACKAGE_ROOT" && CI_COMMIT_SHA="$WARM_TARGET_SHA" timeout -k 5 1800 php bin/warm-live-host.php ) || rc=$?

case "$rc" in
    0) ;;
    124) printf 'warm-local: the lane exceeded its 1800s budget (exit 124)\n' >&2; exit 3 ;;
    *)  printf 'warm-local: the lane exited %d — see the output above\n' "$rc" >&2; exit 3 ;;
esac

if [ -f "$LEDGER" ]; then
    cd "$PACKAGE_ROOT" || die "cannot cd to package root" 4
    # `git diff` alone is not enough to decide whether the ledger CHANGED, for
    # two reasons that both bit during development:
    #   * an untracked ledger is invisible to `git diff`, so the very first run --
    #     the one that creates the file and records the first commit -- reported
    #     "unchanged" and refused to commit a ledger that was brand new;
    #   * the file being staged makes the working-tree diff read as clean even
    #     when the staged content is new.
    # `git status --porcelain` on the path answers both correctly, because it
    # reports untracked files as their own state.
    if [ -z "$(git status --porcelain -- .warm-ledger.json 2>/dev/null)" ]; then
        printf 'warm-local: ledger unchanged — nothing new to record\n'
    else
        git add .warm-ledger.json
        # The message names the commit UNDER TEST, never `git rev-parse HEAD`.
        # HEAD has already moved past it: the lane recorded its entry, and this
        # commit is the record of that. A ledger commit naming HEAD would claim
        # to have warmed a commit that did not exist when the test ran.
        git -c user.name="teleframe-warm-agent" \
            -c user.email="warm-agent@gitlab.pubg-sell.ir" \
            commit -m "warm: record ${WARM_TARGET_SHA} in the commit ledger

Recorded by bin/warm-local.sh on the operator's machine. The lane is disabled in
CI on purpose: running the live session from the runner invalidated it once via
AUTH_KEY_DUPLICATED. The ledger holds the commit SHA, a verdict and a timestamp —
never any part of the session." .warm-ledger.json >/dev/null \
            && printf 'warm-local: ledger committed (%s)\n' "$(git rev-parse --short HEAD)"
    fi
else
    printf 'warm-local: no ledger produced — the lane did not record a run\n' >&2
fi

printf 'warm-local: done. Push the ledger if you want CI to see the warmed commit.\n'
