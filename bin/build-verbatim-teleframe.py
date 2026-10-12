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
Regen check (idempotence; generated files must be byte-identical):
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
regeneration can never re-commit a Telegram session string. Only a sha256
prefix is ever printed. Set VERBATIM_MIN_RUN to change the threshold (200).

The coverage ledger (.brain/verbatim-coverage.md) is curated — not regenerated.
"""

import hashlib
import json
import collections
import os
import re
import sqlite3
import sys
from datetime import datetime, timezone

DB = os.environ.get("VERBATIM_DB", "/home/me/.local/share/opencode/opencode.db")
ROOT = os.environ.get("VERBATIM_ROOT", "/home/me/Documents/projects/teleframe")
VDIR = os.environ.get("VERBATIM_OUT", f"{ROOT}/.brain/verbatim")

# The 4 committed teleframe dirs + the teleframe-origin sessions recorded in
# the coverage ledger §1 (origin sessions live under projects/tele and /home/me;
# those dirs are NOT included wholesale — only these explicit session ids).
DIRS = (
    "/home/me/Documents/projects/teleframe",
    "/home/me/Documents/projects/teleframe-psr3",
    "/home/me/Documents/projects/teleframe-automation-6-run-47",
    "/home/me/Documents/projects/teleframe-automation-7-run-48",
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
# session id, timestamp and first line, and the run prints the count. Nothing
# the user said can disappear quietly — a false positive is visible in that
# review file on the very next run.
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


def exclusion_reason(txt, is_dispatch_session=False):
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
    return None


con = sqlite3.connect(f"file:{DB}?mode=ro", uri=True)
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
for sid, agent, title, mt, data in con.execute(
    f"""
    SELECT s.id, s.agent, s.title, p.time_created, p.data
    FROM part p
    JOIN message m ON m.id = p.message_id
    JOIN session s ON s.id = m.session_id
    WHERE ( s.directory IN ({dir_placeholders})
            OR s.id IN ({",".join("?" * len(ORIGIN_SESSIONS))}) )
      AND json_extract(p.data,'$.type') = 'text'
      AND json_extract(m.data,'$.role') = 'user'
      AND COALESCE(json_extract(p.data,'$.synthetic'),0) != 1
      AND s.parent_id IS NULL
      AND s.agent IN ({agent_placeholders})
    ORDER BY p.time_created
    """,
    (*DIRS, *ORIGIN_SESSIONS, *MAIN_AGENTS),
):
    txt = ""
    try:
        txt = json.loads(data).get("text", "")
    except Exception:
        continue
    if not txt or not txt.strip():
        continue
    reason = exclusion_reason(txt, is_dispatch_session=parts_per_session.get(sid, 0) <= 1)
    if reason:
        dropped.append(
            dict(
                sid=sid,
                mt=int(mt),
                reason=reason,
                first=txt.strip().split("\n")[0][:110],
            )
        )
        continue
    rows.append(
        dict(sid=sid, agent=agent, title=title, mt=int(mt), txt=redact(txt, sid))
    )
con.close()


# Every dropped part is recorded, never silently discarded — see the block
# comment above AGENT_PROMPT_MARKERS. A false positive is a visible line here.
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
        "",
        "| Reason | Parts |",
        "|---|---|",
    ]
    for reason, n in sorted(by_reason.items(), key=lambda kv: -kv[1]):
        L.append(f"| `{reason}` | {n} |")
    L += ["", f"Total dropped: **{len(dropped)}** parts.", "", "## Dropped parts", ""]
    for d in sorted(dropped, key=lambda d: d["mt"]):
        when = datetime.fromtimestamp(d["mt"] / 1000, timezone.utc).strftime(
            "%Y-%m-%d %H:%M:%S UTC"
        )
        L.append(f"- `{when}` `{d['sid']}` — {d['reason']} — `{d['first']}`")
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
            created=datetime.fromtimestamp(r["mt"] / 1000, timezone.utc),
        )


# fork detection: identical full text-set -> same canonical (dedup join)
def sig(sid):
    return tuple(sorted(r["txt"] for r in sess_rows[sid]))


seen = {}
forks = collections.defaultdict(list)
canonical = set()
for sid in sorted(sess_rows, key=lambda s: session_meta[s]["created"]):
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
for d, rs in sorted(by_date.items()):
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
        L.append(f"## Session: {meta['title']}")
        L.append(f"- opencode session id: `{sid}`")
        L.append(
            f"- agent: `{meta['agent']}` · created: {meta['created'].strftime('%Y-%m-%d %H:%M:%S')} UTC"
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
