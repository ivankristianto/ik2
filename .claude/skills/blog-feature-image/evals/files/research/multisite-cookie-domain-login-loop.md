# Research: The multisite login loop caused by a hardcoded COOKIE_DOMAIN

Idea size: short. Source idea: `.claude/blog/ideas/2026-10-20.md`, item 4.

## 1. Thesis

On a WordPress multisite with mapped domains, a hardcoded `COOKIE_DOMAIN` in `wp-config.php` makes every site outside that domain refuse logins with the "Cookies are blocked" error. On a subdirectory install, removing the constant stops the loop; on a subdomain install, core would put the network domain back, so set it from the request host instead.

## 2. Audience

WordPress developers running or inheriting a multisite network with custom domains per site. They know `wp-config.php` and what a cookie is. They may not know how multisite picks a cookie domain.

## 3. Story

- During a migration of the Northwind Trust network (NWT-4182), editors on two of the mapped-domain sites reported they could not log in. The main site worked.
- The login form reloaded with "Error: Cookies are blocked or not supported by your browser. You must enable cookies to use WordPress."
- I first suspected a caching layer stripping `Set-Cookie`. Response headers showed the cookies were sent, so that was wrong.
- The `Set-Cookie` headers carried `Domain=.example.com` on a site served from `example.org`. The browser drops a cookie whose domain does not match the host, so the login check found no test cookie.
- `wp-config.php` had `define( 'COOKIE_DOMAIN', '.example.com' );`, added years earlier when the network had a single domain.
- The network was a subdirectory install (`SUBDOMAIN_INSTALL` false). Removing the line fixed the mapped sites: `ms_cookie_constants()` skips `COOKIE_DOMAIN` on subdirectory installs, `wp_cookie_constants()` then defines it as an empty string, and every domain gets host-only cookies.
- On a subdomain install the same removal would not help. `ms_cookie_constants()` would define `COOKIE_DOMAIN` as `.` plus the network domain, and mapped domains would reject it again. I read this in core; I did not reproduce it.
- I did not test the `$_SERVER['HTTP_HOST']` variant on that network; I only tested it locally.

## 4. Claims table

| # | Claim | Status | How verified | Source |
|---|-------|--------|--------------|--------|
| 1 | When `COOKIE_DOMAIN` is not defined and the install uses subdomains, `ms_cookie_constants()` defines it as `.` plus the network's `cookie_domain` or `domain`. | sourced | Read core source at 6.8 | https://github.com/WordPress/wordpress-develop/blob/6.8/src/wp-includes/ms-default-constants.php |
| 2 | Browsers reject a `Set-Cookie` whose `Domain` attribute does not domain-match the request host. | sourced | RFC 6265 section 5.3, step 6 | https://www.rfc-editor.org/rfc/rfc6265#section-5.3 |
| 3 | The login form shows "Error: Cookies are blocked or not supported by your browser. You must enable cookies to use WordPress." when the test cookie is missing. | verified | Reproduced on a local two-domain multisite | local reproduction |
| 4 | Removing the `COOKIE_DOMAIN` define fixed login on the mapped-domain sites. | verified | Reproduced locally, and confirmed on the real network | local reproduction |
| 5 | `define( 'COOKIE_DOMAIN', $_SERVER['HTTP_HOST'] );` also fixes it. | verified | Local only, WordPress 6.8, PHP 8.4 | local reproduction |
| 6 | The wp-config documentation describes `COOKIE_DOMAIN`. | sourced | Read the doc section | https://developer.wordpress.org/apis/wp-config-php/#set-cookie-domain |
| 7 | On a subdirectory install, `wp_cookie_constants()` defines `COOKIE_DOMAIN` as an empty string when nothing else has. | sourced | Read core source at 6.8 | https://github.com/WordPress/wordpress-develop/blob/6.8/src/wp-includes/default-constants.php |
| 8 | About 30% of editors on the network were locked out. | unverified | Estimate from a chat message, never counted | none |

## 5. Snippets

The line that caused it (`wp-config.php`):

```php
define( 'COOKIE_DOMAIN', '.example.com' );
```

Checking the cookie domain from the shell (tested with curl 8.7 against the local stack):

```bash
curl -s -o /dev/null -D - -X POST https://example.org.test/wp-login.php \
  --data 'log=editor&pwd=wrong&testcookie=1' -b 'wordpress_test_cookie=WP+Cookie+check' \
  | rg -i '^set-cookie'
```

Printed before the fix: `set-cookie: wordpress_test_cookie=WP%20Cookie%20check; path=/; domain=.example.com; secure`

The per-request alternative, if something else needs the constant defined (WordPress 6.8, PHP 8.4, local only):

```php
define( 'COOKIE_DOMAIN', $_SERVER['HTTP_HOST'] ?? '' );
```

## 6. Related

- https://developer.wordpress.org/apis/wp-config-php/#set-cookie-domain : the official doc; says what the constant does, not how it breaks mapped domains.
- https://developer.wordpress.org/advanced-administration/multisite/ : multisite admin handbook, no mention of this failure.
- No existing post on ivankristianto.com covers multisite cookies.

## 7. Suggested outline

Title candidate: "WordPress multisite login loop on mapped domains: check COOKIE_DOMAIN"
Excerpt candidate: "Editors on mapped-domain sites got 'Cookies are blocked' at login. The cause was one old line in wp-config.php."

1. The symptom
2. The short answer
3. Why the browser threw the cookie away
4. How multisite picks a cookie domain (subdirectory vs subdomain)
5. The fix, and the per-request variant for subdomain installs

## 8. Questions for Ivan

- How long had the define been there? The transcript does not say.

## 9. Leave out

- The hosting provider's name and the caching setup that was ruled out.
- The editors' names and the support thread.
