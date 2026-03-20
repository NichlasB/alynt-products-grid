#!/bin/bash
set -e

REMOTE_HOST="your-ssh-alias"
REMOTE_PATH="/var/www/your-site/htdocs/wp-content/plugins/alynt-products-grid"

echo "Deploying alynt-products-grid to ${REMOTE_HOST}:${REMOTE_PATH}"
rsync -avz --delete \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='tests' \
  --exclude='coverage' \
  --exclude='.env' \
  --exclude='composer.phar' \
  --exclude='*.map' \
  ./ "${REMOTE_HOST}:${REMOTE_PATH}/"

echo "Deployment complete."
