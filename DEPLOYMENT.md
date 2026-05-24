# DEPLOYMENT GUIDE — MI Laravel App on cPanel

> **Quick-start summary:** isolate repo → generate deploy key → configure server →
> add GitHub Secrets → push to `main` → deploy runs automatically.

---

## Table of Contents

1. [Required GitHub Secrets](#1-required-github-secrets)
2. [Repository Isolation (one-time local)](#2-repository-isolation-one-time-local)
3. [Generate Deploy Key (SSH)](#3-generate-deploy-key-ssh)
4. [Server Setup (one-time via SSH)](#4-server-setup-one-time-via-ssh)
5. [docroot Configuration in cPanel](#5-docroot-configuration-in-cpanel)
6. [cPanel Cron Job (Laravel Scheduler)](#6-cpanel-cron-job-laravel-scheduler)
7. [First Deploy](#7-first-deploy)
8. [Subsequent Deploys (automatic)](#8-subsequent-deploys-automatic)
9. [Rollback](#9-rollback)
10. [Fallback: cPanel Git™ Version Control](#10-fallback-cpanel-git-version-control)
11. [Warnings & Gotchas](#11-warnings--gotchas)

---

## 1. Required GitHub Secrets

Add these in **GitHub → Repository → Settings → Secrets and variables → Actions → New repository secret**:

| Secret name         | Value                              | Notes                              |
|---------------------|------------------------------------|------------------------------------|
| `SSH_HOST`          | `65.181.116.2`                     | cPanel server IP                   |
| `SSH_PORT`          | `22`                               | SSH port                           |
| `SSH_USER`          | `sanadnat1`                        | cPanel username                    |
| `SSH_PRIVATE_KEY`   | *(contents of `deploy_key` file)*  | ed25519 private key — see step 3   |
| `DEPLOY_PATH`       | `/home/sanadnat1/laravel_app`      | App dir **outside** `public_html`  |

---

## 2. Repository Isolation (one-time local)

> **Why:** The parent `D:\laragon\www` is a monorepo containing many unrelated projects
> and `.env` files. This app needs its own clean private repository.

Run these commands **inside** `D:\laragon\www\mi\mi\laravel_app`:

```bash
# 1. Initialise a new local git repo (isolated from the parent)
git init
git branch -M main

# 2. Stage all project files (the .gitignore will exclude secrets/vendor/etc.)
git add .
git status          # review what will be committed

# 3. Initial commit
git commit -m "chore: initial commit — Laravel app scaffold"

# 4. Create a PRIVATE repository on GitHub (do this manually in the browser):
#    github.com → New repository → Private → do NOT add README/gitignore

# 5. Connect and push
git remote add origin git@github.com:<your-org>/mi-laravel.git
git push -u origin main
```

---

## 3. Generate Deploy Key (SSH)

A deploy key is a dedicated ed25519 SSH key pair used only for this repository.

```bash
# Run locally (or anywhere with ssh-keygen)
ssh-keygen -t ed25519 -C "mi-laravel-deploy" -f ~/.ssh/mi_deploy_key -N ""

# This creates two files:
#   ~/.ssh/mi_deploy_key      ← PRIVATE key  (→ GitHub Secret SSH_PRIVATE_KEY)
#   ~/.ssh/mi_deploy_key.pub  ← PUBLIC key   (→ server authorized_keys)
```

### Add public key to the server

```bash
# Copy the public key content
cat ~/.ssh/mi_deploy_key.pub

# SSH into server and append it
ssh -p 22 sanadnat1@65.181.116.2
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo "PASTE_PUBLIC_KEY_HERE" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### Add private key to GitHub

Copy the **entire** content of `~/.ssh/mi_deploy_key` (including `-----BEGIN...` lines)
and paste it as the `SSH_PRIVATE_KEY` secret (see step 1).

---

## 4. Server Setup (one-time via SSH)

After the first GitHub Actions run completes (which copies the code to the server),
run the setup script **once**:

```bash
# From your local machine
ssh -p 22 sanadnat1@65.181.116.2 'bash -s' < deploy/server-setup.sh
```

The script will:
- Create `.env` from `.env.example` and prompt you to edit it
- Set `chmod 775` on `storage/` and `bootstrap/cache/`
- Run `php artisan key:generate`
- Run `php artisan migrate --seed --force`
- Create the `public/storage` symlink
- Cache config, routes, and views

**After the script pauses**, edit `.env` on the server:

```bash
ssh -p 22 sanadnat1@65.181.116.2
nano /home/sanadnat1/laravel_app/.env
```

Set at minimum:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
DB_HOST=localhost
DB_DATABASE=sanadnat1_mi_contracts   # cPanel DB names are prefixed with username
DB_USERNAME=sanadnat1_mi_user
DB_PASSWORD=YourStrongPassword
MAIL_HOST=mail.yourdomain.com
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=YourMailPassword
```

> **Brand assets note:** `storage/app/public/` (logo, images, pricing cards) is excluded
> from git and rsync. Upload brand assets manually via cPanel File Manager or:
> ```bash
> scp -r storage/app/public/brand/ sanadnat1@65.181.116.2:/home/sanadnat1/laravel_app/storage/app/public/
> ```

---

## 5. docroot Configuration in cPanel

The app code lives in `~/laravel_app` (outside `public_html` for security).
Only the `public/` subdirectory should be the web root.

### Option A — Preferred: cPanel Addon Domain / Subdomain docroot

1. cPanel → **Domains** (or Addon Domains / Subdomains)
2. Set the **Document Root** to: `/home/sanadnat1/laravel_app/public`
3. Save. Done — requests go directly to `public/index.php`.

### Option B — Bridge via public_html (if Option A is unavailable)

Create `/home/sanadnat1/public_html/index.php`:

```php
<?php
// Bridge: forward all requests to the Laravel app
define('LARAVEL_START', microtime(true));

require __DIR__.'/../laravel_app/public/index.php';
```

And copy the `.htaccess` from `laravel_app/public/` to `public_html/`:

```bash
cp /home/sanadnat1/laravel_app/public/.htaccess /home/sanadnat1/public_html/.htaccess
```

---

## 6. cPanel Cron Job (Laravel Scheduler)

In cPanel → **Cron Jobs**, add:

```
* * * * * cd /home/sanadnat1/laravel_app && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

> If `php` is version-specific on your host, replace `/usr/local/bin/php` with the
> correct path, e.g. `/opt/cpanel/ea-php83/root/usr/bin/php`.

---

## 7. First Deploy

1. Complete steps 1–5 above.
2. Push to `main`:
   ```bash
   git push origin main
   ```
3. Watch the run in **GitHub → Actions**.
4. After the run succeeds, SSH into the server and run `server-setup.sh` (step 4).
5. Configure docroot (step 5).
6. Visit your domain — the app should be live.

---

## 8. Subsequent Deploys (automatic)

Every `git push` to `main` triggers the workflow automatically:

```
push → checkout → composer install → npm build → rsync → migrate + cache
```

No manual steps required. Monitor at **GitHub → Actions**.

---

## 9. Rollback

If a deploy breaks the site, revert to the previous commit and push:

```bash
# Option A: revert the bad commit
git revert HEAD
git push origin main        # triggers a new clean deploy

# Option B: reset to a known-good commit (use with caution)
git reset --hard <good-sha>
git push --force-with-lease origin main
```

Database migrations that have run cannot be automatically rolled back.
If the migration is destructive, restore from a DB backup before rolling back code.

---

## 10. Fallback: cPanel Git™ Version Control

If GitHub Actions cannot reach the server (e.g. SSH port blocked), use the
`.cpanel.yml` fallback:

1. cPanel → **Git™ Version Control** → Create repository
2. Point the bare repo path to `/home/sanadnat1/repos/mi-laravel.git`
3. Set the deployment directory to `/home/sanadnat1/laravel_app`
4. Clone locally: `git clone sanadnat1@65.181.116.2:/home/sanadnat1/repos/mi-laravel.git`
5. Push to that remote — cPanel runs `.cpanel.yml` automatically.

**Limitations:**
- `vendor/` and `public/build/` must be pre-installed on the server (no Composer/Node in cPanel Git hooks by default).
- Run `composer install --no-dev` and `npm run build` manually on the server, or temporarily commit `vendor/` and `public/build/` (not recommended long-term).

---

## 11. Warnings & Gotchas

### ⚠️ rsync `--delete` is destructive

The workflow uses `rsync --delete`, which removes files on the server that are not
in the current build. If `DEPLOY_PATH` points to the wrong directory (e.g. `public_html`),
it **will delete** unrelated server files.

**Before the first push:**
- Confirm `DEPLOY_PATH=/home/sanadnat1/laravel_app` is correct.
- The path must be an otherwise-empty directory (or already contain the app).

### ⚠️ PHP version must match

`PHP_VERSION: '8.3'` in `deploy.yml` must match the server's PHP version.
A mismatch means `vendor/` built in CI won't work on the server.

Verify: `ssh -p 22 sanadnat1@65.181.116.2 'php -v'`

Then update the `PHP_VERSION` env in `.github/workflows/deploy.yml`.

### ⚠️ `.env` and `storage/` are never touched by rsync

These are excluded from every deploy. They persist on the server between deployments.
Edit `.env` on the server directly; never put secrets in the repository.

### ⚠️ Database backup before migrations

`php artisan migrate --force` runs on every deploy.
Take a DB backup before deploying schema changes:

```bash
ssh -p 22 sanadnat1@65.181.116.2
mysqldump -u sanadnat1_mi_user -p sanadnat1_mi_contracts > ~/backups/mi_$(date +%Y%m%d_%H%M).sql
```
