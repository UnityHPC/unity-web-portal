#!/bin/bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required" >&2
    exit 1
fi
occurrences="$(grep -H --line-number --color=always -P '\bdie\s*\(' "$@" | grep -v -P 'UnitySite::die\s*\(')" || true
if [ -n "$occurrences" ]; then
    echo "die() is not allowed! use UnitySite::die() instead." >&2
    echo "$occurrences"
    exit 1
fi
