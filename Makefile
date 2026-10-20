#SHELL := bash
#.ONESHELL:
#.SHELLFLAGS := -eu -o pipefail -c
#.DELETE_ON_ERROR:
#MAKEFLAGS += --warn-undefined-variables
#MAKEFLAGS += --no-builtin-rules

DEV_APP     := taicol-nametool-dev-app
DEV_WORKER  := taicol-nametool-dev-worker
STAG_APP    := taicol-nametool-stag-app
STAG_WORKER := taicol-nametool-stag-worker
PROD_APP    := taicol-nametool-app
PROD_WORKER := taicol-nametool-worker

# 開發環境只清快取、不重建（改 route 時才不用一直重跑 route:cache）
dev-up:
	docker compose up -d
	docker exec $(DEV_APP) php artisan optimize:clear
	docker restart $(DEV_WORKER)

dev-down:
	docker compose down

dev-build:
	docker compose build

stag-build:
	docker compose -f staging.yml build

stag-up:
	docker compose -f staging.yml up -d
	docker exec $(STAG_APP) php artisan optimize:clear
	docker exec $(STAG_APP) php artisan route:cache
	docker exec $(STAG_APP) php artisan view:cache
	docker restart $(STAG_WORKER)

stag-down:
	docker compose -f staging.yml down

prod-build:
	docker compose -f production.yml build

prod-up:
	docker compose -f production.yml up -d
	docker exec $(PROD_APP) php artisan optimize:clear
	docker exec $(PROD_APP) php artisan route:cache
	docker exec $(PROD_APP) php artisan view:cache
	docker restart $(PROD_WORKER)

prod-down:
	docker compose -f production.yml down