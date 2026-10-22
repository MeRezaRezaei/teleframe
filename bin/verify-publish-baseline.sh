#!/usr/bin/env bash
# =============================================================================
# bin/verify-publish-baseline.sh — PROVE THE PUBLISH-BASELINE GATE STILL BITES.
#
# WHY A GATE THAT WAS NEVER SEEN FAILING IS NOT A GATE. The publish-baseline
# resolution went dark on 2026-10-18 in the worst possible way: not loudly, but
# by being WRONG. It compared a stale public branch against private HEAD, called
# 168 unmodified older blobs "content drift", refused to publish, and every CI
# gate in the project went dark with no red anywhere a human reads. The fix
# (bin/resolve-publish-baseline.sh) reads the private commit the public tip
# already declares, so the answer never expires.
#
# A fix that makes a gate pass has proved nothing on its own — it may pass
# because the gate is correct, or because the gate was quietly removed. So this
# script builds three real fixtures from the LIVE public branch and asserts all
# three outcomes, including the two that must FAIL:
#
#   GREEN   honest message, honest content   -> RESOLVED via `declared`, exit 0
#   FORGED  a message naming a sha this      -> RESOLVED via `search`,  exit 0
#           repository has never had
#   TAMPER  honest message, ONE published   -> UNRESOLVED,            exit 1
#           blob edited on the public branch
#
# FORGED resolving is not a weakness and is asserted on purpose: the declared
# sha is a HINT, and the content comparison is the gate. A message that was
# edited cannot make the gate pass — it can only make it do more work. TAMPER is
# the case that matters, and it is the one that must refuse.
#
# USAGE   bin/verify-publish-baseline.sh [--search=N] [--public-ref=REF]
#
# REF defaults to `public/main`. The fixture is that ref, so run this after
# `git fetch public main`.
#
# exit 0 = all three outcomes observed.  exit 1 = at least one did not.
# =============================================================================
set -u

SEARCH=""
PUBLIC_REF="public/main"
SELF_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO="$(cd "$SELF_DIR/.." && pwd)"

for ARG in "$@"; do
  case "$ARG" in
    --search=*)     SEARCH="${ARG#*=}" ;;
    --public-ref=*) PUBLIC_REF="${ARG#*=}" ;;
    -h|--help)      sed -n '2,42p' "$0"; exit 0 ;;
    *) echo "verify-publish-baseline: unknown argument: $ARG" >&2; exit 2 ;;
  esac
done

cd "$REPO" || exit 2

if ! git rev-parse --verify "$PUBLIC_REF" >/dev/null 2>&1; then
  echo "verify-publish-baseline: no such ref: $PUBLIC_REF"
  echo "                        run: git fetch public main"
  exit 2
fi
if ! git cat-file -e "$(git rev-parse HEAD):bin/check-public-surface.php" 2>/dev/null; then
  echo "verify-publish-baseline: HEAD does not carry bin/check-public-surface.php"
  exit 2
fi

LIST="$(mktemp)"
FIXTURES="$(mktemp -d)"
cleanup() {
  rm -f "$LIST"
  for NAME in green forged tamper; do
    git worktree remove --force "$FIXTURES/$NAME" >/dev/null 2>&1
  done
  rm -rf "$FIXTURES"
  git worktree prune >/dev/null 2>&1
}
trap cleanup EXIT

php bin/check-public-surface.php --list > "$LIST" || {
  echo "verify-publish-baseline: check-public-surface.php --list failed"
  exit 2
}
echo "public surface : $(wc -l < "$LIST" | tr -d ' ') paths"
echo "public ref     : $PUBLIC_REF ($(git rev-parse "$PUBLIC_REF"))"
echo "private HEAD   : $(git rev-parse HEAD)"
echo

# run <fixture-dir> [extra args...] -> writes "<exit> <output>" to $RESULT
RESULT=""
run() {
  local DIR="$1"; shift
  local TREE OUT
  TREE="$(mktemp)"
  git -C "$DIR" ls-tree -r HEAD > "$TREE" || { rm -f "$TREE"; return 2; }
  OUT="$(bash "$SELF_DIR/resolve-publish-baseline.sh" \
          --public-dir="$DIR" --list="$LIST" --tree="$TREE" --repo="$REPO" \
          ${SEARCH:+--search=$SEARCH} "$@" 2>&1)"
  local CODE=$?
  rm -f "$TREE"
  RESULT="$(printf '%s\n' "$OUT")"
  return $CODE
}

declare -i FAILURES=0
report() {
  local LABEL="$1" WANT="$2" CODE="$3"
  printf '%s\n' "$RESULT" | sed 's/^/      /'
  if [ "$CODE" -eq "$WANT" ]; then
    printf '  PASS  %-7s exit %s (wanted %s)\n\n' "$LABEL" "$CODE" "$WANT"
  else
    printf '  FAIL  %-7s exit %s (wanted %s)\n\n' "$LABEL" "$CODE" "$WANT"
    FAILURES+=1
  fi
}

# ── GREEN ────────────────────────────────────────────────────────────────────
echo "CASE GREEN  — the live public branch, as published"
git worktree add --detach -q "$FIXTURES/green" "$PUBLIC_REF" >/dev/null 2>&1 || {
  echo "verify-publish-baseline: could not build the green fixture"
  exit 2
}
run "$FIXTURES/green"
CODE=$?
report "GREEN" 0 "$CODE"
printf '%s\n' "$RESULT" | grep -q '^source     : declared$' \
  || { echo "  FAIL  GREEN    resolved by SEARCH, not by the declared sha — the fix is not in effect"; FAILURES+=1; echo; }

# ── FORGED ───────────────────────────────────────────────────────────────────
echo "CASE FORGED — message rewritten to a sha this repository has never had"
git worktree add --detach -q "$FIXTURES/forged" "$PUBLIC_REF" >/dev/null 2>&1 || {
  echo "verify-publish-baseline: could not build the forged fixture"
  exit 2
}
git -C "$FIXTURES/forged" -c user.email=gate@localhost -c user.name=gate \
  commit -q --amend -m "chore(public): publish the public surface of teleframe. Produced by bin/export-public-repo.sh from the private repository (0000000000000000000000000000000000000000 on main)" \
  >/dev/null 2>&1
run "$FIXTURES/forged"
CODE=$?
report "FORGED" 0 "$CODE"
printf '%s\n' "$RESULT" | grep -q '^declared   : none' \
  || { echo "  NOTE  FORGED   the bogus sha was accepted as a candidate — cat-file -e is not biting"; echo; }

# ── TAMPER ───────────────────────────────────────────────────────────────────
echo "CASE TAMPER — ONE published blob edited on the public branch, DECLARED sha untouched"
git worktree add --detach -q "$FIXTURES/tamper" "$PUBLIC_REF" >/dev/null 2>&1 || {
  echo "verify-publish-baseline: could not build the tamper fixture"
  exit 2
}
# The commit message keeps the ORIGINAL publish wording and its original private
# sha on purpose. This is the strongest form of the case: the gate is handed the
# exact baseline it would have accepted a moment ago, and it must REJECT it
# because the content no longer matches. A case that only failed for want of a
# declared sha would prove nothing about the comparison.
printf '\n' >> "$FIXTURES/tamper/composer.json"
TAMPER_SUBJECT="$(git -C "$FIXTURES/tamper" log -1 --format=%s)"
git -C "$FIXTURES/tamper" -c user.email=gate@localhost -c user.name=gate \
  commit -qam "$TAMPER_SUBJECT" >/dev/null 2>&1
run "$FIXTURES/tamper"
CODE=$?
report "TAMPER" 1 "$CODE"
printf '%s\n' "$RESULT" | grep -q 'REJECTED, the public branch does not match it' \
  || { echo "  FAIL  TAMPER  it did not reach the content comparison — the declared sha was not tested"; FAILURES+=1; echo; }

echo "=============================================================="
if [ "$FAILURES" -eq 0 ]; then
  echo "PASS — the gate resolves the live branch AND still refuses a moved copy."
  exit 0
fi
echo "FAIL — $FAILURES assertion(s) did not hold."
exit 1
