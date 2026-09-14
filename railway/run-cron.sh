#!/usr/bin/env bash
# Scheduler loop for a dedicated cron/worker process.
# Make executable: chmod +x railway/run-cron.sh
set -euo pipefail

while true; do
  echo "Running the scheduler..."
  php artisan schedule:run --verbose --no-interaction &
  sleep 60
done
