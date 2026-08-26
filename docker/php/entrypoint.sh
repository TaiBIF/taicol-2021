#!/bin/sh
set -e

# 讀取 bind mount 目錄實際的 UID/GID(即主機端擁有者)
TARGET_UID=$(stat -c '%u' /var/www/html)
TARGET_GID=$(stat -c '%g' /var/www/html)

# 對齊容器內 www-data 的 UID/GID,bind mount 才不會有權限落差
if [ "$TARGET_GID" != "$(id -g www-data)" ]; then
    groupmod -o -g "$TARGET_GID" www-data
fi
if [ "$TARGET_UID" != "$(id -u www-data)" ]; then
    usermod -o -u "$TARGET_UID" -g "$TARGET_GID" www-data
fi

# 掛載生效後才建立需要寫入的目錄(主機端若缺少也能補上)
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/images/references \
    public/pdfs/references \
    public/usage_results \
    public/import

chown -R www-data:www-data \
    storage bootstrap/cache \
    public/images public/pdfs public/usage_results public/import 2>/dev/null || true

# 降權執行:
# - php-fpm 需以 root 啟動 master,再由它自己 fork www-data worker
# - 其餘命令(如 queue:work)才直接以 www-data 執行
if [ "$1" = "php-fpm" ]; then
    exec "$@"
else
    exec su-exec www-data "$@"
fi