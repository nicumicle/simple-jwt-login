#!/usr/bin/env bash
# Installs and activates the WordPress MCP Adapter plugin in the local Docker
# WordPress container, so AI clients (e.g. Claude Code) can call the
# Simple JWT Login abilities over MCP.
#
# Usage (from the host): ./docker/install-mcp-adapter.sh [version]
set -euo pipefail

VERSION="${1:-v0.7.1}"
CONTAINER="${CONTAINER:-wordpress}"
ZIP_URL="https://github.com/WordPress/mcp-adapter/releases/download/${VERSION}/mcp-adapter.zip"

echo "Installing MCP Adapter ${VERSION} in container '${CONTAINER}'..."
docker exec "${CONTAINER}" wp plugin install "${ZIP_URL}" --activate --force --allow-root --path=/var/www/html

echo
echo "Done. Enable 'AI access' in Simple JWT Login > General, then connect an MCP client, e.g.:"
echo "  claude mcp add simple-jwt-login -- docker exec -i ${CONTAINER} wp mcp-adapter serve --server=mcp-adapter-default-server --user=admin --allow-root --path=/var/www/html"
