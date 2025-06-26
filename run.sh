#!/usr/bin/env bash
# Simple launcher for Xaxino Casino using gunicorn

set -euo pipefail

if [[ ! -f .env ]]; then
  cp .env.example .env
fi

exec gunicorn --bind 0.0.0.0:5000 main:app
