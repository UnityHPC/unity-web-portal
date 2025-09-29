#!/usr/bin/env bash
set -euo pipefail
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

declare -A utils=(
    ["assert"]="ensure"
    ["json_encode"]="jsonEncode"
    ["mb_detect_encoding"]="mbDetectEncoding"
    ["mb_convert_encoding"]="mbConvertEncoding"
)

rc=0
for replaced in "${!utils[@]}"; do
    replacement="${utils[$replaced]}"
    # --color=never because magit git output log doesn't support it
    if grep -H --color=never --line-number -P '\b'"$replaced"'\s*[\(;]' "$@"; then
        echo "$replaced() is not allowed! use $replacement() instead."
        rc=1
    fi
done
exit "$rc"
