set -euo pipefail
trap 's=$?; echo "$0: Error on line "$LINENO": $BASH_COMMAND"; exit $s' ERR
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

funcs=(die exit)
rc=0
for func in "${funcs[@]}"; do
    # --color=never because magit git output log doesn't support it
    grep_rc=0; grep -H --color=never --line-number -P '\b'"$func"'\s*[\(;]' "$@" | grep -v -E 'UnityHTTPD::'"$func" || grep_rc=$?
    case "$grep_rc" in
        0)
            echo "$func() are not allowed! use UnityHTTPD::die() instead."; rc=1 ;;
        1)
            : ;; # code is good, do nothing
        *)
            echo "grep failed!";  rc=1 ;;
    esac
done
exit "$rc"
