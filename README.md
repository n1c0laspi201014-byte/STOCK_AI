# PaperTrade AI

PaperTrade AI is a PHP 8/MySQL school-project dashboard for simulated stock trading. It can use real market data, but it never connects to a broker or submits a real order.

The main database is **`stockdata`**. The authenticated navbar contains exactly four pages: Dashboard, Stocks, Predictions, and Setup.

> This application is a paper-trading educational project. Market predictions are uncertain estimates generated from available data and are not financial advice. No real trades are placed.

## Fast local setup

1. Copy this folder to `C:\wamp64\www\papertrade-ai`.
2. Start WAMP64 and wait for its tray icon to become green.
3. Run `scripts\windows\01-check-requirements.bat`.
4. Run `scripts\windows\02-create-local-env.bat`.
5. Review `.env`; keep `DB_DATABASE=stockdata`.
6. Run `C:\wamp64\bin\php\php8.4.15\php.exe scripts\php\setup_database.php` (adapt the PHP folder to the installed version).
7. Open `http://localhost/papertrade-ai/public/health.php`, then `http://localhost/papertrade-ai/public/login`.

Development accounts:

| Name | Email | Password | Role |
|---|---|---|---|
| Admin Demo | `admin@papertrade.local` | `Admin123!` | admin |
| Trader Demo | `trader@papertrade.local` | `Trader123!` | trader |

Only `admin` and `trader` roles exist. Self-service registration always creates a Trader account; it cannot create an Admin account.

Change all passwords before any public deployment.

## External integrations

- Finnhub: set `MARKET_DATA_API_KEY` in `.env`, then run `php scripts/php/test_market_api.php AAPL`.
- Twelve Data fallback: set `MARKET_DATA_FALLBACK_API_KEY` only if needed.
- OpenRouter: set `OPENROUTER_API_KEY` and the exact `OPENROUTER_MODEL`, then run `php scripts/php/test_openrouter.php AAPL 7d`.
- Telegram: set `TELEGRAM_BOT_TOKEN` for the PHP test button or put the token in n8n credentials.
- n8n: replace `INTERNAL_N8N_API_KEY`, mirror it into Docker Compose, run `docker compose -f docker-compose.n8n.yml up -d`, and import the single `PaperTrade AI – Telegram Hub` workflow.

No secret is included in frontend JavaScript. `.env` is ignored by Git.

## Review and test documents

- [Implementation status](IMPLEMENTATION_STATUS.md)
- [Three-person handoff](SETUP_HANDOFF.md)
- [Team status](TEAM_STATUS.md)
- [Demo script](DEMO_SCRIPT.md)
- [Acceptance checklist](tests/manual/acceptance-checklist.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)

## Architecture

`public/index.php` routes requests to controllers. Services contain business logic, repositories use prepared PDO statements, MySQL is the source of truth, market/OpenRouter/Telegram clients remain server-side, and n8n calls API-key-protected internal endpoints. Composer is optional; `bootstrap/app.php` includes a PSR-4 fallback autoloader.
