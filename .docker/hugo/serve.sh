#!/bin/sh
set -e

exec hugo server \
  --source /src/docs \
  --bind 0.0.0.0 \
  --port 1313 \
  --baseURL http://localhost:1313/ \
  --watch
