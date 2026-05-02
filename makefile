deploy:
	git pull --rebase
	composer install --no-dev --optimize-autoloader --no-interaction
	mkdir -p var/log
	touch var/log/cv-downloads.log
	chmod 664 var/log/cv-downloads.log
	php bin/console doctrine:migrations:migrate --no-interaction || true
	php bin/console asset-map:compile --env=prod
	php bin/console cache:clear --env=prod
	php bin/console cache:warmup --env=prod

track_logs:
	tail -f --retry var/log/cv-downloads.log
