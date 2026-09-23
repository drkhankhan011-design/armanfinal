#!/bin/bash
# cPanel Deployment Script
# Build the React frontend and deploy it without deleting the PHP API.
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd -- "$SCRIPT_DIR/../.." && pwd)"
FRONTEND_DIR="$SCRIPT_DIR/src/frontend"
PUBLIC_HTML="${CPANEL_PUBLIC_HTML:-$HOME/public_html}"
BACKUP_DIR=""

cleanup() {
    local exit_code=$?
    if [[ "$exit_code" -ne 0 && -n "$BACKUP_DIR" && -d "$BACKUP_DIR" ]]; then
        echo "Deployment failed; restoring frontend backup..."
        rm -rf "$PUBLIC_HTML/assets" "$PUBLIC_HTML/index.html" "$PUBLIC_HTML/.htaccess"
        cp -a "$BACKUP_DIR/." "$PUBLIC_HTML/"
        rm -rf "$BACKUP_DIR"
    elif [[ -n "$BACKUP_DIR" && -d "$BACKUP_DIR" ]]; then
        rm -rf "$BACKUP_DIR"
    fi
    exit "$exit_code"
}
trap cleanup EXIT

command -v node >/dev/null 2>&1 || { echo "Node.js is required" >&2; exit 1; }
command -v npm >/dev/null 2>&1 || { echo "npm is required" >&2; exit 1; }

node_version="$(node --version)"
echo "Using Node.js $node_version"
mkdir -p "$PUBLIC_HTML"

# Back up only frontend files. The API directory is deliberately preserved.
BACKUP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/arman-cpanel-backup.XXXXXX")"
for item in index.html .htaccess assets; do
    if [[ -e "$PUBLIC_HTML/$item" ]]; then
        cp -a "$PUBLIC_HTML/$item" "$BACKUP_DIR/"
    fi
done

# Install from the existing lockfile when available, then build the frontend.
cd "$FRONTEND_DIR"
if [[ -f package-lock.json ]]; then
    npm ci
else
    npm install
fi
npm run build

# Deploy only the generated frontend. Do not remove public_html/api.
cp -a dist/. "$PUBLIC_HTML/"
cp -a "$SCRIPT_DIR/.htaccess" "$PUBLIC_HTML/.htaccess"

# On a first deployment, populate the API from the repository if it is absent.
if [[ ! -d "$PUBLIC_HTML/api" && -d "$REPO_ROOT/public_html/api" ]]; then
    cp -a "$REPO_ROOT/public_html/api" "$PUBLIC_HTML/api"
fi

# Ensure PHP API configuration exists without overwriting an existing cPanel config.
for file in config.php env.json; do
    if [[ ! -e "$PUBLIC_HTML/$file" && -e "$REPO_ROOT/public_html/$file" ]]; then
        cp -a "$REPO_ROOT/public_html/$file" "$PUBLIC_HTML/$file"
    fi
done

find "$PUBLIC_HTML" -type f -exec chmod 644 {} +
find "$PUBLIC_HTML" -type d -exec chmod 755 {} +

echo "Deployment completed successfully: $PUBLIC_HTML"
echo "The existing public_html/api directory was preserved."
