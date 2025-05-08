#!/bin/bash
#
# Quick and dirty local test runner.
# Requires docker, python 3.13, and python3-venv.
#
# Usage:
#   ./run_test.sh [version]
#
# Examples:
#   ./run_test.sh
#   ./run_test.sh 1.42

# Set working directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ORIGINAL_DIR="$(pwd)"
VENV_DIR=".venv"

cd "$SCRIPT_DIR/../.."

# Register cleanup
cleanup() {
  echo -e "\n\n==> Stopping Docker containers"
  docker stop mediawiki database >/dev/null 2>&1 || true
  docker rm mediawiki database >/dev/null 2>&1 || true
  docker network rm mw-net >/dev/null 2>&1 || true
  echo "OK, bye!"
}

trap cleanup EXIT

# Choose MediaWiki version (default to 1.42)
MW_VERSION="${1:-1.42}"
echo -e "\n\n==> Using MediaWiki version: $MW_VERSION"

# Setup virtual env
if [ ! -d "$VENV_DIR" ]; then
    python3 -m venv "$VENV_DIR"
    echo "Virtual environment created at $VENV_DIR"
fi

source "$VENV_DIR/bin/activate"
pip install -r requirements.txt || exit 1

# Start Docker containers
echo -e "\n\n==> Starting Docker containers"
docker network create mw-net || true

docker run -d --name database \
  --network "mw-net" \
  -e MYSQL_DATABASE=mediawiki \
  -e MYSQL_USER=mediawiki \
  -e MYSQL_PASSWORD=mediawiki1234 \
  -e MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=yes \
  -p 3306:3306 \
  mariadb:11.4

sleep 5

docker run -d --name mediawiki \
  --network "mw-net" \
  -p 8080:80 \
  -v "$(pwd):/var/www/html/extensions/Attachments" \
  "mediawiki:$MW_VERSION"

sleep 5

# Configure MediaWiki
echo -e "\n\n==> Configuring MediaWiki"
cat tests/scripts/prepare.sh | docker exec -i mediawiki bash

# Run tests
echo -e "\n\n==> Running tests"
pytest tests/pytest
TEST_EXIT_CODE=$?

# Cleanup
echo
read -n 1 -s -r -p "Press any key to stop containers..."

exit $TEST_EXIT_CODE