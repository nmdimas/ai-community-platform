#!/usr/bin/env bash
set -euo pipefail

echo "==> Installing OpenCode plugins..."
if [ -f .opencode/package.json ]; then
  cd .opencode && bun install && cd ..
fi

echo "==> Installing PHP project dependencies..."
if [ -f composer.json ] && command -v composer &>/dev/null; then
  composer install --no-interaction --prefer-dist || true
fi

echo "==> Done! All tools installed."
echo "  - Claude Code: $(claude --version 2>/dev/null || echo 'N/A')"
echo "  - OpenCode:    $(opencode --version 2>/dev/null || echo 'N/A')"
echo "  - Node:        $(node --version)"
echo "  - PHP:         $(php --version | head -1)"
echo "  - TypeScript:  $(tsc --version)"
echo "  - Go:          $(go version)"
echo "  - Docker:      $(docker --version 2>/dev/null || echo 'N/A')"
echo "  - Bun:         $(bun --version)"
