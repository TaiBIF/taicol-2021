#!/usr/bin/env bash
# 前端 build + rsync 部署
# 用法：./deploy.sh stag | prod | all
set -euo pipefail

TARGET="${1:-}"
case "$TARGET" in
  stag|prod|all) ;;
  *) echo "用法：$0 stag | prod | all"; exit 1 ;;
esac

cd "$(dirname "$0")"

# ---- 從 .env 讀取部署目的地 ----
# 不直接 source .env，避免 Laravel .env 其他值造成 shell 語法錯誤
env_get() {
  { grep -E "^$1=" .env || true; } | tail -n1 | cut -d= -f2- | sed -E "s/^[\"']|[\"']$//g"
}
STAG_DEST="$(env_get DEPLOY_STAG_DEST)"
PROD_DEST="$(env_get DEPLOY_PROD_DEST)"

if [[ "$TARGET" != prod && -z "$STAG_DEST" ]]; then echo ".env 缺少 DEPLOY_STAG_DEST"; exit 1; fi
if [[ "$TARGET" != stag && -z "$PROD_DEST" ]]; then echo ".env 缺少 DEPLOY_PROD_DEST"; exit 1; fi

# ---- build ----
export NVM_DIR="${NVM_DIR:-$HOME/.nvm}"
# shellcheck disable=SC1091
source "$NVM_DIR/nvm.sh"
nvm use 16

echo "==> npm run prod"
npm run prod

# ---- deploy ----
# 只同步 build 產物，其餘 public/ 檔案由 git 控制
sync_assets() {
  echo "==> rsync 到 $1"
  rsync -av --no-perms --exclude='.DS_Store' \
    public/js public/css public/mix-manifest.json \
    "$1" -e ssh
}

confirm_prod() {
  read -r -p "確定要部署到正式站 ($PROD_DEST)？[y/N] " ans
  [[ "$ans" =~ ^[Yy]$ ]] || { echo "略過正式站"; return 1; }
}

case "$TARGET" in
  stag) sync_assets "$STAG_DEST" ;;
  prod) confirm_prod && sync_assets "$PROD_DEST" ;;
  all)  sync_assets "$STAG_DEST"; confirm_prod && sync_assets "$PROD_DEST" ;;
esac

echo "==> 完成"