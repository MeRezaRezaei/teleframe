#!/usr/bin/env python3
"""Split an Infection text log into one work tree per source file.

WHY THIS EXISTS
---------------
A mutation gate answers "is this suite strong enough to kill the mutants of the
files it owns", and the answer is per FILE, not per slice: one file can be
killed at 56% while its neighbour in the same slice sits at 11%. A slice-level
number hides that, and the repair work ("write the test that kills this
mutant") is per file.

So this tool reads Infection's TEXT log and writes one markdown work tree per
source file, each listing that file's escaped mutants with the line, the
mutator name and the diff that survived. Those work trees are the input a
kill-test lane is dispatched from — one lane per file, so the lanes do not
touch each other's files.

MEASURED FORMAT (Infection 0.35.8)
----------------------------------
    Escaped mutants:
    ================

    1) /abs/path/File.php:28    [M] Coalesce [ID] 47a75701d84f9dd00e39e40b33bb0d76

    @@ @@
         $a = 1;
    -    $b = 2;
    +    $b = 3;

The status letter in the brackets is Infection's own legend: M escaped, T timed
out, E errored, X syntax error, U not covered, S skipped, I ignored.

USAGE
-----
    bin/mutation-escaped-by-file.py infection/schema-ingest-infection.log \
        --source src/Schema --out /tmp/mutation-worktree/schema-ingest

Prints a per-file summary to stdout and writes <out>/<relative path>.md.
"""

from __future__ import annotations

import argparse
import os
import re
import sys

MUTANT_HEADER = re.compile(
    r"^(?P<index>\d+)\) (?P<file>\S+):(?P<line>\d+)\s+\[(?P<status>[A-Z])\]"
    r"\s+(?P<mutator>.+?)\s+\[ID\]\s+(?P<id>[0-9a-f]+)\s*$"
)

SECTION = re.compile(r"^(?P<name>[A-Z][A-Za-z ]+) mutants:$")

# Infection's own legend, spelled out so a work tree never leaves the reader
# guessing what a letter means.
LEGEND = {
    "M": "escaped (survived the suite)",
    "T": "timed out",
    "E": "errored",
    "X": "syntax error",
    "U": "not covered",
    "S": "skipped",
    "I": "ignored",
    "A": "killed by static analysis",
}


def parse(path: str) -> dict[str, list[dict]]:
    """Return {source file: [mutant dicts]} for every non-killed mutant."""
    per_file: dict[str, list[dict]] = {}
    section = ""
    current: dict | None = None
    diff_lines: list[str] = []

    def flush() -> None:
        if current is None:
            return
        current["diff"] = "\n".join(diff_lines).strip("\n")
        per_file.setdefault(current["file"], []).append(current)

    with open(path, "r", encoding="utf-8", errors="replace") as handle:
        for raw in handle:
            line = raw.rstrip("\n")

            match = SECTION.match(line)
            if match:
                flush()
                current = None
                diff_lines = []
                section = match.group("name").strip()
                continue

            header = MUTANT_HEADER.match(line)
            if header:
                flush()
                diff_lines = []
                current = {
                    "index": int(header.group("index")),
                    "file": header.group("file"),
                    "line": int(header.group("line")),
                    "status": header.group("status"),
                    "mutator": header.group("mutator").strip(),
                    "id": header.group("id"),
                    "section": section,
                }
                continue

            if current is not None:
                diff_lines.append(line)

    flush()
    return per_file


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("log", help="Infection text log")
    parser.add_argument(
        "--source",
        default="src",
        help="source root the mutated files live under (default: src)",
    )
    parser.add_argument(
        "--out",
        required=True,
        help="directory the per-file work trees are written to",
    )
    parser.add_argument(
        "--only",
        default="",
        help="comma separated file names to keep, relative to --source",
    )
    args = parser.parse_args()

    if not os.path.isfile(args.log):
        print(f"no such log: {args.log}", file=sys.stderr)
        return 2

    per_file = parse(args.log)
    source_root = os.path.abspath(args.source)
    keep = {name.strip() for name in args.only.split(",") if name.strip()}

    os.makedirs(args.out, exist_ok=True)

    total = 0
    for source_file in sorted(per_file):
        absolute = os.path.abspath(source_file)
        if not absolute.startswith(source_root + os.sep):
            continue
        relative = os.path.relpath(absolute, source_root)
        if keep and relative not in keep:
            continue

        mutants = per_file[source_file]
        total += len(mutants)
        target = os.path.join(args.out, relative + ".md")
        os.makedirs(os.path.dirname(target), exist_ok=True)

        by_status: dict[str, int] = {}
        for mutant in mutants:
            by_status[mutant["status"]] = by_status.get(mutant["status"], 0) + 1

        with open(target, "w", encoding="utf-8") as handle:
            handle.write(f"# {relative} — {len(mutants)} surviving mutants\n\n")
            handle.write(
                "statuses: "
                + ", ".join(
                    f"{status}={count} ({LEGEND.get(status, 'unknown')})"
                    for status, count in sorted(by_status.items())
                )
                + "\n\n"
            )
            handle.write(
                "Each mutant below SURVIVED the slice's suite. Write one assertion "
                "that fails when the mutation is present.\n\n"
            )
            for mutant in mutants:
                handle.write(
                    f"## {mutant['index']}) line {mutant['line']} "
                    f"[{mutant['status']}] {mutant['mutator']} "
                    f"(id {mutant['id']})\n\n"
                )
                handle.write("```diff\n")
                handle.write(mutant["diff"])
                handle.write("\n```\n\n")

        print(f"{relative}: {len(mutants)} surviving ({by_status})")

    print(f"total surviving mutants written: {total}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
