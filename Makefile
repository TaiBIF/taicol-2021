#SHELL := bash
#.ONESHELL:
#.SHELLFLAGS := -eu -o pipefail -c
#.DELETE_ON_ERROR:
#MAKEFLAGS += --warn-undefined-variables
#MAKEFLAGS += --no-builtin-rules

# 開發容器前綴可由 .env 的 DEV_PREFIX 覆寫（升級用 worktree 與原本開發環境並存）
DEV_PREFIX  := $(or $(shell grep -E '^DEV_PREFIX=' .env 2>/dev/null | cut -d= -f2 | tr -d '"'),taicol-nametool-dev)
DEV_APP     := $(DEV_PREFIX)-app
DEV_WORKER  := $(DEV_PREFIX)-worker
STAG_APP    := taicol-nametool-stag-app
STAG_WORKER := taicol-nametool-stag-worker
UPG_APP     := taicol-nametool-upg-app
PROD_APP    := taicol-nametool-app
PROD_WORKER := taicol-nametool-worker

.PHONY: dev-up dev-down dev-build stag-build stag-up stag-down stag-reload upg-build upg-up upg-down upg-reload prod-build prod-up prod-down prod-reload

# 換了 PHP／Blade／routes 或跑完 ./deploy.sh 之後，讓變更生效（不重建、不重啟 app）
# 參數：$(1) app 容器
# worker 用 queue:restart：做完目前的 job 才結束（restart: always 會自動再啟動、載入新程式碼），
# 不會中斷正在跑的匯入；要放在 optimize:clear 之後（它會清掉 queue:restart 寫在快取裡的時間戳記）
# kill -USR2 1 php-fpm（PID 1）平順重載以清掉 OPcache；否則 revalidate_freq=60 下最多 60 秒仍跑舊程式
define reload
	docker exec $(1) php artisan optimize:clear
	docker exec $(1) php artisan route:cache
	docker exec $(1) php artisan view:cache
	docker exec $(1) kill -USR2 1
	docker exec $(1) php artisan queue:restart
endef
# 開發環境只清快取、不重建（改 route 時才不用一直重跑 route:cache）
dev-up:
	docker compose up -d
	docker exec $(DEV_APP) php artisan optimize:clear
	docker restart $(DEV_WORKER)

dev-down:
	docker compose down

dev-build:
	docker compose build --pull

stag-build:
	docker compose -f staging.yml build

stag-up:
	docker compose -f staging.yml up -d
	$(call reload,$(STAG_APP))

stag-reload:
	$(call reload,$(STAG_APP))

stag-down:
	docker compose -f staging.yml down

# 升級版測試站（staging-upgrade.yml，與舊版測試站並存）
upg-build:
	docker compose -f staging-upgrade.yml build

upg-up:
	docker compose -f staging-upgrade.yml up -d
	$(call reload,$(UPG_APP))

upg-reload:
	$(call reload,$(UPG_APP))

upg-down:
	docker compose -f staging-upgrade.yml down

prod-build:
	docker compose -f production.yml build

prod-up:
	docker compose -f production.yml up -d
	$(call reload,$(PROD_APP))

prod-reload:
	$(call reload,$(PROD_APP))

prod-down:
	docker compose -f production.yml down