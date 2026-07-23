# Manual WAMP64 setup

## Install or start WAMP64

1. Install WAMP64 from its official site if it is not present.
2. Start WAMP64 and wait for the tray icon to become green.
3. Open `http://localhost/`. If it fails, use WAMP's port test and see `TROUBLESHOOTING.md`.
4. Copy this project to `C:\wamp64\www\papertrade-ai` without copying real `.env` secrets into a public repository.

## PHP requirements

In WAMP > PHP > PHP extensions, enable `curl`, `pdo_mysql`, `openssl`, `json`, and `mbstring`. Run:

```bat
scripts\windows\01-check-requirements.bat
```

Expected: every line starts with `PASS`. A failure states the extension/file and likely fix.

## Environment and database

Run `scripts\windows\02-create-local-env.bat`, then edit `C:\wamp64\www\papertrade-ai\.env`:

```dotenv
APP_URL=http://localhost/papertrade-ai/public
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stockdata
DB_USERNAME=root
DB_PASSWORD=
```

Do not change the project database name from `stockdata`. Run:

```bat
php scripts\php\setup_database.php
```

Expected: `PASS Database connection`, `PASS Database stockdata exists`, `PASS Schema applied`, `PASS Admin and Trader users seeded`, `PASS Portfolios created`, `PASS Demo data created`.

If creation permission fails, open `http://localhost/phpmyadmin/`, create `stockdata` with `utf8mb4_unicode_ci`, and rerun the script.

## Apache routing

Open `http://localhost/papertrade-ai/public/health.php`, then `/login`. If health works but login gives 404, enable `rewrite_module`, set `AllowOverride All` for the WAMP web root, and restart Apache. `public/.htaccess` is already generated.
