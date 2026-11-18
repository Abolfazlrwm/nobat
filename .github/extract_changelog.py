#!/usr/bin/env python3
"""Extract one version's section from CHANGELOG.md for GitHub Release notes.

Usage (used by .github/workflows/release.yml):
    python3 .github/extract_changelog.py 1.35.0 > release-notes.md

Prints everything between `## [<version>]` and the next `## [` heading.
Exits 1 with a clear message when the version has no section yet, so a
tag pushed against an unchangelogged commit fails loudly instead of
publishing an empty release.
"""
import re
import sys
from pathlib import Path


def main() -> int:
    if len(sys.argv) != 2:
        print('usage: extract_changelog.py <version>', file=sys.stderr)
        return 1

    version = sys.argv[1].lstrip('v')
    changelog = (Path(__file__).resolve().parent.parent / 'CHANGELOG.md').read_text(encoding='utf-8')

    match = re.search(rf'^## \[{re.escape(version)}\](?: - .*)?$', changelog, re.MULTILINE)
    if not match:
        print(f'CHANGELOG.md has no section for [{version}] — add one before tagging.', file=sys.stderr)
        return 1

    body = changelog[match.end():]
    next_section = re.search(r'^## \[', body, re.MULTILINE)
    if next_section:
        body = body[: next_section.start()]

    print(body.strip())
    return 0


if __name__ == '__main__':
    sys.exit(main())
