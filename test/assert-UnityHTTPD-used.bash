#!/bin/bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

rc=0

# --color=never because magit git output log doesn't support it
die_occurrences="$(
    grep -H --color=never --line-number -P '\bdie\s*[\(;]' "$@" | grep -v -P 'UnityHTTPD::die'
)" || true
if [ -n "$die_occurrences" ]; then
    echo "die is not allowed! use UnityHTTPD::die() instead."
    echo "$die_occurrences"
    rc=1
fi

# --color=never because magit git output log doesn't support it
exit_occurrences="$(grep -H --color=never --line-number -P '\bexit\s*[\(;]' "$@")" || true
if [ -n "$exit_occurrences" ]; then
    echo "exit is not allowed! use UnityHTTPD::die() instead."
    echo "$exit_occurrences"
    rc=1
fi

exit "$rc"
