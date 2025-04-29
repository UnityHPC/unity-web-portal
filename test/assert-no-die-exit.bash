#!/bin/bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

# --color=never because magit git output log doesn't support it
die_occurrences="$(grep -H --color=never --line-number -P '\bdie\s*\(' "$@" | grep -v -P 'UnitySite::die\s*\(')" || true
if [ -n "$die_occurrences" ]; then
    echo "die() is not allowed! use UnitySite::die() instead."
    echo "$die_occurrences"
    exit 1
fi

# --color=never because magit git output log doesn't support it
exit_occurrences="$(grep -H --color=never --line-number -P '\exit\s*\(' "$@")" || true
if [ -n "$exit_occurrences" ]; then
    echo "exit() is not allowed!"
    echo "$exit_occurrences"
    exit 1
fi
