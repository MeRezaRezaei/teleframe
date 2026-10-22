#!/usr/bin/env python3
"""Export every delegated subagent session to a durable ledger.

WHY THIS EXISTS, and why it is NOT part of bin/build-verbatim-teleframe.py:

The verbatim archive has one contract, stated in that script's docstring:
it stores the USER's own words, byte-for-byte, from main-agent sessions. That
contract is load-bearing. A dispatch prompt is also a role=user text part, so
folding subagent sessions into the archive would file the agent's prose in the
user's mouth, and the whole goal-driven cycle treats the archive as the source
of meaning. Mixing them would corrupt the one artifact everything trusts.

But excluding them means the engineering evidence is thrown away. Every audit,
scorecard and gate receipt this project produced was written by a subagent: the
Version One capability scores, the security review, the fresh-install proof, the
Postgres data-loss audit. None of it is in the verbatim archive, and all of it is
needed later -- to re-score a capability, to justify a decision, or to recover
what a crashed session had found.

So the two are kept apart on purpose:
  docs/verbatim/      the user's words, never the agent's
  docs/subagents/     the agent's work, clearly labelled as such

This exporter writes the second. It is read-only against the database and
idempotent: two runs over an unchanged database produce byte-identical output.

The database is opened read-only via URI so a concurrent opencode process can
never be locked out or corrupted by this script.
"""

from __future__ import annotations

import json
import os
import sqlite3
import sys
from datetime import datetime, timezone

DB = os.environ.get("VERBATIM_DB", "/home/me/.local/share/opencode/opencode.db")
OUT_DIR = os.environ.get("SUBAGENT_OUT", "docs/subagents")

# SCOPE: this ledger belongs to THIS repository and nothing else.
#
# The opencode database is shared by every project on the machine — one file,
# thousands of sessions from unrelated codebases. Unscoped, the first run of
# this exporter swept up 3351 delegated sessions of which only a fraction were
# ever about this package. The verbatim generator already scopes itself the same
# way (its SQL filters `directory IN (...)`); this exporter must not be looser
# than the archive it sits beside.
#
# Override with SUBAGENT_DIR only when deliberately exporting another project.
REPO_DIR = os.environ.get("SUBAGENT_DIR") or os.path.abspath(
    os.path.join(os.path.dirname(os.path.abspath(__file__)), os.pardir)
)


def iso(ms: int | None) -> str:
    """Render a millisecond epoch as an ISO-8601 UTC stamp."""
    if not ms:
        return ""
    return datetime.fromtimestamp(ms / 1000, tz=timezone.utc).strftime(
        "%Y-%m-%dT%H:%M:%SZ"
    )


def text_of(con: sqlite3.Connection, message_id: str) -> str:
    """Concatenate the plain-text parts of one message, in order.

    Only `type == "text"` parts count. Tool calls, tool results, reasoning and
    synthetic control-plane text are deliberately excluded: this ledger records
    what an agent REPORTED, not every token it spent.
    """
    rows = con.execute(
        "SELECT data FROM part WHERE message_id = ? ORDER BY time_created",
        (message_id,),
    ).fetchall()
    chunks: list[str] = []
    for (raw,) in rows:
        try:
            data = json.loads(raw)
        except (TypeError, ValueError):
            continue
        if data.get("type") != "text":
            continue
        if data.get("synthetic"):
            continue
        text = data.get("text")
        if isinstance(text, str) and text.strip():
            chunks.append(text)
    return "\n".join(chunks)


def main() -> int:
    if not os.path.exists(DB):
        print(f"subagent-ledger: database not found at {DB}", file=sys.stderr)
        return 1

    os.makedirs(OUT_DIR, exist_ok=True)
    con = sqlite3.connect(f"file:{DB}?mode=ro", uri=True)

    # A delegated session is one with a parent. Everything else is a main-agent
    # lane, and main-agent user text belongs to the verbatim archive instead.
    # Scoped to this repository's directory: see REPO_DIR above for why.
    sessions = con.execute(
        """
        SELECT id, parent_id, title, agent, model,
               time_created, time_updated,
               tokens_input, tokens_output, tokens_reasoning, cost
          FROM session
         WHERE parent_id IS NOT NULL
           AND directory = ?
      ORDER BY time_created, id
        """,
        (REPO_DIR,),
    ).fetchall()

    by_id = {
        row[0]: {
            "parent": row[1],
            "title": row[2] or "",
            "agent": row[3] or "",
            "model": row[4] or "",
            "created": row[5],
            "updated": row[6],
            "tin": row[7] or 0,
            "tout": row[8] or 0,
            "treason": row[9] or 0,
            "cost": row[10] or 0,
            "messages": 0,
            "final": "",
        }
        for row in sessions
    }

    # Attach message counts and the LAST assistant text as the report.
    for sid in by_id:
        n = con.execute(
            "SELECT COUNT(*) FROM message WHERE session_id = ?", (sid,)
        ).fetchone()[0]
        by_id[sid]["messages"] = n
        last = con.execute(
            """
            SELECT id FROM message
             WHERE session_id = ?
               AND json_extract(data, '$.role') = 'assistant'
          ORDER BY time_created DESC
             LIMIT 1
            """,
            (sid,),
        ).fetchone()
        if last:
            by_id[sid]["final"] = text_of(con, last[0])

    index = [
        "# Subagent ledger",
        "",
        "> Generated by `bin/export-subagent-ledger.py` from the opencode",
        "> database (read-only). **These are the AGENT's words, not the user's.**",
        "> The user's own words live in `docs/verbatim/` and must never be mixed",
        "> with this file: the goal-driven cycle treats the verbatim archive as the",
        "> source of meaning, and agent prose filed there would corrupt it.",
        ">",
        "> Kept because this is where the engineering evidence lives — every",
        "> capability scorecard, audit and gate receipt was written by a subagent,",
        "> and none of it is in the verbatim archive.",
        "",
        f"> Delegated sessions: **{len(by_id)}** (scoped to `{REPO_DIR}`)",
        "",
        "| Session | Agent | Started (UTC) | Msgs | Tokens in/out | Report? |",
        "|---|---|---|---|---|---|",
    ]
    for sid, s in by_id.items():
        has_report = "yes" if s["final"].strip() else "no"
        index.append(
            f"| `{sid}` | {s['agent'] or '?'} | {iso(s['created'])} | "
            f"{s['messages']} | {s['tin']}/{s['tout']} | {has_report} |"
        )

    index_path = os.path.join(OUT_DIR, "INDEX.md")
    with open(index_path, "w", encoding="utf-8") as fh:
        fh.write("\n".join(index) + "\n")

    # One file per session that actually produced a report. A session with no
    # assistant text carries no finding worth keeping.
    written = 0
    for sid, s in by_id.items():
        report = s["final"].strip()
        if not report:
            continue
        path = os.path.join(OUT_DIR, f"{sid}.md")
        with open(path, "w", encoding="utf-8") as fh:
            fh.write(f"# Subagent report `{sid}`\n\n")
            fh.write(f"- agent: `{s['agent'] or '?'}`\n")
            fh.write(f"- model: `{s['model'] or '?'}`\n")
            fh.write(f"- parent session: `{s['parent']}`\n")
            fh.write(f"- title: {s['title'] or '(untitled)'}\n")
            fh.write(f"- started: {iso(s['created'])}\n")
            fh.write(f"- messages: {s['messages']}\n")
            fh.write(
                f"- tokens in/out/reasoning: {s['tin']}/{s['tout']}/{s['treason']}\n"
            )
            fh.write("\n---\n\n")
            fh.write(report + "\n")
        written += 1

    print(
        f"subagent-ledger: {len(by_id)} delegated sessions, "
        f"{written} with a report -> {OUT_DIR}/"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
