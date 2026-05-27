.PHONY: deploy track_logs reload_assets cc serv check install-hooks gpt_css pagespeed_audit private-admin-secret private-prod-check private-prod-auth-check

PRIVATE_SECRET_ENV ?= prod
PRIVATE_BASE_URL ?= https://benlemin.be
PRIVATE_ADMIN_USERNAME ?= private_admin
PAGESPEED_BASE_URL ?= https://benlemin.be
PAGESPEED_LOCALE ?= fr-FR
PAGESPEED_STRATEGY ?= both
PAGESPEED_OUTPUT_DIR ?= var/audits/pagespeed/$(shell date +%F)
PAGESPEED_API_KEY ?=
PAGESPEED_RETRY_COUNT ?= 2
PAGESPEED_RETRY_DELAY_MS ?= 1500
PAGESPEED_TIMEOUT_SECONDS ?= 120

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
	@echo "==> Markdown format"
	npm run lint:md
	@echo "==> CSS lint"
	npx stylelint "assets/styles/**/*.css"
	@echo "==> JavaScript lint"
	npm run lint:js
	@echo "==> JavaScript tests"
	npm run test:js
	@echo "All checks passed."

install-hooks:
	git config core.hooksPath .githooks
	chmod +x .githooks/pre-commit
	@echo "Git hooks installed from .githooks."

gpt_css:
	php tools/build_gpt_css.php var/gpt/consolidated-css-context.css assets/styles

pagespeed_audit:
	PAGESPEED_API_KEY="$(PAGESPEED_API_KEY)" php tools/pagespeed/collect_pagespeed.php --base-url="$(PAGESPEED_BASE_URL)" --locale="$(PAGESPEED_LOCALE)" --strategy="$(PAGESPEED_STRATEGY)" --output-dir="$(PAGESPEED_OUTPUT_DIR)" --retry-count="$(PAGESPEED_RETRY_COUNT)" --retry-delay-ms="$(PAGESPEED_RETRY_DELAY_MS)" --timeout-seconds="$(PAGESPEED_TIMEOUT_SECONDS)"

private-admin-secret:
	@bash -euo pipefail -c '\
		env_name="$(PRIVATE_SECRET_ENV)"; \
		secret_dir="config/secrets/$$env_name"; \
		public_key="$$secret_dir/$$env_name.encrypt.public.php"; \
		if [ ! -f "$$public_key" ]; then \
			echo "==> Generating Symfony secrets keys for $$env_name"; \
			php bin/console secrets:generate-keys --env="$$env_name" --no-interaction; \
		fi; \
		read -r -s -p "Private admin password: " password; echo; \
		read -r -s -p "Confirm private admin password: " password_confirm; echo; \
		if [ -z "$$password" ]; then \
			echo "Password cannot be empty." >&2; \
			exit 1; \
		fi; \
		if [ "$$password" != "$$password_confirm" ]; then \
			echo "Passwords do not match." >&2; \
			exit 1; \
		fi; \
		hash="$$(printf "%s" "$$password" | php -r '\''require "vendor/autoload.php"; $$password = stream_get_contents(STDIN); $$hasher = new Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher(); echo $$hasher->hash($$password), PHP_EOL;'\'' )"; \
		unset password password_confirm; \
		printf "%s" "$$hash" | php bin/console secrets:set PRIVATE_ADMIN_PASSWORD_HASH - --env="$$env_name" --no-interaction; \
		unset hash; \
		echo "PRIVATE_ADMIN_PASSWORD_HASH stored in Symfony secrets for $$env_name."; \
	'

private-prod-check:
	@bash -euo pipefail -c '\
		base_url="$(PRIVATE_BASE_URL)"; \
		base_url="$${base_url%/}"; \
		headers_file="$$(mktemp)"; \
		body_file="$$(mktemp)"; \
		trap '\''rm -f "$$headers_file" "$$body_file"'\'' EXIT; \
		echo "==> Checking private redirect without session"; \
		status="$$(curl -sS -o /dev/null -D "$$headers_file" -w "%{http_code}" "$$base_url/private")"; \
		location="$$(tr -d "\r" < "$$headers_file" | awk '\''BEGIN{IGNORECASE=1} /^location:/ {print $$2; exit}'\'')"; \
		if [ "$$status" != "302" ] && [ "$$status" != "303" ]; then \
			echo "Expected /private to redirect, got HTTP $$status." >&2; \
			exit 1; \
		fi; \
		case "$$location" in \
			*/private/login|*/private/login\?*) ;; \
			*) echo "Expected redirect to /private/login, got: $$location" >&2; exit 1 ;; \
		esac; \
		echo "==> Checking private login page"; \
		status="$$(curl -sS -o "$$body_file" -w "%{http_code}" "$$base_url/private/login")"; \
		if [ "$$status" != "200" ]; then \
			echo "Expected /private/login HTTP 200, got $$status." >&2; \
			exit 1; \
		fi; \
		if ! grep -q '\''name="_csrf_token"'\'' "$$body_file"; then \
			echo "Login page does not expose the CSRF token field." >&2; \
			exit 1; \
		fi; \
		if ! grep -qi '\''noindex,nofollow'\'' "$$body_file"; then \
			echo "Login page does not contain noindex,nofollow." >&2; \
			exit 1; \
		fi; \
		echo "==> Checking robots.txt"; \
		curl -sS "$$base_url/robots.txt" -o "$$body_file"; \
		if ! grep -q '\''Disallow: /private/'\'' "$$body_file"; then \
			echo "robots.txt does not contain Disallow: /private/." >&2; \
			exit 1; \
		fi; \
		echo "==> Checking sitemap exclusion"; \
		curl -sS "$$base_url/sitemap.xml" -o "$$body_file"; \
		if grep -q '\''/private'\'' "$$body_file"; then \
			echo "sitemap.xml exposes a private URL." >&2; \
			exit 1; \
		fi; \
		echo "==> Checking private asset entrypoint"; \
		status="$$(curl -sS -o "$$body_file" -w "%{http_code}" "$$base_url/assets/entrypoint.private.json")"; \
		if [ "$$status" != "200" ]; then \
			echo "Expected private asset entrypoint HTTP 200, got $$status." >&2; \
			exit 1; \
		fi; \
		echo "Private production public checks passed."; \
	'

private-prod-auth-check:
	@bash -euo pipefail -c '\
		base_url="$(PRIVATE_BASE_URL)"; \
		base_url="$${base_url%/}"; \
		username="$(PRIVATE_ADMIN_USERNAME)"; \
		cookies_file="$$(mktemp)"; \
		login_file="$$(mktemp)"; \
		post_file="$$(mktemp)"; \
		result_file="$$(mktemp)"; \
		trap '\''rm -f "$$cookies_file" "$$login_file" "$$post_file" "$$result_file"'\'' EXIT; \
		get_csrf_token() { \
			curl -sS -c "$$cookies_file" -b "$$cookies_file" "$$base_url/private/login" -o "$$login_file"; \
			sed -n '\''s/.*name="_csrf_token" value="\([^"]*\)".*/\1/p'\'' "$$login_file" | head -n 1; \
		}; \
		build_login_form() { \
			printf "%s\n%s\n%s\n" "$$1" "$$2" "$$3" | php -r '\''$$username = rtrim(fgets(STDIN), "\r\n"); $$password = rtrim(fgets(STDIN), "\r\n"); $$token = rtrim(fgets(STDIN), "\r\n"); echo http_build_query(["_username" => $$username, "_password" => $$password, "_csrf_token" => $$token]);'\'' > "$$post_file"; \
		}; \
		echo "==> Checking invalid private login"; \
		token="$$(get_csrf_token)"; \
		if [ -z "$$token" ]; then \
			echo "Unable to read CSRF token from private login page." >&2; \
			exit 1; \
		fi; \
		build_login_form "$$username" "__invalid_private_password__" "$$token"; \
		result="$$(curl -sS -L -b "$$cookies_file" -c "$$cookies_file" -o "$$result_file" -w "%{http_code} %{url_effective}" \
			-H "Content-Type: application/x-www-form-urlencoded" \
			--data-binary "@$$post_file" \
			"$$base_url/private/login")"; \
		: > "$$post_file"; \
		status="$${result%% *}"; \
		final_url="$${result#* }"; \
		if [ "$$status" != "200" ]; then \
			echo "Expected invalid login to end with HTTP 200 on login page, got $$status." >&2; \
			exit 1; \
		fi; \
		case "$$final_url" in \
			*/private/login|*/private/login\?*) ;; \
			*) echo "Expected invalid login to stay on /private/login, got: $$final_url" >&2; exit 1 ;; \
		esac; \
		if ! grep -q '\''Identifiants invalides'\'' "$$result_file"; then \
			echo "Invalid login did not display the expected error message." >&2; \
			exit 1; \
		fi; \
		read -r -s -p "Private admin password for $$username: " password; echo; \
		if [ -z "$$password" ]; then \
			echo "Password cannot be empty." >&2; \
			exit 1; \
		fi; \
		echo "==> Checking valid private login"; \
		token="$$(get_csrf_token)"; \
		if [ -z "$$token" ]; then \
			echo "Unable to read CSRF token from private login page." >&2; \
			exit 1; \
		fi; \
		build_login_form "$$username" "$$password" "$$token"; \
		result="$$(curl -sS -L -b "$$cookies_file" -c "$$cookies_file" -o "$$result_file" -w "%{http_code} %{url_effective}" \
			-H "Content-Type: application/x-www-form-urlencoded" \
			--data-binary "@$$post_file" \
			"$$base_url/private/login")"; \
		: > "$$post_file"; \
		unset password; \
		status="$${result%% *}"; \
		final_url="$${result#* }"; \
		if [ "$$status" != "200" ]; then \
			echo "Expected valid login to end with HTTP 200 on dashboard, got $$status." >&2; \
			exit 1; \
		fi; \
		case "$$final_url" in \
			*/private|*/private/) ;; \
			*) echo "Expected valid login to reach /private, got: $$final_url" >&2; exit 1 ;; \
		esac; \
		if ! grep -q '\''Déconnexion'\'' "$$result_file"; then \
			echo "Authenticated dashboard does not contain the logout link." >&2; \
			exit 1; \
		fi; \
		if ! grep -qi '\''noindex,nofollow'\'' "$$result_file"; then \
			echo "Authenticated dashboard does not contain noindex,nofollow." >&2; \
			exit 1; \
		fi; \
		echo "==> Checking private logout"; \
		result="$$(curl -sS -L -b "$$cookies_file" -c "$$cookies_file" -o "$$result_file" -w "%{http_code} %{url_effective}" "$$base_url/private/logout")"; \
		status="$${result%% *}"; \
		final_url="$${result#* }"; \
		if [ "$$status" != "200" ]; then \
			echo "Expected logout to end with HTTP 200 on login page, got $$status." >&2; \
			exit 1; \
		fi; \
		case "$$final_url" in \
			*/private/login|*/private/login\?*) ;; \
			*) echo "Expected logout to redirect to /private/login, got: $$final_url" >&2; exit 1 ;; \
		esac; \
		status="$$(curl -sS -o /dev/null -w "%{http_code}" -b "$$cookies_file" "$$base_url/private")"; \
		if [ "$$status" != "302" ] && [ "$$status" != "303" ]; then \
			echo "Expected /private to redirect after logout, got HTTP $$status." >&2; \
			exit 1; \
		fi; \
		echo "Private production authenticated checks passed."; \
	'
