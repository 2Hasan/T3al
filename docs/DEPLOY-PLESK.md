# Deploying T3al on Plesk (test: 7assan.dev)

Hosts:

| Surface | Host | Path |
|---|---|---|
| Marketing and customer cards | `7assan.dev` | `/`, `/c/{card}` |
| Shop panel (owners and cashiers) | `panel.7assan.dev` | `/app` |
| Admin panel | `admin.7assan.dev` | `/admin` |

All three hosts point at the same Plesk document root: the repo's `public/` folder.

## 1. Plesk domains

1. Add `7assan.dev` as a domain (or subscription).
2. Add `panel.7assan.dev` and `admin.7assan.dev` as subdomains of it.
3. Set each one's document root to the `public` folder of the repo.
4. Enable Let's Encrypt SSL for all three.

## 2. Server requirements

- PHP 8.3 with `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `fileinfo`, `openssl`, `tokenizer`.
- Composer 2.
- Node 20 or newer, for `npm ci` and `npm run build`.
- PostgreSQL 16, with a database and a role for T3al.

## 3. Git deployment

1. Plesk > Git > add repository `https://github.com/2Hasan/T3al`, branch `main`.
2. If the repo is private, add the Plesk SSH public key to GitHub as a deploy key first.
3. Under "Additional deployment actions", add:

   ```bash
   bash deploy/plesk-deploy.sh
   ```

4. Turn on automatic deployment, or deploy by hand from Plesk.

The script installs dependencies without dev packages, builds the front end, runs migrations, publishes Filament assets, creates the storage link, and caches config, routes and views. It is safe to run on every deploy.

## 4. Environment

1. Copy `deploy/env.production.example` to `.env` in the repo folder.
2. Fill in `DB_*`, and set `APP_KEY` from `php artisan key:generate --show` run on the server.
3. Keep `APP_ENV=production` and `APP_DEBUG=false`.
4. Never commit `.env`.

## 5. First deploy

1. Run the deploy script (Plesk deploy, or `bash deploy/plesk-deploy.sh` over SSH).
2. Create the first admin user. Do not run `db:seed` on production, because the seeder creates demo accounts with the password `password`:

   ```bash
   php artisan tinker
   ```

   ```php
   App\Models\User::create(['name' => 'Owner', 'email' => 'you@example.com', 'password' => Hash::make('a-strong-password'), 'is_admin' => true]);
   ```

## 6. Scheduler

Add a Plesk cron job, every minute:

```bash
php /full/path/to/repo/artisan schedule:run >> /dev/null 2>&1
```

This runs `merchants:suspend-expired` daily at 00:05, which suspends shops whose trial or paid period has ended.

## 7. Smoke test

- `https://7assan.dev/` loads in Arabic, and `?` switches to English through the language link.
- `https://7assan.dev/c/{code}` shows a customer card.
- `https://panel.7assan.dev/app/login` signs in a shop owner.
- `https://admin.7assan.dev/admin/login` signs in the admin.
- A shop owner cannot open `admin.7assan.dev`.
- Adding a stamp from the cashier screen updates the card.

## Not ready yet

- Apple Wallet needs the Apple Developer account and certificates. Until then, leave the `MOBILE_PASS_APPLE_*` values empty; the card page shows the wallet as unavailable.
- Google Wallet needs an issuer ID and a service-account key. Leave the `MOBILE_PASS_GOOGLE_*` values empty until then.
