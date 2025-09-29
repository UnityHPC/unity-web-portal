#!/bin/bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

# --color=never because magit git output log doesn't support it
if grep -H --color=never --line-number -P '\bjson_encode\b' "$@"; then
    echo "json_encode() is not allowed! use \jsonEncode() instead."
    exit 1
fi
