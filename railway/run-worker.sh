#!/usr/bin/env bash
# Queue worker for Railway Worker service.
# Make executable: chmod +x railway/run-worker.sh
set -euo pipefail

# Process channel webhooks first, then default jobs (mail, reminders, alerts).
exec php artisan queue:work database \
  --queue="${QUEUE_QUEUES:-channels,default}" \
  --sleep="${QUEUE_SLEEP:-1}" \
  --tries="${QUEUE_TRIES:-3}" \
  --timeout="${QUEUE_TIMEOUT:-90}" \
  --max-time="${QUEUE_MAX_TIME:-3600}"
