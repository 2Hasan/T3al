# Deploying T3al on Plesk (test: 7assan.dev)

T3al runs on one domain, with each surface on its own path:

| Surface | URL | Who uses it |
|---|---|---|
| Marketing site and customer cards | `https://7assan.dev/`, `https://7assan.dev/c/{code}` | Visitors and customers |
| Shop panel (owners and cashiers) | `https://7assan.dev/app` | Shop owners and staff |
| Admin panel | `https://7assan.dev/admin` | T3al staff only |

No subdomains are needed. The `T3AL_*_DOMAIN` values in `.env` stay empty, which tells the app to serve the panels on paths.

## 1. Plesk domain

1. Add `7assan.dev` as a domain (or subscription).
2. Set its document root to the repo's `public` folder.
3. Enable Let's Encrypt SSL for `7assan.dev`.

## 2. Server requirements

- PHP 8.3 with `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `fileinfo`, `openssl`, `tokenizer`.
- Note the PHP 8.3 command-line path on the server (for example `/opt/plesk/php/8.3/bin/php`). The default `php` may be an older version, and `artisan` refuses to run on it.
- Composer 2.
- Node 22 LTS (Node 20.19 or newer also works). Vite 8 does not build on Node 20.13.
- PostgreSQL 16, with a database and a role for T3al.

## 3. Git deployment

1. Plesk > Git > add repository `https://github.com/2Hasan/T3al`, branch `main`.
2. If the repo is private, add the Plesk SSH public key to GitHub as a deploy key first.
3. Under "Additional deployment actions", add (use the PHP 8.3 path from section 2):

   ```bash
   PHP_BIN=/opt/plesk/php/8.3/bin/php bash deploy/plesk-deploy.sh
   ```

4. Turn on automatic deployment, or deploy by hand from Plesk.

The script installs dependencies without dev packages, builds the front end, runs migrations, publishes Filament assets, creates the storage link, and caches config, routes and views. It is safe to run on every deploy.

**Permissions:** run the deploy and any `artisan` command as the domain's own system user, not `root`. If the user runs them as `root`, Laravel creates cache files that the web server cannot write, and the site returns errors. If that happens, give `bootstrap/cache` and `storage` back to the domain user (`chown -R`).

## 4. Environment

1. Copy `deploy/env.production.example` to `.env` in the repo folder.
2. Fill in `DB_*`, and set `APP_KEY` from `php artisan key:generate --show` run on the server.
3. Keep `APP_ENV=production` and `APP_DEBUG=false`.
4. Never commit `.env`.

> **Warning:** `APP_KEY` encrypts the SMTP password saved in the admin panel. Do not change `APP_KEY` unless you re-enter the SMTP settings afterwards; otherwise the saved password can no longer be read.

## 5. First deploy

1. Run the deploy script (Plesk deploy, or `PHP_BIN=/opt/plesk/php/8.3/bin/php bash deploy/plesk-deploy.sh` over SSH).
2. Create the first admin user. Do not run `db:seed` on production, because the seeder creates demo accounts with the password `password`:

   ```bash
   php artisan tinker
   ```

   ```php
   $u = App\Models\User::create(['name' => 'Owner', 'email' => 'you@example.com', 'password' => Hash::make('a-strong-password')]);
   $u->forceFill(['is_admin' => true])->save();
   ```

3. Open `https://7assan.dev/admin` and go to **إعدادات البريد** (Email settings). Enter the SMTP host, port, encryption, username, password and the sender address, then save and send a test email. Team invitations cannot be sent until this works. Until then, shop owners see a message that email is not set up.
4. Check each plan in the admin panel. Ready orders appear only for plans that include **طلبات الانتظار** (`order_queue`). Cashier scanning needs **شاشة الكاشير** (`cashier_scan`), and the card and programs pages need **بطاقات الولاء** (`loyalty_cards`).

## 6. Scheduler

Add a Plesk cron job, every minute, using the PHP 8.3 path:

```bash
/opt/plesk/php/8.3/bin/php /full/path/to/repo/artisan schedule:run >> /dev/null 2>&1
```

This runs `merchants:suspend-expired` daily at 00:05, which suspends shops whose trial or paid period has ended.

## 7. Smoke test

- `https://7assan.dev/` loads in Arabic, and the language switch shows it in English.
- The header shows **تسجيل الدخول** to guests, and **لوحة التحكم** to signed-in users.
- `https://7assan.dev/c/{code}` shows a customer card, with the preview and stamps.
- `https://7assan.dev/app/login` signs in a shop owner.
- `https://7assan.dev/admin/login` signs in the admin.
- A shop owner cannot open `https://7assan.dev/admin` (they get a 403).
- Adding a stamp from the cashier screen updates the card.
- Inviting a team member sends an email (once SMTP is saved).

## Not ready yet

- Apple Wallet needs the Apple Developer account and certificates. Until then, leave the `MOBILE_PASS_APPLE_*` values empty; the card page shows the wallet as unavailable.
- Google Wallet needs an issuer ID and a service-account key. Leave the `MOBILE_PASS_GOOGLE_*` values empty until then.
