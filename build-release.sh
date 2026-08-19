#!/usr/bin/env bash

set -euo pipefail

project_dir="$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)"
version="$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$project_dir/telegram-bot.php" | head -n 1)"

if [[ -z "$version" ]]; then
	echo "Could not read the plugin version from telegram-bot.php." >&2
	exit 1
fi

plugin_slug="telegram-bot-trigger-notifications"
stage_dir="$(mktemp -d "${TMPDIR:-/tmp}/${plugin_slug}-release.XXXXXX")"
package_dir="$stage_dir/$plugin_slug"
archive_name="${plugin_slug}-v${version}.zip"

cleanup() {
	rm -rf "$stage_dir"
}

trap cleanup EXIT

mkdir -p "$package_dir"

for item in telegram-bot.php uninstall.php readme.txt README.md admin assets includes modules; do
	cp -R "$project_dir/$item" "$package_dir/"
done

(
	cd "$stage_dir"
	zip -qr "$archive_name" "$plugin_slug"
)

mv -f "$stage_dir/$archive_name" "$project_dir/$archive_name"

echo "Created $project_dir/$archive_name"
