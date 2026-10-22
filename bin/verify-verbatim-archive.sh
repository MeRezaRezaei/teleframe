#!/usr/bin/env bash
# =============================================================================
# bin/verify-verbatim-archive.sh — MEASURE how much machine text the verbatim
# archive files as the operator's speech.
#
# The archive `.brain/verbatim/*.md` is the fixed point every project goal is
# extracted from. "Is the archive pure?" used to be a paragraph in a report —
# which is not a measurement, because the next reader has no way to re-run it.
# This is the measurement, and it is a NUMBER.
#
# ---------------------------------------------------------------------------
# WHY THE RULE IS EXECUTED, NOT RETYPED
# ---------------------------------------------------------------------------
# The classification rule lives in exactly one place: the module-level function
# `exclusion_reason(txt, is_dispatch_session=False, day=None)` plus the UPPERCASE
# rule tables in `bin/build-verbatim-teleframe.py`. Copying that function into a
# checker would create a SECOND rule. Two rules drift, and the drift is silent:
# the checker keeps reporting 0 while the extractor keeps letting briefs
# through, or the reverse — and neither number can be trusted again. So this
# gate never restates the rule. It parses the shipped file with Python's `ast`,
# keeps ONLY the module-level `Assign` nodes whose targets are all uppercase
# plus the one `FunctionDef`, and `exec`s them in a namespace seeded with `os`,
# `re`, `sys` and the `datetime` CLASS (the script does `from datetime import
# datetime, timezone`, so `datetime` there is the class, not the module), then
# calls the real function. A future edit to the shipped rule changes this
# gate's answer on the next run, with no edit here.
#
# Two traps that cost real time on the first attempt, recorded so the next
# agent does not pay them again:
#   * `datetime` must be seeded as the CLASS. Seeding the module makes
#     `datetime.fromtimestamp(...)` fail with an AttributeError deep inside the
#     exec'd constants, not at the call site.
#   * the `Assign` filter must reject any node with a non-uppercase or
#     non-`Name` target, or `con = sqlite3.connect(...)` — a live connection to
#     a chat-store DB on another host — is exec'd and crashes the gate.
#
# ---------------------------------------------------------------------------
# THE THREE NUMBERS THIS GATE EXISTS TO KEEP APART
# ---------------------------------------------------------------------------
# Measured 2026-10-18 against HEAD 7e300dd45b9e43ab3330cb04652b375998b22e2d,
# with `.brain/verbatim` byte-identical to that commit (43 day files, 2550
# stored entries). The same four numbers were re-measured with the rule read
# from the HEAD blob instead of the working tree, and are identical, so they
# are a property of the archive and not of an uncommitted edit. Every run
# re-prints the live `git rev-parse HEAD`, whether the archive is clean against
# it, AND whether the RULE SOURCE is clean against it — so a number this gate
# prints is always attributable to a commit rather than to a dirty tree:
#
#     2550   entries scanned across every day file.
#        0   of them the SHIPPED rule would drop on the next extraction.
#            That 0 is the correct measured answer, not a broken gate: the
#            archive was extracted WITH this rule, so it is already clean, and
#            a non-zero here means the archive drifted away from the rule.
#
# The recorded probe reported the naive-substring figure as 399. On this tree
# that number does not reproduce under either reading, and the gate prints
# what it actually measures rather than a remembered figure:
#       383   entries hit by a bare substring filter over the SEVEN ordinary
#             English signatures the probe named (`read-only`, `do not commit`,
#             `do not edit`, `exclusive`, `repo at `, `timeout -k 5`,
#             `write one file`).
#      1129   entries hit by a bare substring filter over ALL 43
#             LANE_SIGNATURES members.
#
# 383 and 1129 are FALSE-POSITIVE COUNTS, not drops. LANE_SIGNATURES holds
# ordinary English — `read-only`, `exclusive`, `repo at ` — so a naive filter
# lands on the operator's own prose:
#     2026-09-13 `10:09:54 UTC`  matched=['repo at ']
#             why my repo at branch chore package is still like a laravle
#             instaltion ??
#     2026-09-23 `06:32:01 UTC`  matched=['read-only']
#             ok so commit push what we have for now
# A "purity repair" driven by 399 (or 383, or 1129) would delete the operator's
# sentences. The real rule drops 0 of those same entries, because the lane-
# signature layer is scoped forward from LANE_RULES_FROM and because the
# pasted-document short-circuit runs first. THIS GATE REPORTS ONLY. It writes
# nothing, deletes nothing, sweeps nothing.
#
# ---------------------------------------------------------------------------
# WHAT MAKES THIS A GATE AND NOT A REPORT
# ---------------------------------------------------------------------------
# Section [1] is a self-check: the SAME measurement runs against a synthetic
# in-memory sample whose texts are known to be excluded by the shipped rule,
# plus known-keep controls. The gate asserts the rule really drops the known
# drops AND really keeps the known keeps — a gate that "passes" by dropping
# everything is broken in the other direction and must fail too. If the rule
# extraction ever stops working (renamed constant, widened filter, an
# `Assign` filter that let something un-exec-able through), the assertions
# fail and the gate exits non-zero saying so, instead of reporting a confident
# 0 derived from a function that is no longer the shipped one.
#
# USAGE   bin/verify-verbatim-archive.sh [--self-check-only]
#         runs from the repository root, takes no required argument.
#
# exit 0 = self-check passed and the measurement completed (drop count is
#          REPORTED, not enforced — this gate never rewrites the archive).
# exit 1 = rule extraction broken, or a self-check assertion failed.
# exit 2 = usage error, or the rule source / archive could not be read.
# =============================================================================
set -u

MODE="archive"
SELF_DIR="$(cd "$(dirname "$0")" 2>/dev/null && pwd)"
REPO="$(cd "$SELF_DIR/.." 2>/dev/null && pwd)"

for ARG in "$@"; do
  case "$ARG" in
    --self-check-only) MODE="self-check" ;;
    -h|--help) sed -n '2,99p' "$0"; exit 0 ;;
    *) echo "verify-verbatim-archive: unknown argument: $ARG" >&2; exit 2 ;;
  esac
done

if [ -z "$REPO" ] || [ ! -d "$REPO/.brain/verbatim" ]; then
  echo "verify-verbatim-archive: run this from the repository root" >&2
  echo "  (no $REPO/.brain/verbatim)" >&2
  exit 2
fi
cd "$REPO" || exit 2

exec python3 - "$MODE" "$REPO" <<'PY'
"""Measure the verbatim archive with the SHIPPED exclusion rule, executed."""

import ast
import os
import re
import subprocess
import sys
from collections import Counter
from datetime import datetime, timezone

MODE = sys.argv[1]
REPO = sys.argv[2]
SCRIPT = os.path.join(REPO, "bin/build-verbatim-teleframe.py")
VDIR = os.path.join(REPO, ".brain/verbatim")

# The seven ordinary-English signatures named in the recorded probe. They are
# also asserted to be present in the live LANE_SIGNATURES: if the shipped set
# stops containing one, the recorded false-positive number no longer describes
# the rule and the gate says so instead of quietly reporting a stale figure.
TRAP_SIGNATURES = (
    "read-only",
    "do not commit",
    "do not edit",
    "exclusive",
    "repo at ",
    "timeout -k 5",
    "write one file",
)

DAY_RE = re.compile(r"^(\d{4}-\d{2}-\d{2})-")
LINE = "=" * 78
THIN = "-" * 78


def die(msg, code):
    sys.stderr.write("verify-verbatim-archive: %s\n" % msg)
    raise SystemExit(code)


# ---------------------------------------------------------------------------
# 1. Extract and execute the shipped rule. Never retype it.
# ---------------------------------------------------------------------------
if not os.path.isfile(SCRIPT):
    die("rule source missing: %s" % SCRIPT, 2)

try:
    with open(SCRIPT, encoding="utf-8") as fh:
        source = fh.read()
    tree = ast.parse(source, filename=SCRIPT)
except (OSError, SyntaxError) as exc:
    die("cannot parse the rule source: %s" % exc, 2)

kept_consts = []
kept_funcs = []
for node in tree.body:
    # UPPERCASE Assign nodes only. Anything else at module level is program
    # flow (a sqlite3.connect(), a main loop) and must never be exec'd.
    if isinstance(node, ast.Assign):
        if node.targets and all(
            isinstance(t, ast.Name) and t.id.isupper() for t in node.targets
        ):
            kept_consts.append(node)
        continue
    if isinstance(node, ast.FunctionDef) and node.name == "exclusion_reason":
        kept_funcs.append(node)

if not kept_funcs:
    die(
        "exclusion_reason() not found at module level in %s — the rule this "
        "gate measures no longer exists" % SCRIPT,
        2,
    )

# The shipped script does `from datetime import datetime, timezone`, so the
# names `datetime` / `timezone` in its namespace are the CLASS and the tzinfo,
# not the module. Seeding the module here is the classic first-attempt crash.
ns = {"os": os, "re": re, "sys": sys, "datetime": datetime, "timezone": timezone}
try:
    exec(
        compile(
            ast.Module(body=kept_consts + kept_funcs, type_ignores=[]),
            SCRIPT,
            "exec",
        ),
        ns,
    )
except Exception as exc:  # noqa: BLE001 — any exec failure is a broken extraction
    die("rule extraction failed to exec: %s: %s" % (type(exc).__name__, exc), 1)

rule = ns["exclusion_reason"]
LANE_RULES_FROM = ns["LANE_RULES_FROM"]
LANE_SIGNATURES = list(ns["LANE_SIGNATURES"])
# The shipped entry-shape regexes, read rather than restated.
STAMP = ns["STAMP"]
QUOTE = ns["QUOTE"]
HARNESS_NUDGE = ns["HARNESS_NUDGE"]

missing_trap = [s for s in TRAP_SIGNATURES if s not in LANE_SIGNATURES]


def git(*args):
    try:
        return subprocess.run(
            ["git", "-C", REPO] + list(args),
            capture_output=True,
            text=True,
            timeout=30,
        ).stdout.strip()
    except Exception:  # noqa: BLE001 — git is decoration here, never a blocker
        return ""


HEAD_SHA = git("rev-parse", "HEAD") or "(unknown)"
ARCHIVE_DIRTY = git("status", "--porcelain", ".brain/verbatim")
# The archive being clean is not enough: a number is only attributable to a
# commit if the RULE it was measured with is committed too. An uncommitted edit
# to build-verbatim-teleframe.py means every count below was produced by a rule
# that exists in no commit yet — the exact failure AGENTS.md records, where a
# checked goal described a tree that was never committed.
RULE_DIRTY = git("status", "--porcelain", "bin/build-verbatim-teleframe.py")

print(LINE)
print("verbatim archive gate — bin/verify-verbatim-archive.sh")
print(LINE)
print("rule source    : bin/build-verbatim-teleframe.py  (EXECUTED, not retyped)")
print("rule extracted : %d uppercase constants + %d function(s)" % (len(kept_consts), len(kept_funcs)))
print("repo root      : %s" % REPO)
print("git HEAD       : %s" % HEAD_SHA)
print("archive vs HEAD: %s" % ("DIRTY — numbers below describe the working tree"
                                if ARCHIVE_DIRTY else "clean — numbers below describe that commit"))
print("rule vs HEAD   : %s" % ("DIRTY — these numbers were produced by an UNCOMMITTED rule"
                                if RULE_DIRTY else "clean — these numbers were produced by that commit's rule"))
print("LANE_RULES_FROM: %s   (read live from the shipped constant)" % LANE_RULES_FROM)
print("LANE_SIGNATURES: %d members" % len(LANE_SIGNATURES))
if missing_trap:
    print("DRIFT WARNING  : these recorded false-positive signatures are NOT in the")
    print("                 shipped LANE_SIGNATURES any more: %s" % ", ".join(repr(s) for s in missing_trap))
print(LINE)

# ---------------------------------------------------------------------------
# 2. The measurement itself — one function, used by the archive pass AND by
#    the self-check, so the self-check proves the code under test and not a
#    lookalike of it.
# ---------------------------------------------------------------------------
# The archive is a transcript of what a LIVE conversation produced, so the
# structural tie-breaker is evaluated on its live side: is_dispatch_session is
# False for every stored entry. That side can only ever be the more
# permissive one (it keeps skill-doc headers and short-circuits pasted docs to
# "keep"), so a drop found under it is a drop under the stricter side too.
IS_DISPATCH_SESSION = False


def parse_entries(text):
    """Return [(stamp, body)] for one day file. Quote prefix stripped back off.

    Shape is the shipped writer's shape: a line that is exactly
    `` `HH:MM:SS UTC` ``, then the blockquoted body lines until the first
    line that is not a quote.
    """
    out = []
    lines = text.split("\n")
    i = 0
    while i < len(lines):
        if STAMP.match(lines[i]):
            stamp = lines[i].strip("`")
            j = i + 1
            body = []
            while j < len(lines) and QUOTE.match(lines[j]):
                ln = lines[j]
                body.append(ln[2:] if ln.startswith("> ") else ln[1:])
                j += 1
            out.append((stamp, "\n".join(body)))
            i = j
        else:
            i += 1
    return out


def measure(entries):
    """entries: [(day, stamp, text)] -> the counts this gate is about."""
    res = {
        "scanned": 0,
        "dropped": 0,
        "dropped_in_scope": 0,
        "dropped_pre_scope": 0,
        "in_scope": 0,
        "reasons": Counter(),
        "naive_all": 0,
        "naive_trap": 0,
        "naive_all_per_sig": Counter(),
        "drops": [],
        "naive_examples": [],
    }
    for day, stamp, text in entries:
        res["scanned"] += 1
        in_scope = day >= LANE_RULES_FROM
        if in_scope:
            res["in_scope"] += 1
        reason = rule(text, is_dispatch_session=IS_DISPATCH_SESSION, day=day)
        if reason:
            res["dropped"] += 1
            res["reasons"][reason] += 1
            if in_scope:
                res["dropped_in_scope"] += 1
            else:
                res["dropped_pre_scope"] += 1
            res["drops"].append((day, stamp, reason, text))
        low = text.lower()
        hits = [s for s in LANE_SIGNATURES if s in low]
        if hits:
            res["naive_all"] += 1
            for s in hits:
                res["naive_all_per_sig"][s] += 1
            if len(res["naive_examples"]) < 8:
                res["naive_examples"].append((day, stamp, hits, text))
            if any(s in low for s in TRAP_SIGNATURES):
                res["naive_trap"] += 1
    return res


# ---------------------------------------------------------------------------
# 3. Section [1] — SELF-CHECK. The gate must be seen to fire.
# ---------------------------------------------------------------------------
SAMPLES = [
    # (label, day, text, expect_drop)
    ("lane brief, agent prompt opener", "2026-10-12",
     "You are lane 3 of 5. Repo: teleframe. Touch ONLY the file named below.",
     True),
    ("dispatch fingerprint", "2026-10-12",
     "Call delegate_to_agent with the lane spec, then continue the goal.",
     True),
    ("lane signature in scope", "2026-10-12",
     "The lane brief says: write one file and stop. Keep it minimal.",
     True),
    ("control plane, pre-scope day", "2026-09-20",
     "<goal_continuation>carry on from where you stopped",
     True),
    ("harness nudge, in scope", "2026-10-13", HARNESS_NUDGE, True),
    ("SAME text as sample 3, PRE-scope day", "2026-10-01",
     "The lane brief says: write one file and stop. Keep it minimal.",
     False),
    ("operator prose, pre-scope day", "2026-09-13",
     "why my repo at branch chore package is still like a laravle instaltion ??",
     False),
    ("operator prose, plain", "2026-10-12",
     "ok now jsut contiue the work", False),
]

print("[1] RULE EXTRACTION SELF-CHECK (synthetic in-memory sample)")
print(THIN)
failures = []
for label, day, text, expect_drop in SAMPLES:
    got = rule(text, is_dispatch_session=IS_DISPATCH_SESSION, day=day)
    got_drop = got is not None
    ok = got_drop == expect_drop
    if not ok:
        failures.append((label, expect_drop, got))
    shown = (got if got is not None else "(kept)")
    print("  %-4s %-38s day=%s -> %s" % ("OK" if ok else "FAIL", label, day, shown))
    if not ok:
        print("       expected: %s" % ("a drop" if expect_drop else "keep"))

# The trap in miniature: an entry a naive filter flags and the real rule keeps.
trap_text = SAMPLES[6][2]
trap_low = trap_text.lower()
naive_flag = [s for s in TRAP_SIGNATURES if s in trap_low]
rule_says = rule(trap_text, is_dispatch_session=IS_DISPATCH_SESSION, day=SAMPLES[6][1])
if not naive_flag or rule_says is not None:
    failures.append(("naive-vs-rule divergence", "naive flags, rule keeps", rule_says))
print(
    "  %-4s naive filter flags operator prose: matched=%s | shipped rule says: %s"
    % ("OK" if naive_flag and rule_says is None else "FAIL",
       [s.strip() for s in naive_flag] or "-",
       rule_says or "(kept)")
)
print(THIN)
if failures:
    print("SELF-CHECK FAILED — rule extraction is broken, the numbers below are void.")
    for label, want, got in failures:
        print("  %s: expected %s, got %r" % (label, want, got))
    raise SystemExit(1)
print("SELF-CHECK PASS — %d assertions. The gate can fire." % (len(SAMPLES) + 1))
print(LINE)

if MODE == "self-check":
    print("--self-check-only: archive pass not run.")
    raise SystemExit(0)

# ---------------------------------------------------------------------------
# 4. Section [2] — the archive pass.
# ---------------------------------------------------------------------------
per_file = []
skipped = []
all_entries = []
names = sorted(n for n in os.listdir(VDIR) if n.endswith(".md") and not n.startswith("."))
for name in names:
    path = os.path.join(VDIR, name)
    m = DAY_RE.match(name)
    with open(path, encoding="utf-8") as fh:
        parsed = parse_entries(fh.read())
    if not parsed:
        # Curated, hand-written files carry no timestamped entries. Reporting
        # them as 0 entries scanned would be a false statement about the file,
        # so they are named instead.
        skipped.append((name, m.group(1) if m else "no-date-prefix"))
        continue
    day = m.group(1) if m else "????-??-??"
    rows = [(day, stamp, text) for stamp, text in parsed]
    all_entries.extend(rows)
    per_file.append((day, name, rows))

total = measure(all_entries)

print("[2] PER-DAY-FILE MEASUREMENT")
print(THIN)
print("  %-10s  %7s  %7s  %9s  %s" % ("day", "entries", "dropped", "naive-fp", "file"))
for day, name, rows in per_file:
    r = measure(rows)
    print("  %-10s  %7d  %7d  %9d  %s" % (day, r["scanned"], r["dropped"], r["naive_all"], name))
print(THIN)
print("  %-10s  %7d  %7d  %9d  (%d day files)"
      % ("TOTAL", total["scanned"], total["dropped"], total["naive_all"], len(per_file)))
print(LINE)

print("[3] TOTALS AND REASON BREAKDOWN")
print(THIN)
print("  day files measured                        %d" % len(per_file))
print("  curated files skipped (no entries)        %d" % len(skipped))
print("  entries scanned                           %d" % total["scanned"])
print("  dropped by the SHIPPED rule               %d" % total["dropped"])
print("    of those, IN lane scope (>= %s)   %d" % (LANE_RULES_FROM, total["dropped_in_scope"]))
print("    of those, BEFORE lane scope               %d" % total["dropped_pre_scope"])
print("  entries actually IN lane scope             %d  (measured, not assumed)"
      % total["in_scope"])
print("  entries BEFORE lane scope                 %d"
      % (total["scanned"] - total["in_scope"]))
print(THIN)
if total["reasons"]:
    print("  reason breakdown (the shipped rule's own strings):")
    for reason, n in total["reasons"].most_common():
        print("    %-46s %d" % (reason[:46], n))
else:
    print("  reason breakdown: (none — nothing to break down)")
if total["drops"]:
    print(THIN)
    print("  DROPPED ENTRIES (day, stamp, reason, first line):")
    for day, stamp, reason, text in total["drops"][:40]:
        print("    %s %s  %s" % (day, stamp, reason))
        print("        %s" % text.strip().split("\n")[0][:100])
    if len(total["drops"]) > 40:
        print("    ... %d more" % (len(total["drops"]) - 40))
print(LINE)

print("[4] NAIVE SUBSTRING FILTER — FALSE POSITIVES, NOT DROPS")
print(THIN)
print("  LANE_SIGNATURES holds ordinary English (`read-only`, `exclusive`,")
print("  `repo at `, `timeout -k 5`). Applied as bare substrings it lands on")
print("  the operator's own sentences. These counts are NOT drops and must")
print("  never drive a deletion, a sweep, or a 'purity repair'.")
print(THIN)
print("  the shipped rule drops                        %6d" % total["dropped"])
print("  naive filter, all %-2d LANE_SIGNATURES        %6d" % (len(LANE_SIGNATURES), total["naive_all"]))
print("  naive filter, the 7 recorded signatures       %6d" % total["naive_trap"])
print("  difference (all-signatures naive - rule)      %6d  FALSE POSITIVES"
      % (total["naive_all"] - total["dropped"]))
if missing_trap:
    print("  (the 7 recorded signatures are NOT all in the shipped set — see the")
    print("   DRIFT WARNING above; the recorded 399 does not describe this rule)")
print(THIN)
print("  per-signature naive hits:")
for s, n in total["naive_all_per_sig"].most_common():
    print("    %-46s %d" % (repr(s)[:46], n))
if total["naive_examples"]:
    print(THIN)
    print("  examples a naive filter would have destroyed:")
    for day, stamp, hits, text in total["naive_examples"]:
        print("    %s `%s`  matched=%s" % (day, stamp, [h.strip() for h in hits]))
        for ln in text.strip().split("\n")[:2]:
            print("        %s" % ln[:100])
print(LINE)

print("[5] FILES SKIPPED (curated / hand-written, no timestamped entries)")
print(THIN)
for name, day in skipped:
    print("  %-10s %s" % (day, name))
print(LINE)

print("RESULT: %d entries scanned, %d the shipped rule would drop, %d naive "
      "false positives." % (total["scanned"], total["dropped"], total["naive_all"]))
print("REPORTING ONLY — this gate wrote nothing and deleted nothing.")
print("exit 0 = self-check passed and the measurement completed; the drop count")
print("is reported, never enforced, because the archive was extracted WITH this")
print("rule and a non-zero drop count means drift worth reading, not a repair to")
print("automate.")
raise SystemExit(0)
PY