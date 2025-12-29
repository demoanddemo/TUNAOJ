#!/usr/bin/env bash
set -euo pipefail

# Export repository as a zip archive.
# Usage: scripts/archive_repo.sh [output_path]

out_path="${1:-tuna-oj-source.zip}"

# Ensure we are at repository root
repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"

if git diff --quiet --ignore-submodules HEAD --; then
  echo "Archiving current HEAD..."
else
  echo "Warning: there are uncommitted changes. They will be included in the archive." >&2
fi

git archive --format=zip -o "$out_path" HEAD

abs_path="$(realpath "$out_path")"
echo "Archive created: $abs_path"
