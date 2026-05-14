.PHONY: deploy track_logs reload_assets cc serv check install-hooks gpt_css

deploy:
	git pull --rebase
	composer install --no-dev --optimize-autoloader --no-interaction
	mkdir -p var/log
	touch var/log/cv-downloads.log
	chmod 664 var/log/cv-downloads.log
	#php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console cache:clear --env=prod
	php bin/console cache:warmup --env=prod
	php bin/console asset-map:compile --env=prod
	php bin/console app:generate-sitemap --env=prod

track_logs:
	tail -f --retry var/log/cv-downloads.log

reload_assets:
	php bin/console asset-map:compile

cc: reload_assets
	php bin/console cache:clear

serv:
	symfony local:server:start

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
	@echo "==> CSS lint"
	npx stylelint "assets/styles/**/*.css"
	@echo "All checks passed."

install-hooks:
	git config core.hooksPath .githooks
	chmod +x .githooks/pre-commit
	@echo "Git hooks installed from .githooks."

gpt_css:
	php tools/build_gpt_css.php var/gpt/css-context.css assets/styles/app.css assets/styles/lab/dnd/lab_dnd_initiative.css
