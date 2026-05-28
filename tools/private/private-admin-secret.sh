#!/bin/sh

set -eu

: "${PRIVATE_SECRET_ENV:=prod}"

env_name=$PRIVATE_SECRET_ENV
secret_dir="config/secrets/$env_name"
public_key="$secret_dir/$env_name.encrypt.public.php"

if [ ! -f "$public_key" ]; then
    printf '%s\n' "==> Generating Symfony secrets keys for $env_name"
    php bin/console secrets:generate-keys --env="$env_name" --no-interaction
fi

restore_tty() {
    stty echo 2>/dev/null || true
}

trap 'restore_tty' EXIT INT TERM

read_secret() {
    prompt=$1
    printf '%s' "$prompt" >&2
    stty -echo 2>/dev/null || true
    if ! IFS= read -r value; then
        restore_tty
        printf '\n' >&2
        exit 1
    fi
    restore_tty
    printf '\n' >&2
    printf '%s' "$value"
}

password=$(read_secret 'Private admin password: ')
password_confirm=$(read_secret 'Confirm private admin password: ')

if [ -z "$password" ]; then
    printf '%s\n' 'Password cannot be empty.' >&2
    exit 1
fi

if [ "$password" != "$password_confirm" ]; then
    printf '%s\n' 'Passwords do not match.' >&2
    exit 1
fi

hash=$(printf '%s' "$password" | php -r 'require "vendor/autoload.php"; $password = stream_get_contents(STDIN); $hasher = new Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher(); echo $hasher->hash($password), PHP_EOL;')

unset password password_confirm

printf '%s' "$hash" | php bin/console secrets:set PRIVATE_ADMIN_PASSWORD_HASH - --env="$env_name" --no-interaction

unset hash

printf '%s\n' "PRIVATE_ADMIN_PASSWORD_HASH stored in Symfony secrets for $env_name."
