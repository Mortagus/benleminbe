#!/bin/sh

set -eu

: "${PRIVATE_BASE_URL:=https://benlemin.be}"

base_url=${PRIVATE_BASE_URL%/}
headers_file=$(mktemp)
body_file=$(mktemp)

cleanup() {
    rm -f "$headers_file" "$body_file"
}

trap cleanup EXIT INT TERM

printf '%s\n' '==> Checking private redirect without session'
status=$(curl -sS -o /dev/null -D "$headers_file" -w '%{http_code}' "$base_url/private")
location=$(tr -d '\r' < "$headers_file" | awk 'BEGIN{IGNORECASE=1} /^location:/ {print $2; exit}')

if [ "$status" != "302" ] && [ "$status" != "303" ]; then
    printf '%s\n' "Expected /private to redirect, got HTTP $status." >&2
    exit 1
fi

case "$location" in
    */private/login|*/private/login?*) ;;
    *) printf '%s\n' "Expected redirect to /private/login, got: $location" >&2; exit 1 ;;
esac

printf '%s\n' '==> Checking private login page'
status=$(curl -sS -o "$body_file" -w '%{http_code}' "$base_url/private/login")

if [ "$status" != "200" ]; then
    printf '%s\n' "Expected /private/login HTTP 200, got $status." >&2
    exit 1
fi

if ! grep -q 'Connexion privée' "$body_file"; then
    printf '%s\n' 'Login page does not contain the private login heading.' >&2
    exit 1
fi

if ! grep -q 'name="_csrf_token"' "$body_file"; then
    printf '%s\n' 'Login page does not expose the CSRF token field.' >&2
    exit 1
fi

if ! grep -qi 'noindex,nofollow' "$body_file"; then
    printf '%s\n' 'Login page does not contain noindex,nofollow.' >&2
    exit 1
fi

printf '%s\n' '==> Checking private route protection'
for protected_path in /private/network /private/network/contacts /private/network/platforms /private/network/import; do
    status=$(curl -sS -o /dev/null -D "$headers_file" -w '%{http_code}' "$base_url$protected_path")
    location=$(tr -d '\r' < "$headers_file" | awk 'BEGIN{IGNORECASE=1} /^location:/ {print $2; exit}')

    if [ "$status" != "302" ] && [ "$status" != "303" ]; then
        printf '%s\n' "Expected $protected_path to redirect, got HTTP $status." >&2
        exit 1
    fi

    case "$location" in
        */private/login|*/private/login?*) ;;
        *) printf '%s\n' "Expected $protected_path to redirect to /private/login, got: $location" >&2; exit 1 ;;
    esac
done

printf '%s\n' '==> Checking robots.txt'
curl -sS "$base_url/robots.txt" -o "$body_file"

if ! grep -q 'Disallow: /private/' "$body_file"; then
    printf '%s\n' 'robots.txt does not contain Disallow: /private/.' >&2
    exit 1
fi

printf '%s\n' '==> Checking sitemap exclusion'
curl -sS "$base_url/sitemap.xml" -o "$body_file"

if grep -q '/private' "$body_file"; then
    printf '%s\n' 'sitemap.xml exposes a private URL.' >&2
    exit 1
fi

printf '%s\n' '==> Checking private asset entrypoint'
status=$(curl -sS -o "$body_file" -w '%{http_code}' "$base_url/assets/entrypoint.private.json")

if [ "$status" != "200" ]; then
    printf '%s\n' "Expected private asset entrypoint HTTP 200, got $status." >&2
    exit 1
fi

if ! php -r '
$bodyFile = $argv[1];
$expected = array_slice($argv, 2);
$data = json_decode(file_get_contents($bodyFile), true, 512, JSON_THROW_ON_ERROR);

foreach ($expected as $value) {
    if (!in_array($value, $data, true)) {
        fwrite(STDERR, sprintf("Private asset entrypoint does not reference %s.\n", $value));
        exit(1);
    }
}
' "$body_file" '/assets/styles/private/private.css' '/assets/scripts/private/copy-to-clipboard.js' '/assets/scripts/theme-switcher.js'; then
    exit 1
fi

printf '%s\n' 'Private production public checks passed.'
