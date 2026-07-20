#!/usr/bin/env bash
# Deploy project ini ke Hostinger lewat SSH + git pull.
# Alur: push perubahan ke GitHub dulu (git push), baru jalankan: bash deploy.sh
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
SSH_OPTS=(-i "$SSH_KEY" -p "$SSH_PORT" -o BatchMode=yes -o StrictHostKeyChecking=accept-new)

echo "==> Menyinkronkan branch main lokal ke GitHub..."
git push origin main

echo "==> Menarik perubahan terbaru di server (${SSH_HOST}:${REMOTE_PATH})..."
ssh "${SSH_OPTS[@]}" "${SSH_USER}@${SSH_HOST}" "cd '${REMOTE_PATH}' && git fetch origin main && git reset --hard origin/main"

echo "==> Selesai. Perubahan sudah live di server."
echo "    Catatan: koneksi.php di server dikunci (skip-worktree) jadi tidak ikut tertimpa oleh git reset."
