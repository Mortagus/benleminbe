#!/bin/sh

set -eu

: "${PRIVATE_BASE_URL:=https://benlemin.be}"
: "${PRIVATE_ADMIN_USERNAME:=private_admin}"

base_url=${PRIVATE_BASE_URL%/}
username=$PRIVATE_ADMIN_USERNAME
cookies_file=$(mktemp)
login_file=$(mktemp)
post_file=$(mktemp)
result_file=$(mktemp)

cleanup() {
    rm -f "$cookies_file" "$login_file" "$post_file" "$result_file"
}

trap cleanup EXIT INT TERM

restore_tty() {
    stty echo 2>/dev/null || true
}

trap 'restore_tty; cleanup' EXIT INT TERM

get_csrf_token() {
    curl -sS -c "$cookies_file" -b "$cookies_file" "$base_url/private/login" -o "$login_file"
    sed -n 's/.*name="_csrf_token" value="\([^"]*\)".*/\1/p' "$login_file" | head -n 1
}

build_login_form() {
    php -r '$username = $argv[1]; $password = $argv[2]; $token = $argv[3]; echo http_build_query(["_username" => $username, "_password" => $password, "_csrf_token" => $token]);' \
        "$1" "$2" "$3" > "$post_file"
}

read_secret() {
    prompt=$1
    if [ "${PRIVATE_ADMIN_PASSWORD:-}" != "" ]; then
        printf '%s' "$PRIVATE_ADMIN_PASSWORD"
        return 0
    fi

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

printf '%s\n' '==> Checking invalid private login'
token=$(get_csrf_token)

if [ -z "$token" ]; then
    printf '%s\n' 'Unable to read CSRF token from private login page.' >&2
    exit 1
fi

build_login_form "$username" '__invalid_private_password__' "$token"

result=$(curl -sS -L -b "$cookies_file" -c "$cookies_file" -o "$result_file" -w '%{http_code} %{url_effective}' \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-binary "@$post_file" \
    "$base_url/private/login")

: > "$post_file"
status=${result%% *}
final_url=${result#* }

if [ "$status" != "200" ]; then
    printf '%s\n' "Expected invalid login to end with HTTP 200 on login page, got $status." >&2
    exit 1
fi

case "$final_url" in
    */private/login|*/private/login?*) ;;
    *) printf '%s\n' "Expected invalid login to stay on /private/login, got: $final_url" >&2; exit 1 ;;
esac

if ! grep -q 'Identifiants invalides' "$result_file"; then
    printf '%s\n' 'Invalid login did not display the expected error message.' >&2
    exit 1
fi

password=$(read_secret "Private admin password for $username: ")

if [ -z "$password" ]; then
    printf '%s\n' 'Password cannot be empty.' >&2
    exit 1
fi

printf '%s\n' '==> Checking valid private login'
token=$(get_csrf_token)

if [ -z "$token" ]; then
    printf '%s\n' 'Unable to read CSRF token from private login page.' >&2
    exit 1
fi

build_login_form "$username" "$password" "$token"

result=$(curl -sS -L -b "$cookies_file" -c "$cookies_file" -o "$result_file" -w '%{http_code} %{url_effective}' \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    --data-binary "@$post_file" \
    "$base_url/private/login")

: > "$post_file"
unset password
status=${result%% *}
final_url=${result#* }

if [ "$status" != "200" ]; then
    printf '%s\n' "Expected valid login to end with HTTP 200 on dashboard, got $status." >&2
    exit 1
fi

case "$final_url" in
    */private|*/private/) ;;
    *) printf '%s\n' "Expected valid login to reach /private, got: $final_url" >&2; exit 1 ;;
esac

if ! grep -q 'Déconnexion' "$result_file"; then
    printf '%s\n' 'Authenticated dashboard does not contain the logout link.' >&2
    exit 1
fi

if ! grep -qi 'noindex,nofollow' "$result_file"; then
    printf '%s\n' 'Authenticated dashboard does not contain noindex,nofollow.' >&2
    exit 1
fi

printf '%s\n' '==> Checking private passkeys page'
result=$(curl -sS -L -b "$cookies_file" -c "$cookies_file" -o "$result_file" -w '%{http_code} %{url_effective}' \
    "$base_url/private/security/passkeys")
status=${result%% *}
final_url=${result#* }

if [ "$status" != "200" ]; then
    printf '%s\n' "Expected passkeys page to return HTTP 200, got $status." >&2
    exit 1
fi

case "$final_url" in
    */private/security/passkeys|*/private/security/passkeys?*) ;;
    *) printf '%s\n' "Expected passkeys page to remain on /private/security/passkeys, got: $final_url" >&2; exit 1 ;;
esac

if ! grep -q 'Passkeys' "$result_file"; then
    printf '%s\n' 'Passkeys page does not contain the expected heading.' >&2
    exit 1
fi

printf '%s\n' '==> Checking private logout'
result=$(curl -sS -L -b "$cookies_file" -c "$cookies_file" -o "$result_file" -w '%{http_code} %{url_effective}' "$base_url/private/logout")
status=${result%% *}
final_url=${result#* }

if [ "$status" != "200" ]; then
    printf '%s\n' "Expected logout to end with HTTP 200 on login page, got $status." >&2
    exit 1
fi

case "$final_url" in
    */private/login|*/private/login?*) ;;
    *) printf '%s\n' "Expected logout to redirect to /private/login, got: $final_url" >&2; exit 1 ;;
esac

status=$(curl -sS -o /dev/null -w '%{http_code}' -b "$cookies_file" "$base_url/private")

if [ "$status" != "302" ] && [ "$status" != "303" ]; then
    printf '%s\n' "Expected /private to redirect after logout, got HTTP $status." >&2
    exit 1
fi

printf '%s\n' 'Private production authenticated checks passed.'
