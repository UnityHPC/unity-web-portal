#!/usr/bin/env bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

err_funcs=(badRequest forbidden internalServerError)

rc=0
for err_func in "${err_funcs[@]}"; do
    # --color=never because magit git output log doesn't support it
    if grep -H --color=never --line-number -P '\b'"$err_func"'\s*[\(;]' "$@"; then
        echo "$err_func() is not allowed! use an exceptioninstead."
        rc=1
    fi
done
exit "$rc"
