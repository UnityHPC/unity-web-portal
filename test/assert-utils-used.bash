set -euo pipefail
trap 's=$?; echo "$0: Error on line "$LINENO": $BASH_COMMAND"; exit $s' ERR
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
    grep_rc=0; grep -H --color=never --line-number -P '\b'"$replaced"'\s*[\(;]' "$@" || grep_rc=$?
    case "$grep_rc" in
        0)
            echo "$replaced() are not allowed! use $replacement() instead."; rc=1 ;;
        1)
            : ;; # code is good, do nothing
        *)
            echo "grep failed!";  rc=1 ;;
    esac
done
exit "$rc"
