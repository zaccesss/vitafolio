#!/usr/bin/env python3
"""Decides whether Crowdin's pull request is safe to merge without a person looking at it.

It compares every translation file on main with the same file on the l10n_crowdin branch. The pull
request is unsafe when a key disappears or a real translation turns back into the english
source text, which happens when Crowdin has not yet received translations committed here.
Prints the problems and exits 1 when there are any, so the workflow leaves the pull request open.
"""

import json
import subprocess
import sys
from pathlib import Path

BASE, HEAD = sys.argv[1], sys.argv[2]


def load(ref: str, path: str) -> dict:
    shown = subprocess.run(["git", "show", f"{ref}:{path}"], capture_output=True, text=True)
    return json.loads(shown.stdout) if shown.returncode == 0 else {}


english = json.loads(Path("lang/en.json").read_text(encoding="utf-8"))
problems = []
for path in sorted(Path("lang").glob("*.json")):
    if path.name == "en.json":
        continue
    before, after = load(BASE, str(path)), load(HEAD, str(path))
    for key, value in before.items():
        if key not in after:
            problems.append(f"{path.name}: {key!r} was removed")
        elif value != english.get(key, key) and after[key] == english.get(key, key):
            problems.append(f"{path.name}: {key!r} went back to english")

for line in problems[:50]:
    print(line)
print(f"{len(problems)} problem(s)" if problems else "no translation is removed or reverted to english")
sys.exit(1 if problems else 0)
