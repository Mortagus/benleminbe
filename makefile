deploy:
	git pull --rebase
	composer install --no-dev --optimize-autoloader --no-interaction
	php bin/console doctrine:migrations:migrate --no-interaction || true
	php bin/console asset-map:compile --env=prod
	php bin/console cache:clear --env=prod
