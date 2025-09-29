#!/bin/bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

# --color=never because magit git output log doesn't support it
if grep -H --color=never --line-number -P '\bassert\s*[\(;]' "$@"; then
    echo "assert() is not allowed! use \ensure() instead."
    exit 1
fi
