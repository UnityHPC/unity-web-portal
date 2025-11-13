set -euo pipefail
trap 's=$?; echo "$0: Error on line "$LINENO": $BASH_COMMAND"; exit $s' ERR
if [[ $# -lt 1 ]]; then
    echo "at least one argument required"
    exit 1
fi

rc=0
# --color=never because magit git output log doesn't support it
grep_rc=0; grep -H --color=never --line-number -P '\bassertTrue\s*[\(;]' "$@" || grep_rc=$?
case "$grep_rc" in
    0)
        echo "assertTrue() is not allowed! use assert() instead."; rc=1 ;;
    1)
        : ;; # code is good, do nothing
    *)
        echo "grep failed!";  rc=1 ;;
esac
exit "$rc"
