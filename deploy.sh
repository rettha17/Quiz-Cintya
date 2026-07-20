#!/usr/bin/env bash
# Deploy project ini ke Hostinger lewat SSH.
# Pemakaian: bash deploy.sh
set -euo pipefail

cd "$(dirname "$0")"

if [ ! -f deploy.env ]; then
  echo "File deploy.env tidak ditemukan."
  echo "Salin deploy.env.example jadi deploy.env lalu isi datanya, baru jalankan lagi."
  exit 1
fi

# shellcheck disable=SC1091
source deploy.env

: "${SSH_HOST:?SSH_HOST belum diisi di deploy.env}"
: "${SSH_PORT:=22}"
: "${SSH_USER:?SSH_USER belum diisi di deploy.env}"
: "${REMOTE_PATH:?REMOTE_PATH belum diisi di deploy.env}"
: "${SSH_KEY:=~/.ssh/hostinger_quizbps}"

SSH_KEY="${SSH_KEY/#\~/$HOME}"
SSH_OPTS=(-i "$SSH_KEY" -p "$SSH_PORT" -o StrictHostKeyChecking=accept-new)

ARCHIVE="deploy_$(date +%Y%m%d%H%M%S).tar.gz"
REMOTE_TMP="~/${ARCHIVE}"

echo "==> Membuat arsip project (mengecualikan file yang tidak perlu di-deploy)..."
tar czf "$ARCHIVE" \
  --exclude=".git" \
  --exclude=".claude" \
  --exclude="deploy.env" \
  --exclude="deploy.env.example" \
  --exclude="deploy.sh" \
  --exclude="koneksi.php" \
  --exclude="*.sql" \
  --exclude="node_modules" \
  .

echo "==> Mengirim arsip ke server (${SSH_HOST}:${REMOTE_PATH})..."
scp "${SSH_OPTS[@]}" "$ARCHIVE" "${SSH_USER}@${SSH_HOST}:${REMOTE_TMP}"

echo "==> Mengekstrak arsip di server..."
ssh "${SSH_OPTS[@]}" "${SSH_USER}@${SSH_HOST}" "mkdir -p '${REMOTE_PATH}' && tar xzf '${REMOTE_TMP}' -C '${REMOTE_PATH}' && rm -f '${REMOTE_TMP}'"

rm -f "$ARCHIVE"

echo "==> Selesai. Perubahan sudah live di server."
echo "    Catatan: koneksi.php TIDAK ikut di-deploy supaya kredensial database produksi di server tidak tertimpa."
