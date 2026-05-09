#!/bin/bash

PORT="${PORT:-8080}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DATADIR="/tmp/pmapp-mysql"
MYSQL_SOCK="/tmp/pmapp-mysql.sock"

echo "[ProjectFlow] ======================================"
echo "[ProjectFlow] Mini Project Management App"
echo "[ProjectFlow] ======================================"

# ----- 1. Clean init MySQL every startup (ensures fresh state) -----
echo "[ProjectFlow] Initializing MySQL..."
rm -rf "$DATADIR"
mkdir -p "$DATADIR"
mysqld --initialize-insecure \
  --user="$(whoami)" \
  --datadir="$DATADIR" \
  2>&1

echo "[ProjectFlow] Starting MySQL..."

# Must run from DATADIR so InnoDB undo tablespace relative paths resolve
cd "$DATADIR"
mysqld \
  --user="$(whoami)" \
  --datadir="$DATADIR" \
  --socket="$MYSQL_SOCK" \
  --port=3306 \
  --pid-file="$DATADIR/mysqld.pid" \
  --log-error="$DATADIR/mysqld.log" \
  --mysqlx=OFF \
  2>/dev/null &
MYSQL_PID=$!

# Wait up to 20s for MySQL to accept connections
for i in $(seq 1 20); do
  if mysqladmin --socket="$MYSQL_SOCK" -u root ping --silent 2>/dev/null; then
    echo "[ProjectFlow] MySQL ready! (PID $MYSQL_PID)"
    break
  fi
  sleep 1
  echo "[ProjectFlow] Waiting for MySQL... ($i/20)"
done

# ----- 2. Import schema -----
echo "[ProjectFlow] Importing database schema..."
cd "$SCRIPT_DIR"
mysql --socket="$MYSQL_SOCK" -u root < "$SCRIPT_DIR/sql/schema.sql"
echo "[ProjectFlow] Database + seed data ready."

# ----- 3. Start PHP built-in server (foreground) -----
echo "[ProjectFlow] Starting PHP server on port $PORT..."
echo "[ProjectFlow] -------------------------------------------"
echo "[ProjectFlow] Demo admin:  admin@demo.com / password"
echo "[ProjectFlow] Demo member: alice@demo.com / password"
echo "[ProjectFlow] -------------------------------------------"
exec php -S "0.0.0.0:$PORT" -t "$SCRIPT_DIR" "$SCRIPT_DIR/router.php"
