-include .env
export

.PHONY: bootstrap build up start down stop restart logs shell install configure-mailpit demo-data theme-install cron backup update status

bootstrap:
	./scripts/bootstrap-moodle.sh

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
	docker compose exec moodle php admin/cli/upgrade.php --non-interactive
	docker compose exec moodle php admin/cli/cfg.php --name=theme --set=almondb
	docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=brandcolor --set="#0d3f5c"
	docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=sitecolor --set="#0d3f5c"
	docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=navbarcolor --set="#ffffff"
	docker compose exec moodle php admin/cli/cfg.php --component=theme_almondb --name=backcolor --set="#ffffff"
	docker compose exec moodle php admin/cli/build_theme_css.php --themes=almondb --direction=ltr --verbose
	docker compose exec moodle php admin/cli/purge_caches.php

cron:
	docker compose exec moodle php admin/cli/cron.php --keep-alive=0

backup:
	./scripts/backup.sh

update:
	./scripts/update-moodle.sh $(MOODLE_VERSION)

status:
	docker compose ps
