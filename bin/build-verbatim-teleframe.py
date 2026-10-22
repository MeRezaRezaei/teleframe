#!/usr/bin/env python3
"""
build-verbatim-teleframe.py — reproducible teleframe verbatim extractor.

Re-generates .brain/verbatim/YYYY-MM-DD-<slug>.md + .brain/verbatim.md from
the opencode chat store, per the ruling in .brain/verbatim-coverage.md:
  - verbatim = role=user text parts of main-agent sessions (build/goal/codeg)
    for this repo, across the 4 teleframe dirs PLUS the recorded
    teleframe-origin sessions (projects/tele / /home/me);
  - byte-for-byte from the DB (CRLF preserved, never normalized);
  - dedupe same (timestamp, text); grouped by UTC day; globally chronological
    (session groups ordered by first entry, entries within a session by time);
  - EXCLUDED: subagent dispatch prompts (agent general/explore/goal-verify),
    synthetic control-plane text (synthetic=1), <pty_exited> process-exit
    blocks, and the 2 known agent-authored dispatch-prompt fingerprints.

Run:  python3 bin/build-verbatim-teleframe.py            # -> .brain/verbatim
Gap-closing pass (write ONLY the day files in a range, leave every other day
and the cumulative aggregate untouched — the aggregate is appended by hand in
that mode, see LANE_RULES_FROM for why the older days are not re-extracted):
  VERBATIM_DAYS=2026-10-13..2026-10-17 python3 bin/build-verbatim-teleframe.py
Regen check (idempotence; generated files must be byte-identical):
  NOT RUNNABLE ON THIS HOST — MEASURED 2026-10-18. Two preconditions, and
  this machine meets NEITHER, so the recipe below is an UNRUN gate, not a
  passing one. No agent may quote it as evidence until it has been run and
  pasted.

  1. The source store must be READABLE. The default below, /home/me/..., is
     another user's home: `stat /home/me/.local/share/opencode/opencode.db`
     returns EACCES for uid 1013(codeh) (measured), and the script now exits 2
     with a named classification instead of writing anything.
  2. It must be the SAME store the committed files came from. The chat store
     is per-user and per-project, so regenerating against a different one and
     diffing it against .brain/verbatim measures the difference between two
     databases, not idempotence. A green diff from the wrong store is worse
     than no diff.

  Where BOTH hold, the recipe is:
  export VERBATIM_DB=/path/to/the/same/store/opencode.db
  rm -rf /tmp/vregen && mkdir -p /tmp/vregen
  VERBATIM_OUT=/tmp/vregen/verbatim VERBATIM_MD_OUT=/tmp/vregen/verbatim.md \
    python3 bin/build-verbatim-teleframe.py
  diff -r .brain/verbatim /tmp/vregen/verbatim   # only the 10 curated files
                                               # may be "Only in .brain/…":
                                               # they are hand-written and the
                                               # generator never writes them.
  diff .brain/verbatim.md /tmp/vregen/verbatim.md

Security: every extracted text is passed through redact() before it reaches
disk (same base64-shaped-run rule as bin/scan-credentials.php), so a
regeneration can never re-commit a Telegram session string. That covers the
review file too, which now stores each dropped part's FULL text rather than a
stub, so it has more surface to guard, not less. Only a sha256 prefix is ever
printed. Set VERBATIM_MIN_RUN to change the threshold (200).

The coverage ledger (.brain/verbatim-coverage.md) is curated — not regenerated.
"""

import hashlib
import json
import collections
import errno
import os
import re
import sqlite3
import sys
from datetime import datetime, timezone

DB_DEFAULT = "/home/me/.local/share/opencode/opencode.db"
ROOT_DEFAULT = "/home/me/Documents/projects/teleframe"
DB = os.environ.get("VERBATIM_DB", DB_DEFAULT)
ROOT = os.environ.get("VERBATIM_ROOT", ROOT_DEFAULT)
VDIR = os.environ.get("VERBATIM_OUT", f"{ROOT}/.brain/verbatim")

# The 4 committed teleframe dirs + the teleframe-origin sessions recorded in
# the coverage ledger §1 (origin sessions live under projects/tele and /home/me;
# those dirs are NOT included wholesale — only these explicit session ids).
#
# The repo moved home: `/home/codeh/Documents/projects/teleframe` is the current
# working copy and holds the operator's own sessions from 2026-10-16 onward, so
# leaving it out silently truncates the archive at the day the move happened.
# Two further paths match `LIKE '%teleframe%'` — a literal
# `/home/me/Documents/projects/teleframe</workingdir>` and `/tmp/teleframe` — and
# are deliberately NOT here: all 7 of their user parts were measured (2026-10-17)
# and every one is an agent-authored lane brief, so including them would add
# noise and no operator words. See coverage ledger §11.
DIRS = (
    "/home/me/Documents/projects/teleframe",
    "/home/me/Documents/projects/teleframe-psr3",
    "/home/me/Documents/projects/teleframe-automation-6-run-47",
    "/home/me/Documents/projects/teleframe-automation-7-run-48",
    "/home/codeh/Documents/projects/teleframe",
)
ORIGIN_SESSIONS = (
    "ses_f9ab17d2dffeE25uU23KgOsezU",  # 2026-09-03 teleframe origin (/home/me)
    "ses_f948ee367ffey08taryQQ1z2r9",  # 2026-09-04..09-09 long-running (projects/tele)
    "ses_f7a16fc6cffeg8j8XYpSxbkYv7",  # 2026-09-09 app-core design (projects/tele)
    "ses_f79ac6da8ffezUnmLsR9DjrpBV",  # 2026-09-09 fork #2 (projects/tele)
)
MAIN_AGENTS = (
    "build",
    "goal",
    "codeg",
)  # main-agent lanes only; subagent lanes are excluded structurally below

# ---------------------------------------------------------------------------
# Exclusions applied post-extraction (ruling §5).
#
# opencode's chat store records NO authorship flag: the human's message and a
# subagent's dispatch prompt are BOTH role=user text parts, in different
# sessions, with an identical shape. Verified 2026-09-25 by inspecting the
# part/message/session rows of a known human message and a known dispatch
# prompt — the only differences are the session's parent_id and the text. So
# two layers are used:
#
#   1. STRUCTURAL, exact. `s.parent_id IS NULL` in the SQL excludes every
#      dispatched subagent of the task/delegate_to_agent paths; the ids in
#      state.metadata.sessionId are excluded as a second, independent source.
#      Both sets are the same 3,282 sessions across 19 agent lanes.
#
#   2. CONTENT, curated + auditable. The goal plugin's OWN fan-out creates
#      PARENTLESS sessions (agent='goal', title "New session - <ISO>") whose
#      sole user part is a prompt this agent wrote; layer 1 cannot see them.
#      The marker lists below hold the exact opening markers observed in the
#      store, anchored to the start of the part.
#
# Content markers are a maintenance surface, so they are never silent: EVERY
# dropped part is recorded in .brain/verbatim/.agent-text-review.md with its
# session id, timestamp and FULL text, and the run prints the count. Nothing
# the user said can disappear quietly — a false positive is visible in that
# review file on the very next run, and now visible in full rather than as a
# cut-off first line.
#
# Anchored prefix matching is safe here because the human's register never
# opens with these markers: his messages are lowercase and conversational
# (".", "ok now jsut contiue the work", "you seem to not extract index all
# verbtaims", "b", "ping", "continue").
CONTROL_PREFIXES = ("<goal_continuation>", "<pty_exited>")
AGENT_PROMPT_MARKERS = (
    # subagent persona / instruction openings written by the main agent
    "You are ",
    "You are a ",
    "Your task is ",
    "TASK —",
    "AUDIT TASK",
    "READ-ONLY GOAL EXTRACTION",
    "Read-only ",
    "READ-ONLY ",
    "Implementation wave",
    "IMPLEMENTATION ",
    "IMPLEMENT —",
    "IMPLEMENTATION —",
    "This delegated task was interrupted",
    "This task was interrupted",
    # wave / mission dispatch subject lines
    "Design the backup-store remainder",
    "Write usage documentation for the 5 release areas",
    "Fix daemon/backfill/relay-active-account robustness",
    "Fix UpdateDispatcher + StoreService robustness",
    "Fix bus-consumer resilience gaps",
    "Fix the per-set backup lock race",
    "DOCS (RETRY",
    "FIX (RETRY",
    "DOCS — usage documentation",
    "FIX — daemon fd churn",
    "FIX — dispatcher TypeError",
    "FIX — bus orphaned-pending",
    # the plugin echoing a goal change, not the human typing
    "New active goal:",
    # upstream provider / system notices relayed into the lane
    "The previous request exceeded the provider's size limit",
)
# Substring markers (not anchored) kept from the 2026-09-20 ruling.
DISPATCH_FINGERPRINTS = (
    "You are an autonomous engineering agent",
    "You are the opencode agent running the opencode-zen/big-pickle",
    "You are the next-goal extraction specialist",
    "You are a read-only",
)
# Whole-skill documents the agent pasted into its own lane as a dispatch body.
# These are markers ONLY in a dispatch session (see exclusion_reason): a human
# pasting the same document into a live conversation is the user sending a
# message, and the archive's duty is to store what the user sent.
AGENT_SKILL_DOC_HEADERS = (
    "# Multi-Parallel Nested Sub-Agent Goal-Driven Development",
    "# The Brain — Master Autonomous Companion",
)
AGENT_REVIEW_PATH = f"{ROOT}/.brain/verbatim/.agent-text-review.md"

# ---------------------------------------------------------------------------
# Lane-brief classifier — the ruling that coverage ledger §10 records as the
# one actually used on 2026-10-11/12, extended by the 2026-10-17 pass that
# closed 2026-10-13..17.
#
# WHY IT EXISTS. From 2026-10-10 the goal plugin fans out so hard that the
# OVERWHELMING MAJORITY of `role=user` parts are briefs this agent wrote, not
# speech from the operator: 2026-10-13 measured 124 user parts for 30 entries,
# 2026-10-15 measured 197 for 62. Storing raw `role=user` would fill the
# archive with dispatch prompts. A brief has NO fixed opening — one live brief
# on 2026-10-12 began "Closing the LAST TWO PRODUCTION PATHS…" and would have
# survived any prefix rule — so the signature set below is matched ANYWHERE in
# the body, case-insensitively. The first six entries are the ones §10 names
# verbatim; the rest are the rest of the set as measured on 2026-10-13..17.
# It is applied in this order, and every drop is written to
# .agent-text-review.md, so a false positive is a visible line, not a hole.
#
# SCOPED FORWARD, ON PURPOSE. Days before LANE_RULES_FROM still carry briefs
# (the pre-existing purity defect ledger §10 measured and deliberately left
# alone: their briefs interleave with real operator text, so a correct repair
# is a re-extraction, not a line deletion). Applying the signature set to them
# here would silently do that sibling task inside a gap-closing change.
# ---------------------------------------------------------------------------
LANE_RULES_FROM = "2026-10-11"
LANE_SIGNATURES = tuple(
    s.lower()
    for s in (
        # — the six §10 names —
        "⚠️ SHARED TREE",
        "YOU OWN",
        "Do NOT commit",
        "HARD RULES",
        "=== YOUR TASK ===",
        "delegate_to_agent",
        # — the rest of the set, as measured on 2026-10-13..17 —
        "Do not edit",
        "do not edit",
        "Do NOT edit",
        "do not commit",
        "read-only",
        "Touch NOTHING",
        "Touch ONLY",
        "touch NO source",
        "change NO source",
        "exclusive",
        "Own ONLY",
        "own ONLY",
        "own ONE",
        "own one turn",
        "lane node",
        "=== YOUR LANE",
        "Your full lane spec",
        "You are one lane",
        "You are lane",
        "Repo:",
        "repo at ",
        "Every command bounded",
        "timeout -k 5",
        "write ONE file",
        "Write ONE file",
        "write one file",
        "and stop. Keep it",
        "Write the file and stop",
        "write the deliverable, stop",
        "Do not re-scrape",
        "DO NOT SCRAPE",
        "finish and stop",
        "write the deliverable",
        "MEASUREMENT + DOCUMENTATION",
        "MEASUREMENT lane",
        "This is a MEASUREMENT",
        "Lane `",
    )
)
# The harness's own nudge, stored as a `role=user` part with nothing of the
# operator's in it. Exact match only — the operator has written short lines
# that begin the same way, and those are his.
HARNESS_NUDGE = (
    "Continue if you have next steps, or stop and ask for clarification "
    "if you are unsure how to proceed."
)
# `[automatic recovery]` parts: keep the wrapper inline (the archive has stored
# it that way since 2026-10-03) ONLY when the operator appended words of his
# own after the boilerplate; drop it when nothing of his remains.
RECOVERY_PREFIX = "[automatic recovery]"
RECOVERY_TAIL = (
    "Re-read your last state and carry on.",
    "Do not apologise and do not restart the task.",
)
# A pasted skill/document body arriving in a LIVE conversation is the operator
# pasting it (ruling §5, and the incident in §9 where this extractor was about
# to drop the very message that said "doing the verbatims"). The same markers
# inside a one-part session are the agent pasting into its own lane, and the
# structural tie-breaker in exclusion_reason() decides it.
PASTED_DOC_MARKERS = (
    "# The Brain — Master Autonomous Companion",
    "# brain-testing",
    "# Verbatim Goal Anchoring",
    "> **Read this when**",
    "Base directory for this skill:",
    "<!-- nmem:skill-outcome-footer v1 -->",
)
# Goal-plugin `⚠️ Replacing active goal:` events: both operator goal texts are
# his and are kept; the machine footer the plugin appends is not.
GOAL_PLUGIN_HEAD = "⚠️ Replacing active goal:"
GOAL_PLUGIN_FOOTER = "Start working toward this goal now"

# ---------------------------------------------------------------------------
# Credential guard (mirrors bin/scan-credentials.php rule 1).
#
# The 2026-09-14 archive carried a real Telegram session string inside a pasted
# SQLSTATE error. Regeneration from the chat store would reintroduce it, so the
# generator itself redacts every base64-shaped run >= --min chars before any
# text reaches disk. Matches the PHP guard: [A-Za-z0-9+/]{min,}={0,2}; the
# value is never printed, only path-free sha256-prefixed markers, so the
# generator's own stdout is safe to paste into a commit message or a report.
# ---------------------------------------------------------------------------
MIN_RUN = int(os.environ.get("VERBATIM_MIN_RUN", "200"))
_B64 = re.compile(r"[A-Za-z0-9+/]{%d,}={0,2}" % MIN_RUN)


def redact(txt, sid=""):
    """Replace credential-shaped runs with a sha256-prefixed marker."""
    n = [0]

    def sub(m):
        n[0] += 1
        digest = hashlib.sha256(m.group(0).encode()).hexdigest()[:12]
        return f"[REDACTED: credential-shaped run — sha256:{digest}]"

    out = _B64.sub(sub, txt)
    if n[0]:
        print(
            f"  redacted {n[0]} credential-shaped run(s) (>= {MIN_RUN} chars) sha256-withheld",
            file=sys.stderr,
        )
    return out


def goal_plugin_trim(txt):
    """Keep both operator goal texts of a goal-plugin event; drop its footer.

    The plugin wraps a goal change in machine text: the operator's old goal,
    a hint line, the operator's new goal, then "Start working toward this goal
    now…" and a run-on instruction block that is not his. Both goal texts stay
    byte-for-byte; only the trailing machine footer is cut.
    """
    if not txt.lstrip().startswith(GOAL_PLUGIN_HEAD):
        return txt
    i = txt.find(GOAL_PLUGIN_FOOTER)
    return txt if i < 0 else txt[:i].rstrip()


def exclusion_reason(txt, is_dispatch_session=False, day=None):
    """Why this role=user part is machine/agent text, or None to keep it.

    Order matters: control-plane blocks are exact, then the anchored agent
    prompt markers, then the substring fingerprints, then pasted skill docs.

    `is_dispatch_session` is the structural tie-breaker for the two ambiguous
    cases — a pasted skill document and a bare "Read-only …" style line. A
    dispatch/fan-out session has exactly ONE user part: the prompt the agent
    wrote. A live conversation has many. So when the same text arrives in a
    one-part session it is agent-authored, and when it arrives in a session
    alongside the user's other messages it is the user's, and is kept.

    Without that tie-breaker the choice is impossible either way: dropping a
    document the user actually pasted loses his words, and keeping a dispatch
    prompt files the agent's prose in the user's mouth. Over-inclusion of one
    pasted document is recoverable and visible; losing a user message is not.

    `day` enables the lane-brief layers, which are scoped forward from
    LANE_RULES_FROM (see the comment on that constant).
    """
    head = txt.lstrip()
    for pfx in CONTROL_PREFIXES:
        if head.startswith(pfx) or pfx in txt[:200]:
            return f"control-plane:{pfx}"
    for pfx in AGENT_PROMPT_MARKERS:
        if head.startswith(pfx):
            return f"agent-prompt-prefix:{pfx!r}"
    for fp in DISPATCH_FINGERPRINTS:
        if fp in txt:
            return f"agent-fingerprint:{fp!r}"
    if is_dispatch_session:
        for hd in AGENT_SKILL_DOC_HEADERS:
            if head.startswith(hd):
                return f"agent-skill-doc:{hd!r}"
    if day is None or day < LANE_RULES_FROM:
        return None
    # -- lane-brief layers (2026-10-11 onward; see LANE_SIGNATURES) ----------
    if head.strip() == HARNESS_NUDGE:
        return "harness-nudge:exact"
    if head.startswith(RECOVERY_PREFIX):
        cut = -1
        for tail in RECOVERY_TAIL:
            i = head.find(tail)
            if i >= 0:
                cut = max(cut, i + len(tail))
        if not head[cut:].strip():
            return "recovery-boilerplate-only"
        return None  # the operator appended words of his own — keep it inline
    # A pasted document in a live conversation is his paste, whatever markers
    # it carries inside. Checked BEFORE the signature set, because a pasted
    # brain-skill body contains `delegate_to_agent` and `HARD RULES` itself.
    if not is_dispatch_session and any(m in txt for m in PASTED_DOC_MARKERS):
        return None
    low = txt.lower()
    for sig in LANE_SIGNATURES:
        if sig in low:
            return f"lane-brief:{sig!r}"
    return None


# ---------------------------------------------------------------------------
# Source-database preflight.
#
# WHY IT EXISTS. The archive is the project's fixed point, and this script is
# the only thing that can regenerate it — so when it cannot run, the reason has
# to be NAMED. Measured 2026-10-18 on this host: the repo moved home, so the
# default store above still points at /home/me, which belongs to another user.
# `/home/me` and `/home/me/.local` are both drwxr-xr-x (measured), but
# `stat /home/me/.local/share/opencode/opencode.db` returns EACCES for uid
# 1013(codeh), and sqlite3 reports that as exactly one line:
#
#     sqlite3.OperationalError: unable to open database file
#
# That string reads like a missing file and sends the next reader hunting for
# a typo instead of at a permissions wall. Measured: it also hides a THIRD
# distinct blocker, because sqlite3.connect() on a readable non-SQLite file
# SUCCEEDS — the header check is deferred to the first query, which then raises
# DatabaseError / SQLITE_NOTADB (26). So "unable to open", "not a database" and
# "no such table" were three different walls behind one indistinguishable line.
#
# Every arm below is decided by os.stat, by os.access, by the file's own 16-byte
# header, by the tables sqlite_master actually reports, or by the TYPE and
# error code of an inspected exception. None of them compares an error message
# against a fixed expected string — that string comparison is precisely the
# trap that produced the one unhelpful line above.
# ---------------------------------------------------------------------------
# Tables the two queries below cannot run without. Named, because "this opened
# fine but it is not the chat store" is a different blocker with a different
# fix from "this is not a database".
EXPECTED_TABLES = ("session", "message", "part")
SQLITE_MAGIC = b"SQLite format 3\x00"


def _db_failure(reason):
    """Print a classified, actionable reason and return the exit status.

    Returns 2 rather than raising, so every call site reads
    `raise SystemExit(_db_failure(...))`: that makes each failure arm terminal
    in the obvious way, which also keeps the values computed just above it — the
    stat result, the header bytes, the table list — from looking unbound to a
    reader or to a checker.

    Exit 2, never 0: an unusable source store is not a soft failure. There is no
    fallback store to degrade to, so the archive simply cannot be produced, and
    a script that exited 0 there would report a regeneration that never
    happened — the exact failure the working-tree-is-not-the-record rule in
    AGENTS.md exists to stop.
    """
    print(f"verbatim: source database unusable — {reason}", file=sys.stderr)
    return 2


def _errno_name(exc):
    """`EACCES`, `ENOENT`, … for an OSError; the report must never raise here."""
    code = getattr(exc, "errno", None)
    return "unset" if code is None else errno.errorcode.get(code, str(code))


def open_source_db(path):
    """Classify `path`, then open it read-only. Raises SystemExit(2) if unusable.

    The connection stays read-only via URI so a concurrent opencode process can
    never be locked out or corrupted by this script.
    """
    # Named as a suggestion only, and never opened: the sibling store of the
    # user running the script. Listing it as a HINT (not as a path tried) keeps
    # the report honest about what was actually probed.
    home_store = os.path.join(
        os.path.expanduser("~"), ".local", "share", "opencode", "opencode.db"
    )
    try:
        st = os.stat(path)
    except FileNotFoundError as e:
        raise SystemExit(
            _db_failure(
                f"the file does not exist. Path tried: {path}. VERBATIM_DB "
                f"overrides the built-in default ({DB_DEFAULT}), and "
                f"VERBATIM_ROOT likewise overrides {ROOT_DEFAULT}. The store "
                f"this script reads is opencode's chat database. Not tried by "
                f"this run: {home_store} (hint only). errno={_errno_name(e)}"
            )
        )
    except PermissionError as e:
        raise SystemExit(
            _db_failure(
                f"permission denied while stat()ing the path, so no component of "
                f"it is reachable by this user. Path tried: {path}. "
                f"uid={os.getuid()} ({_uid_name(os.getuid())}). "
                f"errno={_errno_name(e)}. If the path lives under another user's "
                f"home, nothing on this side can fix it — point VERBATIM_DB at a "
                f"store this user can read."
            )
        )
    except OSError as e:
        raise SystemExit(
            _db_failure(
                f"the path could not be stat()ed. Path tried: {path}. "
                f"{type(e).__name__} errno={_errno_name(e)}"
            )
        )
    if not os.path.isfile(path):
        raise SystemExit(
            _db_failure(
                f"the path exists (stat ok) but is not a regular file. "
                f"Path tried: {path}."
            )
        )
    if not os.access(path, os.R_OK):
        raise SystemExit(
            _db_failure(
                f"the file exists and IS this user's to stat, but it is NOT "
                f"readable by this user. Path tried: {path}. "
                f"uid={os.getuid()} ({_uid_name(os.getuid())}), file owner "
                f"uid={st.st_uid} ({_uid_name(st.st_uid)}), "
                f"mode={oct(st.st_mode & 0o777)}. This is a permissions wall, "
                f"not a missing file — chmod/chown it, or point VERBATIM_DB at "
                f"a store this user can read."
            )
        )
    # Readable. Check the SQLite header MYSELF before connecting: measured,
    # sqlite3.connect() happily returns a handle on a readable non-database and
    # only fails on first use (DatabaseError / SQLITE_NOTADB = 26), which would
    # put "wrong file" behind the same one uninformative line as everything else.
    try:
        with open(path, "rb") as fh:
            head = fh.read(len(SQLITE_MAGIC))
    except OSError as e:
        raise SystemExit(
            _db_failure(
                f"the file exists and os.access said it is readable, but opening "
                f"it for reading still failed. Path tried: {path}. "
                f"{type(e).__name__} errno={_errno_name(e)}"
            )
        )
    if head != SQLITE_MAGIC:
        raise SystemExit(
            _db_failure(
                f"the file exists, is readable, and is NOT a SQLite database "
                f"(first bytes {head!r}, expected {SQLITE_MAGIC!r}). Path tried: "
                f"{path}. This is the wrong file, not an unreadable one."
            )
        )
    try:
        con = sqlite3.connect(f"file:{path}?mode=ro", uri=True)
    except sqlite3.Error as e:
        raise SystemExit(
            _db_failure(
                f"the file passed the header check but sqlite3 refused to open "
                f"it. Path tried: {path}. {type(e).__name__} "
                f"sqlite_errname={getattr(e, 'sqlite_errname', None)} "
                f"sqlite_errorcode={getattr(e, 'sqlite_errorcode', None)}"
            )
        )
    try:
        tables = {r[0] for r in con.execute("SELECT name FROM sqlite_master")}
    except sqlite3.Error as e:
        con.close()
        raise SystemExit(
            _db_failure(
                f"the file passed the header check and opened, but sqlite3 "
                f"refused to read its catalogue. Path tried: {path}. "
                f"{type(e).__name__} "
                f"sqlite_errname={getattr(e, 'sqlite_errname', None)} "
                f"sqlite_errorcode={getattr(e, 'sqlite_errorcode', None)}"
            )
        )
    missing = [t for t in EXPECTED_TABLES if t not in tables]
    if missing:
        con.close()
        raise SystemExit(
            _db_failure(
                f"the file opened as a valid SQLite database but is not the "
                f"opencode chat store: missing table(s) {', '.join(missing)} (of "
                f"{', '.join(EXPECTED_TABLES)}). Path tried: {path}. Tables "
                f"found: {', '.join(sorted(tables)) or '(none)'}. Point "
                f"VERBATIM_DB at the chat store, not at some other .db."
            )
        )
    return con


def _uid_name(uid):
    """Best-effort account name for a uid; the report must never raise here."""
    try:
        import pwd

        return pwd.getpwuid(uid).pw_name
    except Exception:
        return "unknown"


con = open_source_db(DB)
dir_placeholders = ",".join("?" * len(DIRS))
agent_placeholders = ",".join("?" * len(MAIN_AGENTS))
rows = []
dropped = []

# How many role=user text parts does each session have? A dispatch/fan-out
# session has exactly ONE — the prompt this agent wrote. A live conversation has
# many. This is the structural tie-breaker for the ambiguous exclusions.
parts_per_session = collections.Counter(
    row[0]
    for row in con.execute(
        f"""
        SELECT m.session_id
        FROM part p JOIN message m ON m.id = p.message_id
        WHERE ( m.session_id IN (SELECT id FROM session WHERE parent_id IS NULL
                                  AND directory IN ({dir_placeholders}))
                OR m.session_id IN ({",".join("?" * len(ORIGIN_SESSIONS))}) )
          AND json_extract(p.data,'$.type') = 'text'
          AND json_extract(m.data,'$.role') = 'user'
        """,
        (*DIRS, *ORIGIN_SESSIONS),
    )
)
for sid, agent, title, screated, mt, data in con.execute(
    f"""
    SELECT s.id, s.agent, s.title, s.time_created, p.time_created, p.data
    FROM part p
    JOIN message m ON m.id = p.message_id
    JOIN session s ON s.id = m.session_id
    WHERE ( s.directory IN ({dir_placeholders})
            OR s.id IN ({",".join("?" * len(ORIGIN_SESSIONS))}) )
      AND json_extract(p.data,'$.type') = 'text'
      AND json_extract(m.data,'$.role') = 'user'
      AND ( COALESCE(json_extract(p.data,'$.synthetic'),0) != 1
            OR json_extract(p.data,'$.text') LIKE ? )
      AND s.parent_id IS NULL
      AND s.agent IN ({agent_placeholders})
    ORDER BY p.time_created
    """,
    (*DIRS, *ORIGIN_SESSIONS, GOAL_PLUGIN_HEAD + "%", *MAIN_AGENTS),
):
    txt = ""
    try:
        txt = json.loads(data).get("text", "")
    except Exception:
        continue
    if not txt or not txt.strip():
        continue
    day = datetime.fromtimestamp(int(mt) / 1000, timezone.utc).strftime("%Y-%m-%d")
    # Both operator goal texts of a goal-plugin event are his; the machine
    # footer the plugin appends to them is not. The event itself arrives
    # synthetic=1, so the SQL above exempts exactly this shape; days before
    # LANE_RULES_FROM keep dropping it, because their day files were extracted
    # without this rule and must stay byte-identical.
    is_goal_event = txt.lstrip().startswith(GOAL_PLUGIN_HEAD)
    txt = goal_plugin_trim(txt)
    if not txt.strip():
        continue
    if is_goal_event and day < LANE_RULES_FROM:
        dropped.append(
            dict(
                sid=sid,
                mt=int(mt),
                reason="goal-plugin-event:pre-lane-rules-scope",
                text=redact(txt, sid),
            )
        )
        continue
    reason = exclusion_reason(
        txt, is_dispatch_session=parts_per_session.get(sid, 0) <= 1, day=day
    )
    if reason:
        dropped.append(
            dict(
                sid=sid,
                mt=int(mt),
                reason=reason,
                text=redact(txt, sid),
            )
        )
        continue
    rows.append(
        dict(
            sid=sid,
            agent=agent,
            title=title,
            screated=int(screated),
            mt=int(mt),
            txt=redact(txt, sid),
        )
    )
con.close()


# Every dropped part is recorded, never silently discarded — see the block
# comment above AGENT_PROMPT_MARKERS. A false positive is a visible entry here.
#
# FULL TEXT, not a stub. MEASURED 2026-10-18 on the committed review file: 1462
# dropped parts, every one of them stored as its first line cut at 110
# characters (1046 of them hit the cap exactly). For the two
# `goal-plugin-event:pre-lane-rules-scope` parts that cut the operator's OWN
# goal texts mid-word — "…a faithful COPY of the database Telegram itself
# designed " and "…prove the code is logicall" — and the day files deliberately
# drop those events, so that review file is the LAST place either text exists.
# A stub there loses his words a second time, in the very file whose header
# promises they are never dropped silently. So the whole part is stored, and
# the header says so.
def _fence(text):
    """A backtick fence the text cannot terminate, whatever it contains.

    Agent briefs routinely carry their own ``` fences and 4-space-indented
    blocks. Indenting the text to nest it in a list, or using a fixed ``` of 3,
    both let the content break out of the block and continue as live markdown.
    So the text is emitted at column 0 inside a fence strictly longer than the
    longest backtick run it contains: under CommonMark no line of it can then
    close the block early, and the fence body is the text verbatim.
    """
    runs = re.findall(r"`+", text)
    longest = max((len(r) for r in runs), default=0)
    return "`" * max(3, longest + 1)


def write_agent_review(dropped):
    by_reason = collections.Counter(d["reason"] for d in dropped)
    L = [
        "# Agent-authored text review (generated; do not edit)",
        "",
        "> Written by `bin/build-verbatim-teleframe.py` on every run. It lists every",
        "> `role=user` text part that the extractor dropped as machine / agent",
        "> authored rather than human. The human's raw words must never be dropped",
        "> silently — if a real message of his ever shows up here, add a narrower",
        "> marker to the script instead of widening an existing one.",
        ">",
        "> **The text under each heading is the part's FULL content** — not a",
        "> preview, not the first line, nothing cut off at a character budget. It",
        "> was a 110-character first-line stub until 2026-10-18, which truncated",
        "> the operator's own goal texts mid-word in the only file they survive in.",
        "> Each heading also carries the part's line and character counts, so a",
        "> reader can check completeness without re-counting the block below.",
        ">",
        "> One transformation is applied before anything reaches this file:",
        "> `redact()` replaces every credential-shaped base64 run of >=",
        "> VERBATIM_MIN_RUN (200) characters with a sha256-prefixed marker, so a",
        "> regeneration can never re-commit a Telegram session string. So",
        '> "full text" means full EXCEPT for that guard — a marker here is a',
        "> redaction, never a truncation.",
        "",
        "| Reason | Parts |",
        "|---|---|",
    ]
    for reason, n in sorted(by_reason.items(), key=lambda kv: -kv[1]):
        L.append(f"| `{reason}` | {n} |")
    L += [
        "",
        f"Total dropped: **{len(dropped)}** parts.",
        "",
        "## Dropped parts — full text",
        "",
    ]
    for i, d in enumerate(sorted(dropped, key=lambda d: d["mt"]), 1):
        when = datetime.fromtimestamp(d["mt"] / 1000, timezone.utc).strftime(
            "%Y-%m-%d %H:%M:%S UTC"
        )
        text = d["text"]
        L.append(
            f"### {i}. `{when}` · `{d['sid']}` · {d['reason']} "
            f"({len(text.splitlines())} lines, {len(text)} chars)"
        )
        L.append("")
        fence = _fence(text)
        L.append(fence)
        L.extend(text.split("\n"))
        L.append(fence)
        L.append("")
    os.makedirs(os.path.dirname(AGENT_REVIEW_PATH), exist_ok=True)
    with open(AGENT_REVIEW_PATH, "w") as f:
        f.write("\n".join(L).rstrip("\n") + "\n")


write_agent_review(dropped)

sess_rows = collections.defaultdict(list)
for r in rows:
    sess_rows[r["sid"]].append(r)

session_meta = {}
for r in rows:
    if r["sid"] not in session_meta:
        session_meta[r["sid"]] = dict(
            agent=r["agent"],
            title=r["title"],
            # Two stamps: `created` is the session's own creation time and is
            # what the §10-format files carry; `created_first` is the first
            # surviving entry's time, which is what the earlier day files were
            # rendered with. Both are kept so one change cannot silently
            # re-render a day file that was audited under the other rule.
            created=datetime.fromtimestamp(r["screated"] / 1000, timezone.utc),
            created_first=datetime.fromtimestamp(r["mt"] / 1000, timezone.utc),
        )


# fork detection: identical full text-set -> same canonical (dedup join)
def sig(sid):
    return tuple(sorted(r["txt"] for r in sess_rows[sid]))


seen = {}
forks = collections.defaultdict(list)
canonical = set()
# Ordered by first surviving entry, NOT by session creation time: which copy of
# a fork is canonical must not move when only the rendered header stamp changed.
for sid in sorted(sess_rows, key=lambda s: session_meta[s]["created_first"]):
    s = sig(sid)
    if s in seen:
        forks[seen[s]].append(sid)
    else:
        seen[s] = sid
        canonical.add(sid)

keep = []
for sid in canonical:
    group = list(sess_rows[sid])
    for fid in forks[sid]:
        group += sess_rows[fid]
    seen_pairs = set()
    for r in sorted(group, key=lambda r: r["mt"]):
        k = (r["mt"], r["txt"])
        if k in seen_pairs:
            continue
        seen_pairs.add(k)
        keep.append(
            dict(
                sid=sid,
                agent=session_meta[sid]["agent"],
                title=session_meta[sid]["title"],
                mt=r["mt"],
                txt=r["txt"],
            )
        )

by_date = collections.OrderedDict()
for r in keep:
    d = datetime.fromtimestamp(r["mt"] / 1000, timezone.utc).strftime("%Y-%m-%d")
    by_date.setdefault(d, []).append(r)

desc = {
    "2026-09-03": "teleframe-origin",
    "2026-09-04": "package-decomposition",
    "2026-09-05": "engine-extraction",
    "2026-09-06": "teleframe-naming-and-merge",
    "2026-09-07": "teleclient-merge",
    "2026-09-08": "consolidation",
    "2026-09-09": "app-core-design",
    "2026-09-12": "telegram-relational-migrations-and-package-structure",
    "2026-09-13": "framework-vision-and-skill-seeding",
    "2026-09-14": "nf5-schema-remediation-and-progress-rally",
    "2026-09-15": "mysql-truth-false-positive-fix-and-domain-curation",
    "2026-09-16": "parallel-subagent-speedup-and-deep-wire-psr3",
    "2026-09-17": "update-state-backfill-full-history",
    "2026-09-18": "gap-sweep-queries-depth-and-proxy-tunneling",
    "2026-09-19": "os-stop-resume",
    "2026-09-20": "verbatim-history-rebuild-and-control-layer",
}

os.makedirs(VDIR, exist_ok=True)
day_files = []
# VERBATIM_DAYS=2026-10-13..2026-10-17 restricts WHICH day files this run
# writes, so a pass that closes a date gap cannot silently rewrite the older
# day files it did not audit. With it set, the cumulative aggregate is NOT
# rebuilt: a partial day set would drop every other day from .brain/verbatim.md,
# which is exactly the "rolling latest-only" failure the aggregate exists to
# prevent. Append those sections by hand (or re-run without VERBATIM_DAYS).
DAYS_ENV = os.environ.get("VERBATIM_DAYS", "").strip()
# The date the header claims for a §10-style day file. VERBATIM_EXTRACTED_ON
# overrides it; it is the honest knob to turn when a later pass re-extracts.
EXTRACTED_ON = os.environ.get(
    "VERBATIM_EXTRACTED_ON", datetime.now(timezone.utc).strftime("%Y-%m-%d")
)
selected = None
if DAYS_ENV:
    lo, _, hi = DAYS_ENV.partition("..")
    selected = (lo.strip(), (hi.strip() or lo.strip()))
    print("day filter:", selected[0], "..", selected[1], "(aggregate not rebuilt)")
for d, rs in sorted(by_date.items()):
    if selected and not (selected[0] <= d <= selected[1]):
        continue
    slug = desc.get(d, "notes")
    fn = f"{VDIR}/{d}-{slug}.md"
    # session groups ordered by first entry time (globally chronological);
    # entries within a session by time — matches the committed layout.
    sess_order = sorted(
        set(r["sid"] for r in rs),
        key=lambda s: min(r["mt"] for r in rs if r["sid"] == s),
    )
    L = []
    L.append(f"# Verbatim — {d} ({slug.replace('-', ' ')})")
    L.append("")
    if d >= LANE_RULES_FROM:
        # The day-file header written by the §10 pass, kept verbatim so the
        # files it produced and the files this pass produces read the same.
        L.append(
            f"> Source: user (role=user) text parts from main-agent sessions in opencode for this repo "
            f"(directory: teleframe). Extracted {EXTRACTED_ON} from "
            f"`~/.local/share/opencode/opencode.db` (read-only). Stored byte-for-byte; nothing paraphrased "
            f"or trimmed. Spelling left as the user wrote it."
        )
        L.append(">")
        L.append(
            "> Classification: a user part counts as verbatim only when operator words remain after "
            "stripping harness/agent boilerplate. Agent dispatch prompts (lane briefs), resume prompts "
            "and `<goal_continuation>` blocks are agent-authored and excluded, per the existing ruling "
            "(see `.brain/verbatim-coverage.md` §5). Where the operator appended his own words to an "
            "`[automatic recovery]` block or pasted the brain skill body, only his words are stored."
        )
    else:
        L.append(
            "> Source: user (role=user) text parts from main-agent sessions (build/goal/codeg) in opencode for this repo "
            "(directories: teleframe, teleframe-psr3, teleframe-automation-6-run-47, teleframe-automation-7-run-48, "
            "plus the teleframe-origin sessions under projects/tele and /home/me). Extracted 2026-09-20 from "
            "`~/.local/share/opencode/opencode.db` (read-only). Stored byte-for-byte; nothing paraphrased or trimmed. "
            "Spelling left as the user wrote it."
        )
    L.append("")
    L.append(
        f"**Sessions in this day:** {len(sess_order)} · **verbatim entries:** {len(rs)}"
    )
    L.append("")
    for sid in sess_order:
        meta = session_meta[sid]
        created = meta["created"] if d >= LANE_RULES_FROM else meta["created_first"]
        L.append(f"## Session: {meta['title']}")
        L.append(f"- opencode session id: `{sid}`")
        L.append(
            f"- agent: `{meta['agent']}` · created: {created.strftime('%Y-%m-%d %H:%M:%S')} UTC"
        )
        if sid in forks and forks[sid]:
            fl = ", ".join(f"`{x}`" for x in forks[sid])
            L.append(
                f"- NOTE: {len(forks[sid])} fork copy(ies) with identical content skipped — see coverage ledger ({fl})"
            )
        L.append("")
        for r in sorted([x for x in rs if x["sid"] == sid], key=lambda r: r["mt"]):
            t = datetime.fromtimestamp(r["mt"] / 1000, timezone.utc).strftime(
                "%H:%M:%S UTC"
            )
            L.append(f"`{t}`")
            if d >= LANE_RULES_FROM:
                # §10 format: the quote prefix is applied to every line, so a
                # blank line inside the quote renders as "> " — the same shape
                # the 2026-10-11/12 files already carry. `.gitattributes` marks
                # this archive `-whitespace`, so that trailing space is kept and
                # `git diff --check` stays clean.
                L.append("\n".join("> " + ln for ln in r["txt"].split("\n")))
            else:
                # The "> " prefix is our formatting, not the user's bytes, so a
                # blank line inside the quote renders as a bare ">" (same
                # blockquote, no trailing whitespace for `git diff --check`).
                L.append(
                    "\n".join(("> " + ln) if ln else ">" for ln in r["txt"].split("\n"))
                )
            L.append("")
    while L and L[-1] == "":
        L.pop()
    with open(fn, "w") as f:
        f.write("\n".join(L) + "\n")
    day_files.append((d, fn))
    print("day:", d, os.path.basename(fn), len(rs), "entries")

if selected:
    # Partial pass: day files only. See the VERBATIM_DAYS note above.
    print(
        "day files written:",
        len(day_files),
        "| aggregate .brain/verbatim.md NOT rebuilt (partial day set)",
    )
    raise SystemExit(0)

# .brain/verbatim.md — the cumulative aggregate (see the H[] block near the end)
latest_d = max(by_date)

# Curated (manually written) files in the repo's verbatim dir that the extractor
# does not produce. They are indexed too, so `.brain/verbatim.md` is a complete
# per-file index of the archive. Scanned from the REPO dir on purpose: it makes
# the index identical whether we regenerate in place or into a temp dir for the
# idempotence check.
REPO_VDIR = f"{ROOT}/.brain/verbatim"
day_names = {os.path.basename(fn) for _d, fn in day_files}
supplementals = []
if os.path.isdir(REPO_VDIR):
    for name in sorted(os.listdir(REPO_VDIR)):
        if not name.endswith(".md") or name.startswith(".") or name in day_names:
            continue
        supplementals.append(name)

STAMP = re.compile(r"^`\d{2}:\d{2}:\d{2} UTC`$")
QUOTE = re.compile(r"^>")
HEADING = re.compile(r"^(#{1,6}\s|---$)")


def entry_count(name):
    """Verbatim blocks held by a curated file, counted honestly.

    Extracted day files stamp each entry with `HH:MM:SS UTC`, so their count is
    exact and comes from the query. Curated files are hand-written and store
    their text in two different shapes, so counting quoted lines alone would
    report 0 for a file that stores the user's text UNQUOTED as a single
    flowing block — which is exactly what
    2026-09-24-notification-logger-backup-and-bot-framework.md does, and it is
    the source of five checked goals. Reporting 0 there was a false statement
    about the archive, so the count is derived from the structure instead:

      * the title line and the leading `> Source:` provenance block are header,
        not user text;
      * what remains is split into blocks at a markdown heading, a `---` rule, or
        a run of 2+ blank lines;
      * single blank lines inside one utterance do NOT split it, so a long
        single user message counts as the 1 block it really is.

    A file with body text can therefore never report 0.
    """
    path = os.path.join(REPO_VDIR, name)
    try:
        with open(path, encoding="utf-8") as fh:
            lines = fh.read().split("\n")
    except OSError:
        return 0
    body, in_header = [], True
    for ln in lines:
        if in_header and (not ln.strip() or ln.startswith("#")):
            continue
        if in_header and QUOTE.match(ln):
            continue  # the "> Source:" provenance block
        in_header = False
        body.append(ln)
    blocks, cur, blanks = 0, False, 0
    for ln in body:
        if not ln.strip():
            blanks += 1
            cur = False
            continue
        if HEADING.match(ln.strip()) and cur is False and blanks == 0 and blocks:
            pass
        if (HEADING.match(ln.strip()) or ln.strip() == "---") and cur:
            blocks += 1
            cur = False
        if blanks >= 2 and not cur and blocks:
            blocks += 1
            cur = False
        blanks = 0
        if not cur:
            blocks += 1
            cur = True
    if not body and lines:
        return 1
    return blocks


H = []
H.append("# Project Verbatim (cumulative archive) — teleframe")
H.append("")
H.append(
    "> The user's raw words are the fixed point of the work. This file is the "
    "CUMULATIVE archive: the index, then every day's full raw text in "
    "chronological order. Nothing is ever replaced by a newer day. The same "
    "content is split per day into `.brain/verbatim/YYYY-MM-DD-<slug>.md` for "
    "reading. See `.brain/verbatim-coverage.md` for the source inventory and "
    "coverage ledger."
)
H.append("")
H.append(
    "> Repo-side verbatim (pre-dates the chat extraction era): `.brain/verbatims/teleframe-vision.md` — the "
    "framework vision (Laravel patterns copied into a Telegram framework), committed 2026-09-13 as "
    "`6be43eb5`. It is the earliest raw vision; the chat extraction below spans 2026-09-03 → "
    f"{latest_d} (extended 2026-09-20 to the teleframe-origin sessions; see the coverage ledger)."
)
H.append("")
H.append("## History index — extracted days (DB-derived user text)")
H.append("")
H.append("| Date | File | Verbatim entries |")
H.append("|---|---|---|")
for d, fn in sorted(day_files):
    H.append(f"| {d} | `.brain/verbatim/{os.path.basename(fn)}` | {len(by_date[d])} |")
H.append("")
if supplementals:
    H.append("## History index — curated files (manually written, indexed in full)")
    H.append("")
    H.append("| Date | File | Entries | Note |")
    H.append("|---|---|---|---|")
    for name in supplementals:
        d = name[:10]
        note = "curated"
        if "harness-app-fact" in name:
            note = "NOT verbatim — relocated agent note"
        H.append(f"| {d} | `.brain/verbatim/{name}` | {entry_count(name)} | {note} |")
    H.append("")
H.append("---")
H.append("")
H.append(
    f"## Full archive — every day, chronological ({len(day_files)} days · {len(keep)} entries)"
)
H.append("")
for _d, fn in sorted(day_files):
    with open(fn) as fh:
        body = fh.read()
    # The day file opens with its own H1; demote it so the aggregate keeps one
    # H1 and each day nests as an H2 section.
    H.append(
        "\n".join(("#" + ln) if ln.startswith("# ") else ln for ln in body.splitlines())
    )
    H.append("")

verbatim_md_path = os.environ.get("VERBATIM_MD_OUT", f"{ROOT}/.brain/verbatim.md")
with open(verbatim_md_path, "w") as f:
    f.write("\n".join(H).rstrip("\n") + "\n")

print(
    "total canonical entries:",
    len(keep),
    "| groups:",
    len(canonical),
    "| days:",
    len(by_date),
    "| curated files indexed:",
    len(supplementals),
)
