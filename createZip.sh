#!/usr/bin/env bash
set -euo pipefail

echo "Create zip for plugin"

rm -rf download/simple-jwt-login/
cp -R simple-jwt-login download/

cd download/
# zip -r updates an existing archive and keeps files deleted from the plugin: start from scratch.
rm -f simple-jwt-login.zip
zip -r simple-jwt-login.zip simple-jwt-login

# Cleanup after creating the zip
rm -rf simple-jwt-login/

echo "done."