#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
output="${1:?Usage: package.sh /absolute/output/guardlms-totara-0.1.0.zip}"
[[ "$output" == /* ]] || { echo 'Use an absolute output path.' >&2; exit 1; }
[[ ! -e "$output" ]] || { echo 'Output already exists; choose a new path.' >&2; exit 1; }
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
mkdir -p "$stage/guardlms" "$(dirname "$output")"
for item in callback.php classes cli db index.php lang lib.php settings.php version.php LICENSE docs; do
    cp -R "$root/$item" "$stage/guardlms/"
done
cp "$root/README.md" "$stage/guardlms/README.md"
(cd "$stage" && zip -qr "$output" guardlms)
printf 'Packaged %s\n' "$output"
