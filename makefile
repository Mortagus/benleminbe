.PHONY: deploy track_logs reload_assets cc serv check test_php install-hooks gpt_css gpt_docs pagespeed_audit private-admin-secret private-prod-check private-prod-auth-check db-check migrate

PRIVATE_SECRET_ENV ?= prod
PRIVATE_BASE_URL ?= https://benlemin.be
PRIVATE_ADMIN_USERNAME ?= private_admin
MIGRATION_ENV ?= dev
PAGESPEED_BASE_URL ?= https://benlemin.be
PAGESPEED_LOCALE ?= fr-FR
PAGESPEED_STRATEGY ?= both
PAGESPEED_OUTPUT_DIR ?= var/audits/pagespeed/$(shell date +%F)
PAGESPEED_API_KEY ?=
PAGESPEED_RETRY_COUNT ?= 2
PAGESPEED_RETRY_DELAY_MS ?= 1500
PAGESPEED_TIMEOUT_SECONDS ?= 120
GPT_DOCS_OUTPUT ?= var/gpt/consolidated-markdown-context.md

deploy:
	git pull --rebase
	composer install --no-dev --optimize-autoloader --no-interaction
	mkdir -p var/log
	touch var/log/cv-downloads.log
	chmod 664 var/log/cv-downloads.log
	$(MAKE) migrate MIGRATION_ENV=prod
	php bin/console cache:clear --env=prod
	php bin/console cache:warmup --env=prod
	php bin/console asset-map:compile --env=prod
	php bin/console app:generate-sitemap --env=prod
	$(MAKE) private-prod-check

track_logs:
	tail -f --retry var/log/cv-downloads.log

reload_assets:
	php bin/console asset-map:compile

cc: reload_assets
	php bin/console cache:clear

serv:
	symfony local:server:start

migrate:
	php bin/console doctrine:migrations:migrate --no-interaction --env="$(MIGRATION_ENV)"

db-check:
	@set -e; \
	container_id="$$(docker compose ps -q db)"; \
	if [ -z "$$container_id" ]; then \
		echo "db service is not running. Start it with: docker compose up -d db"; \
		exit 1; \
	fi; \
	if [ "$$(docker inspect -f '{{.State.Running}}' "$$container_id")" != "true" ]; then \
		echo "db service container exists but is not running."; \
		exit 1; \
	fi; \
	docker compose exec -T db mariadb -uapp -papp -h 127.0.0.1 -e "SELECT 1;" app >/dev/null; \
	echo "db service is running and MariaDB is reachable."

check:
	@echo "==> Composer metadata"
	composer validate --strict --no-check-publish
	@echo "==> PHP syntax"
	find src config public tools -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null
	@echo "==> YAML syntax"
	php bin/console lint:yaml config translations --parse-tags
	@echo "==> Twig syntax"
	php bin/console lint:twig templates
	@echo "==> Symfony container"
	php bin/console lint:container
	@echo "==> Markdown format"
	@echo "Markdown lint temporairement désactive dans make check. Utiliser 'npm run lint:md' si besoin."
	@echo "==> CSS lint"
	npx stylelint "assets/styles/**/*.css"
	@echo "==> JavaScript lint"
	npm run lint:js
	@echo "==> JavaScript tests"
	npm run test:js
	@echo "All checks passed."

test_php:
	php bin/console doctrine:database:create --env=test --if-not-exists
	php bin/console doctrine:migrations:migrate --env=test --no-interaction
	./vendor/bin/phpunit --configuration phpunit.xml.dist

install-hooks:
	git config core.hooksPath .githooks
	chmod +x .githooks/pre-commit
	@echo "Git hooks installed from .githooks."

gpt_css:
	php tools/build_gpt_css.php var/gpt/consolidated-css-context.css assets/styles

gpt_docs:
	php tools/build_gpt_markdown_context.php "$(GPT_DOCS_OUTPUT)" README.md docs/documentation-index.md docs/documentation-architecture.md docs/documentation-routing.md docs/project-architecture.md docs/content-workflow.md docs/deployment-and-verification.md docs/assistant-context.md docs/private docs/lab docs/en-cours

pagespeed_audit:
	PAGESPEED_API_KEY="$(PAGESPEED_API_KEY)" php tools/pagespeed/collect_pagespeed.php --base-url="$(PAGESPEED_BASE_URL)" --locale="$(PAGESPEED_LOCALE)" --strategy="$(PAGESPEED_STRATEGY)" --output-dir="$(PAGESPEED_OUTPUT_DIR)" --retry-count="$(PAGESPEED_RETRY_COUNT)" --retry-delay-ms="$(PAGESPEED_RETRY_DELAY_MS)" --timeout-seconds="$(PAGESPEED_TIMEOUT_SECONDS)"

private-admin-secret:
	@PRIVATE_SECRET_ENV="$(PRIVATE_SECRET_ENV)" sh tools/private/private-admin-secret.sh

private-prod-check:
	@PRIVATE_BASE_URL="$(PRIVATE_BASE_URL)" sh tools/private/private-prod-check.sh

private-prod-auth-check:
	@PRIVATE_BASE_URL="$(PRIVATE_BASE_URL)" PRIVATE_ADMIN_USERNAME="$(PRIVATE_ADMIN_USERNAME)" sh tools/private/private-prod-auth-check.sh
