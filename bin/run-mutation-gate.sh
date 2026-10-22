#!/bin/sh
#
# THE MUTATION GATE, IN THE ORDER THAT MAKES IT TRUSTWORTHY.
#
set -eu

ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$ROOT"

# Mutation runs must never share a database. Infection runs four mutant
# processes in parallel, and RunsMysqlMigrations drops/recreates ONE fixed
# scratch database, so concurrent mutants race ("database exists"). The MySQL
# track owns engine-specific assertions; the mutation gate measures logic on the
# default engine. Named here because every result the gate writes depends on it.
export TELEFRAME_MYSQL_SKIP=1

SCOPE="entity"
MIN_MSI="0"

# Argument parsing: first arg may be a scope name or an MSI threshold (legacy).
case "${1:-}" in
    ''|*[!0-9]*)  # non-numeric or empty -> scope name (or default)
        if [ -n "${1:-}" ]; then
            SCOPE="$1"
            MIN_MSI="${2:-0}"
        fi
        ;;
    *)
        MIN_MSI="$1"
        SCOPE="${2:-entity}"
        ;;
esac

# `all` runs every scope in sequence.
if [ "$SCOPE" = "all" ]; then
    for s in entity bus schema ingest; do
        "$0" "$s" "$MIN_MSI"
    done
    exit 0
fi

# A scope name becomes a directory name below, so it must not be able to escape.
case "$SCOPE" in
    ''|*[!A-Za-z0-9_-]*)
        echo "refusing to run a gate for an unsafe scope name: $SCOPE" >&2
        exit 2
        ;;
esac

# EVERY GATE GETS ITS OWN TEMP DIRECTORY, WIPED BEFORE THE RUN.
#
# Infection keeps its coverage index and per-file coverage XMLs under
# sys_get_temp_dir()/infection. A run that is killed, or two runs that share a
# temp dir, leave an index.xml that points at coverage files nobody wrote — and
# the NEXT run aborts with "Could not find the XML coverage file ... listed in
# index.xml", before a single mutant is measured (MEASURED 2026-10-22 10:42 UTC
# on the schema-ingest slice, whose temp dir still held the wreckage of the
# 10:09 parallel launch). Cleaning up afterwards is not enough: the damage is
# invisible until a later run trips over it. Isolation is the fix, and it is
# also what makes two gates safe to run at the same time.
export TMPDIR="${TMPDIR:-/tmp}/teleframe-infection-${SCOPE}"
rm -rf "$TMPDIR"
mkdir -p "$TMPDIR"

CONFIG="infection/${SCOPE}.json5"
# Per-area configs live in infection/ and write their logs RELATIVE TO THE CONFIG
# (measured: `logs.summary: "bus-infection-summary.json"` lands in infection/).
# Reading the summary from the repo root therefore found no artifact and refused a
# run that had actually completed — the assert never saw the result.
SUMMARY="infection/${SCOPE}-infection-summary.json"
# entity scope uses root infection.json5 + infection-summary.json
if [ "$SCOPE" = "entity" ]; then
    CONFIG="infection.json5"
    SUMMARY="infection-summary.json"
fi

# A summary left over from an EARLIER run must not be assertable as this run's
# result. The assert reads the summary file, so a run that dies before writing
# one would otherwise assert the PREVIOUS run's numbers — the gate would report
# a result it never measured. Deleting it first makes "no artifact" mean "no
# artifact" again.
rm -f "$SUMMARY"

echo "== mutation gate ($SCOPE): preflight =="
php bin/infection-preflight.php "$SCOPE"

echo "== mutation gate ($SCOPE): mysql tests skipped (TELEFRAME_MYSQL_SKIP=1) =="
echo "== mutation gate ($SCOPE): isolated tmp $TMPDIR =="
echo "== mutation gate ($SCOPE): measuring (min Covered MSI ${MIN_MSI}) =="
# The measurement and the ASSERTION are two different things, and one of them must
# not hide the other. Infection exits non-zero when Covered MSI misses the
# threshold, and `set -e` used to abort the script right there — so the assert
# (accounting closes, nothing errored, skipped share under the limit, kill floor
# against the committed baseline) never ran on exactly the runs that need it most.
# The failure mode it exists to catch — a scope that stops covering its source —
# is the same one that produces a low MSI. Measured 2026-10-22 on
# schema-ingest: MSI 57.47% failed the run and the accounting assertion that
# would have named the cause never executed.
set +e
vendor/bin/infection \
    -c "$CONFIG" \
    -j4 \
    --with-timeouts \
    --min-msi=0 \
    --min-covered-msi="$MIN_MSI" \
    --no-progress
MEASURE_RC=$?
set -e

echo "== mutation gate ($SCOPE): asserting the result =="
if [ -f "$SUMMARY" ]; then
    set +e
    php bin/infection-result-assert.php "$SUMMARY" "$SCOPE"
    ASSERT_RC=$?
    set -e
else
    echo "  no summary at $SUMMARY — the run produced no artifact, which is not a result"
    ASSERT_RC=1
fi

if [ "$MEASURE_RC" -ne 0 ] || [ "$ASSERT_RC" -ne 0 ]; then
    echo "== mutation gate ($SCOPE): FAILED (measure rc=$MEASURE_RC, assert rc=$ASSERT_RC) =="
    exit 1
fi

echo "== mutation gate ($SCOPE): PASSED =="
