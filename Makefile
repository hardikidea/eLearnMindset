-include .env
export
MOODLE_THEME ?= elearnboost

.PHONY: bootstrap sync-overrides build up start down stop restart logs shell install configure-mailpit demo-data theme-install cron backup restore update update-restore-on-fail status

bootstrap:
	./scripts/bootstrap-moodle.sh

sync-overrides:
	./scripts/sync-moodle-overrides.sh

build:
	docker compose build

up:
	docker compose up -d

start: up

down:
	docker compose down

stop: down

restart:
	docker compose restart moodle cron

logs:
	docker compose logs -f moodle

shell:
	docker compose exec moodle bash

install:
	./scripts/install-site.sh

configure-mailpit:
	./scripts/configure-mailpit.sh

demo-data:
	./scripts/seed-indian-school-demo.sh

theme-install:
	./scripts/sync-moodle-overrides.sh
	docker compose exec moodle php admin/cli/upgrade.php --non-interactive
	docker compose exec moodle php admin/cli/cfg.php --name=theme --set=$(MOODLE_THEME)
	docker compose exec moodle php admin/cli/build_theme_css.php --themes=$(MOODLE_THEME) --direction=ltr --verbose
	docker compose exec moodle php admin/cli/purge_caches.php

cron:
	docker compose exec moodle php admin/cli/cron.php --keep-alive=0

backup:
	./scripts/backup.sh

restore:
	@test -n "$(BACKUP_DIR)" || (echo "Usage: make restore BACKUP_DIR=backups/YYYYMMDD-HHMMSS" && exit 1)
	./scripts/restore-backup.sh "$(BACKUP_DIR)" --yes

update:
	./scripts/update-moodle.sh $(MOODLE_VERSION)

update-restore-on-fail:
	./scripts/update-moodle.sh --restore-on-fail $(MOODLE_VERSION)

status:
	docker compose ps
